<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Database\Eloquent\Collection;

/**
 * TicketObserver for handling ticket lifecycle events
 *
 * Sends notifications to relevant users (super-admin, sdo-admin, technician)
 * when tickets are created or updated, with priority-based annotations.
 */
class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        $this->sendNotification($ticket);
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        if ($ticket->isDirty("status") || $ticket->isDirty("priority")) {
            $this->sendNotification($ticket, "updated");
        }
    }

    /**
     * Send notification to relevant users based on ticket priority.
     *
     * @param Ticket $ticket The ticket instance
     * @param string $action The action type (created or updated)
     * @return void
     */
    private function sendNotification(
        Ticket $ticket,
        string $action = "created",
    ): void {
        // Define priority levels and colors
        $priorities = [
            "critical" => [
                "color" => "danger",
                "label" => "CRITICAL",
                "order" => 1,
            ],
            "high" => ["color" => "warning", "label" => "HIGH", "order" => 2],
            "medium" => ["color" => "info", "label" => "MEDIUM", "order" => 3],
            "low" => ["color" => "gray", "label" => "LOW", "order" => 4],
        ];

        $priorityData = $priorities[$ticket->priority] ?? $priorities["medium"];

        // Determine title based on action
        $title =
            $action === "created"
                ? "New Support Ticket: {$ticket->ticket_number}"
                : "Ticket Updated: {$ticket->ticket_number}";

        // Find users to notify (Technicians, SDO Admins, Super Admins)
        // Using whereHas with roles relationship to filter by roles
        /** @var Collection<int, User> $usersToNotify */
        $usersToNotify = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ["super-admin", "sdo-admin", "technician"]);
            })
            ->get();

        if ($usersToNotify->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body(
                "**Priority: {$priorityData["label"]}**\n\nIssue: {$ticket->issue_title}\nSchool: {$ticket->school->name}",
            )
            ->icon("heroicon-o-ticket")
            ->actions([
                Action::make("view")
                    ->button()
                    ->url(
                        route("filament.admin.resources.tickets.edit", [
                            "record" => $ticket->id,
                        ]),
                    ),
            ])
            ->sendToDatabase($usersToNotify);
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
