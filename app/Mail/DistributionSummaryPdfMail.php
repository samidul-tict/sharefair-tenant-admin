<?php

namespace App\Mail;

use App\Models\CourtCase;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DistributionSummaryPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CourtCase $case,
        private string $pdfContent,
        private string $filename,
        public string $recipientName,
    ) {
    }

    public function build()
    {
        return $this->subject('Distribution summary for case ' . $this->case->case_number)
            ->view('emails.distribution-summary-pdf')
            ->with([
                'case' => $this->case,
                'recipientName' => $this->recipientName,
            ])
            ->attachData($this->pdfContent, $this->filename, [
                'mime' => 'application/pdf',
            ]);
    }
}
