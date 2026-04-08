<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Ticket;

/**
 * Notification sent when a ticket is marked resolved.
 *
 * - Sends an email to the notifiable users (queued).
 * - Stores a database notification payload that Filament's notification drawer can display.
 */
class TicketResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The ticket instance.
     *
     * @var Ticket
     */
    public Ticket $ticket;

    /**
     * Create a new notification instance.
     *
     * @param Ticket $ticket
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int,string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $ticket = $this->ticket;

        $mail = (new MailMessage())
            ->subject('Ticket resolved: ' . $ticket->ticket_number)
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('The following ticket has been marked as resolved and is ready for pickup:')
            ->line('Ticket: ' . $ticket->ticket_number)
            ->line('Issue: ' . $ticket->issue_title)
            ->line('School: ' . ($ticket->school?->name ?? 'N/A'))
            ->action('View ticket', route('filament.admin.resources.tickets.edit', ['record' => $ticket->id]))
            ->line('Please coordinate with the technician to arrange pickup or further instructions.');

        return $mail;
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @param  mixed  $notifiable
     * @return array<string,mixed>
     */
    public function toDatabase($notifiable): array
    {
        $ticket = $this->ticket;

        return [
            'title' => 'Ticket resolved: ' . $ticket->ticket_number,
            'message' => 'The ticket "' . $ticket->issue_title . '" has been resolved and is ready for pickup.',
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'issue_title' => $ticket->issue_title,
            'school' => $ticket->school?->name,
        ];
    }

    /**
     * Fallback array representation.
     *
     * @param  mixed  $notifiable
     * @return array<string,mixed>
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
