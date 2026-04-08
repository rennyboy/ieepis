<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

/**
 * Notification sent when a user account is reassigned.
 *
 * - Sends an email to the affected user and any admins passed as notifiable recipients.
 * - Stores a database notification payload that Filament's notification drawer can display.
 */
class UserReassignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Snapshot of user attributes before reassignment.
     *
     * @var array<string,mixed>
     */
    public array $before;

    /**
     * Snapshot of user attributes after reassignment.
     *
     * @var array<string,mixed>
     */
    public array $after;

    /**
     * The admin (actor) who performed the reassignment, if available.
     *
     * @var User|null
     */
    public ?User $actor;

    /**
     * Create a new notification instance.
     *
     * @param array<string,mixed> $before
     * @param array<string,mixed> $after
     * @param User|null $actor
     */
    public function __construct(array $before, array $after, ?User $actor = null)
    {
        $this->before = $before;
        $this->after = $after;
        $this->actor = $actor;
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
        $actorName = $this->actor?->name ?? 'an administrator';

        $mail = (new MailMessage())
            ->subject('Account reassigned')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line("Your account was updated by {$actorName}.")
            ->line('Previous details:')
            ->line('• Name: ' . ($this->before['name'] ?? '-'))
            ->line('• Email: ' . ($this->before['email'] ?? '-'))
            ->line('• Roles: ' . implode(', ', $this->before['roles'] ?? []))
            ->line('')
            ->line('Updated details:')
            ->line('• Name: ' . ($this->after['name'] ?? '-'))
            ->line('• Email: ' . ($this->after['email'] ?? '-'))
            ->line('• Roles: ' . implode(', ', $this->after['roles'] ?? []));

        // It's safer to avoid assuming a specific Filament route here; include a generic prompt.
        $mail->line('If you did not expect this change or need assistance, please contact your administrator.');

        return $mail;
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * Filament's notification drawer will use this payload (title/message) and can read additional context.
     *
     * @param  mixed  $notifiable
     * @return array<string,mixed>
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Account reassigned',
            'message' => 'Your account details were updated by ' . ($this->actor?->name ?? 'an administrator'),
            'before' => $this->before,
            'after' => $this->after,
            'actor_id' => $this->actor?->id,
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
