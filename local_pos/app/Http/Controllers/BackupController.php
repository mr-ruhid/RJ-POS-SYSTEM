<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    private $backupFolder = 'backups';

    public function index()
    {
        // Qovluq yoxdursa yarat
        if (!Storage::exists($this->backupFolder)) {
            Storage::makeDirectory($this->backupFolder);
        }

        // --- AVTOMATİK BACKUP YOXLAMASI ---
        // Səhifəyə hər dəfə giriləndə yoxlayır:
        // Saat 13:00-14:00 arasındadırsa və bu gün backup yoxdursa -> Yarat
        $this->checkAndRunAutoBackup();

        $lastBackupDate = Setting::where('key', 'last_backup_date')->value('value');

        $files = [];
        $allFiles = Storage::files($this->backupFolder);

        foreach ($allFiles as $file) {
            $name = basename($file);
            $size = Storage::size($file);
            $time = Storage::lastModified($file);

            if (str_ends_with($name, '.zip') || str_ends_with($name, '.sql')) {
                $files[] = [
                    'name' => $name,
                    'size' => $this->formatSize($size),
                    'date' => Carbon::createFromTimestamp($time)->format('d.m.Y H:i:s'),
                    'type' => str_ends_with($name, '.zip') ? 'full' : 'db',
                    'path' => $file
                ];
            }
        }

        // Yenilər yuxarıda
        usort($files, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return view('system.backup.index', compact('files', 'lastBackupDate'));
    }

    public function create(Request $request)
    {
        $type = $request->type;
        $filename = 'backup_' . date('Y-m-d_H-i-s');

        // --- VACİB: Ağır proseslər üçün limitləri LƏĞV EDİRİK ---
        set_time_limit(0); // Sonsuz vaxt
        ini_set('memory_limit', '-1'); // Sonsuz RAM

        try {
            if ($type === 'db') {
                $content = $this->exportDatabase();
                Storage::put($this->backupFolder . '/' . $filename . '.sql', $content);
                $successMsg = 'Verilənlər bazası nüsxəsi yaradıldı!';
            }
            else {
                // ZipArchive yoxlanışı
                if (!class_exists('ZipArchive')) {
                    return back()->with('error', 'Serverdə ZipArchive modulu aktiv deyil.');
                }

                $zipFileName = $filename . '.zip';

                // Storage::path istifadə edirik ki, tam yolu dəqiq tapsın
                if (!Storage::exists($this->backupFolder)) {
                    Storage::makeDirectory($this->backupFolder);
                }
                $zipPath = Storage::path($this->backupFolder . '/' . $zipFileName);

                $zip = new ZipArchive;
                // CREATE | OVERWRITE istifadə edirik
                $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                if ($opened === TRUE) {

                    // 1. SQL əlavə et (Arxivin kökündə database_backup.sql kimi)
                    $sqlContent = $this->exportDatabase();
                    $zip->addFromString('database_backup.sql', $sqlContent);

                    // 2. ROOT (KÖK) QOVLUQDAKI BÜTÜN FAYLLARI GÖTÜR
                    $rootPath = base_path();

                    // Recursive Iterator ilə bütün layihəni gəzirik
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($rootPath),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $name => $file) {
                        // Qovluqları keç, yalnız faylları götür
                        if (!$file->isFile()) continue;

                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($rootPath) + 1);
                        $relativePath = str_replace('\\', '/', $relativePath);

                        // --- İSTİSNALAR (Bunları backup etmirik) ---
                        // A. Backup qovluğunun özünü GÖTÜRMƏ
                        if (str_contains($relativePath, 'storage/app/backups')) continue;
                        // B. Git qovluğu
                        if (str_contains($relativePath, '.git/')) continue;
                        // C. Node modules (Çox böyükdür və lazım deyil)
                        if (str_contains($relativePath, 'node_modules/')) continue;

                        $zip->addFile($filePath, $relativePath);
                    }

                    $zip->close();

                    if (!file_exists($zipPath)) {
                        return back()->with('error', 'ZIP yaradıldı, amma fayl tapılmadı. İcazə xətası ola bilər.');
                    }

                    $successMsg = 'Tam sistem nüsxəsi (Bütün fayllar + SQL) yaradıldı!';
                } else {
                    return back()->with('error', 'ZIP faylı açıla bilmədi. Xəta kodu: ' . $opened);
                }
            }

            // Tarixi yenilə
            Setting::updateOrCreate(
                ['key' => 'last_backup_date'],
                ['value' => Carbon::now()->format('d.m.Y H:i')]
            );

            return back()->with('success', $successMsg);

        } catch (\Exception $e) {
            return back()->with('error', 'Xəta baş verdi: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        $path = $this->backupFolder . '/' . $filename;
        return Storage::exists($path) ? Storage::download($path) : back()->with('error', 'Fayl tapılmadı.');
    }

    public function delete($filename)
    {
        $path = $this->backupFolder . '/' . $filename;
        if (Storage::exists($path)) {
            Storage::delete($path);
            return back()->with('success', 'Nüsxə silindi.');
        }
        return back()->with('error', 'Fayl tapılmadı.');
    }

    public function restoreDb($filename)
    {
        if (!str_ends_with($filename, '.sql')) {
            return back()->with('error', 'Yalnız .sql faylları bərpa edilə bilər.');
        }

        $path = $this->backupFolder . '/' . $filename;
        if (!Storage::exists($path)) return back()->with('error', 'Fayl tapılmadı.');

        try {
            $sql = Storage::get($path);
            if (empty(trim($sql))) return back()->with('error', 'SQL faylı boşdur.');

            DB::beginTransaction();
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::unprepared($sql);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::commit();

            return back()->with('success', 'Verilənlər bazası uğurla bərpa edildi!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bərpa xətası: ' . $e->getMessage());
        }
    }

    // --- Köməkçi Funksiyalar ---

    // Avtomatik Backup Məntiqi
    private function checkAndRunAutoBackup()
    {
        try {
            // İndiki saat
            $hour = date('H');

            // Yalnız 13:00 - 13:59 arasında işləsin
            if ($hour != 13) return;

            // Bu gün artıq avto-backup olunubmu?
            $today = date('Y-m-d');
            $autoBackupKey = 'auto_backup_' . $today;

            // Sadə yoxlama (Setting və ya Cache istifadə edə bilərik)
            $alreadyDone = Setting::where('key', $autoBackupKey)->exists();

            if (!$alreadyDone) {
                // SQL Backup Yarat
                set_time_limit(0);
                $filename = 'auto_backup_' . date('Y-m-d_H-i-s');
                $content = $this->exportDatabase();
                Storage::put($this->backupFolder . '/' . $filename . '.sql', $content);

                // Qeyd et ki, bu gün edildi
                Setting::create(['key' => $autoBackupKey, 'value' => 'done']);

                // Son backup tarixini yenilə
                Setting::updateOrCreate(
                    ['key' => 'last_backup_date'],
                    ['value' => Carbon::now()->format('d.m.Y H:i')]
                );
            }
        } catch (\Exception $e) {
            // Səssiz xəta (istifadəçiyə mane olmasın)
            Log::error('Auto Backup Error: ' . $e->getMessage());
        }
    }

    private function exportDatabase()
    {
        $tables = DB::select('SHOW TABLES');
        $output = "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            try {
                $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
                $output .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $output .= $createTable[0]->{'Create Table'} . ";\n\n";

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function($val) {
                        if (is_null($val)) return "NULL";
                        return "'" . addslashes($val) . "'";
                    }, (array)$row);
                    $output .= "INSERT INTO `$tableName` VALUES (" . implode(", ", $values) . ");\n";
                }
                $output .= "\n";
            } catch (\Exception $e) { continue; }
        }
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $output;
    }

    private function formatSize($bytes)
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        return number_format($bytes / 1024, 2) . ' KB';
    }
}
