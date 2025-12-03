<?php

namespace App\Mail\Team;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $actionUrl, public readonly TeamInvitation $teamInvitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Team Invitation - You are invited to join our team!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team.team-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
