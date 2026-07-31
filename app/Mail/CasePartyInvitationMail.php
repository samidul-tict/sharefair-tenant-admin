<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CasePartyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $caseNumber,
        public string $legalCounselName,
        public string $roleLabel,
        public ?string $clientName,
        public ?string $spouseName,
        public string $joinUrl,
    ) {
    }

    public function build()
    {
        return $this->subject('You have been added to a Share Fair case')
            ->view('emails.case-party-invitation')
            ->with([
                'recipientName' => $this->recipientName,
                'caseNumber' => $this->caseNumber,
                'legalCounselName' => $this->legalCounselName,
                'roleLabel' => $this->roleLabel,
                'clientName' => $this->clientName,
                'spouseName' => $this->spouseName,
                'joinUrl' => $this->joinUrl,
            ]);
    }
}
