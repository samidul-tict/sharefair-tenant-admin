<?php

namespace App\Jobs;

use App\Mail\DistributionSummaryPdfMail;
use App\Models\CourtCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendDistributionSummaryEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  array<int, array{email: string, name: string}>  $recipients
     */
    public function __construct(
        public int $caseId,
        public array $recipients,
        public string $pdfStoragePath,
        public string $filename,
    ) {
    }

    public function handle(): void
    {
        if (!Storage::disk('local')->exists($this->pdfStoragePath)) {
            throw new \RuntimeException('Distribution summary PDF not found for email delivery.');
        }

        $case = CourtCase::with(['caseType', 'caseStatus'])->findOrFail($this->caseId);
        $pdfContent = Storage::disk('local')->get($this->pdfStoragePath);

        try {
            foreach ($this->recipients as $recipient) {
                Mail::to($recipient['email'])->send(
                    new DistributionSummaryPdfMail(
                        $case,
                        $pdfContent,
                        $this->filename,
                        $recipient['name']
                    )
                );
            }
        } finally {
            Storage::disk('local')->delete($this->pdfStoragePath);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Storage::disk('local')->delete($this->pdfStoragePath);
        report($exception);
    }
}
