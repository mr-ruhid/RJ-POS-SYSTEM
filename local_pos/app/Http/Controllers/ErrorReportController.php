<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Mail\ErrorReportMail;

class ErrorReportController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'description' => 'required|string|min:3',
            'screenshot'  => 'nullable|image|max:3072',
        ]);

        try {
            $user = Auth::user();

            $storeName = Setting::where('key', 'store_name')->value('value') ?? 'Təyin olunmayıb';
            $storePhone = Setting::where('key', 'store_phone')->value('value') ?? 'Yoxdur';

            $data = [
                'time'      => date('d.m.Y H:i'),
                'store_name' => $storeName,
                'store_phone' => $storePhone,
                'user_id'   => $user ? $user->id : 'Qonaq',
                'user_name' => $user ? $user->name : 'Qonaq',
                'user_email' => $user ? $user->email : 'Email yoxdur',
                'url'       => url()->previous(),
                'ip'        => $request->ip(),
                // XƏTANIN HƏLLİ: user_agent əlavə edildi
                'user_agent' => $request->header('User-Agent'),
                'description' => $request->description,
                'has_screenshot' => false
            ];

            $screenshotPath = null;

            if ($request->hasFile('screenshot')) {
                $file = $request->file('screenshot');
                $filename = 'error_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('temp_errors', $filename, 'public');
                $screenshotPath = storage_path('app/public/' . $path);
                $data['has_screenshot'] = true;
            }

            // DİQQƏT: Bura öz real emailini yaz
            $myEmail = 'ruhidjavadoff@gmail.com';

            Mail::to($myEmail)->send(new ErrorReportMail($data, $screenshotPath));

            return back()->with('success', 'Xəta bildirişi uğurla göndərildi!');

        } catch (\Exception $e) {
            Log::error('Mail Xətası: ' . $e->getMessage());
            return back()->with('error', 'Xəta baş verdi: ' . $e->getMessage());
        }
    }
}
