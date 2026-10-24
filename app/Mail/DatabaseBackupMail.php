<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param array{
     *     path: string,
     *     filename: string,
     *     size_formatted: string,
     *     created_at: Carbon,
     *     tables_count: int,
     *     database: string
     * } $backup
     */
    public function __construct(public array $backup) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $dateStr = $this->backup['created_at']->format('Y-m-d');
        $appName = config('app.name', 'NTTN Portal');

        return new Envelope(
            subject: "[{$appName}] Automated Database Disaster Recovery Backup — {$dateStr}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.database_backup',
            with: [
                'backup' => $this->backup,
                'appName' => config('app.name', 'NTTN Portal'),
                'serverIp' => request()->server('SERVER_ADDR') ?? '10.89.25.72',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (file_exists($this->backup['path'])) {
            return [
                Attachment::fromPath($this->backup['path'])
                    ->as($this->backup['filename'])
                    ->withMime(str_ends_with($this->backup['filename'], '.gz') ? 'application/gzip' : 'application/sql'),
            ];
        }

        return [];
    }
}
