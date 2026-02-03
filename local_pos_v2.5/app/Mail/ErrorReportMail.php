<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErrorReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $screenshotPath;

    /**
     * Create a new message instance.
     */
    public function __construct($data, $screenshotPath = null)
    {
        $this->data = $data;
        $this->screenshotPath = $screenshotPath;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $email = $this->subject('⚠️ RJ POS - Yeni Xəta Bildirişi')
                      ->view('emails.error_report'); // View faylının yolu

        // Əgər screenshot yüklənibsə, emailə əlavə et
        if ($this->screenshotPath && file_exists($this->screenshotPath)) {
            $email->attach($this->screenshotPath, [
                'as' => 'screenshot.jpg',
                'mime' => 'image/jpeg',
            ]);
        }

        return $email;
    }
}
