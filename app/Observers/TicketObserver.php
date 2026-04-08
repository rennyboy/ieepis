<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketResolvedNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * TicketObserver for handling ticket lifecycle events
 *
 * Sends notifications to relevant users (super-admin, sdo-admin, technician)
 * when tickets are created or updated, with priority-based annotations.
 *
 * When a ticket is marked 'resolved' this observer will:
 *  - store a Filament (database) notification for the school's school-admin(s)
 *  - send an email (queued) using the TicketResolvedNotification to the same recipients
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

        // If ticket was marked 'resolved', notify the school's admins that it's ready for pickup
        if ($ticket->isDirty("status") && $ticket->status === "resolved") {
            $this->notifySchoolAboutResolved($ticket);
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
        // Define priority levels and colors/labels
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

        // Find core users to notify (Technicians, SDO Admins, Super Admins)
        /** @var Collection<int, User> $usersToNotify */
        $usersToNotify = User::query()
            ->whereHas("roles", function ($query) {
                $query->whereIn("name", [
                    "super-admin",
                    "sdo-admin",
                    "technician",
                ]);
            })
            ->get();

        // If no recipients and the ticket isn't resolved, nothing to do
        if ($usersToNotify->isEmpty() && $ticket->status !== "resolved") {
            return;
        }

        // Build recipients collection starting from core recipients
        $recipients = $usersToNotify;

        // If ticket is resolved, also include the school's school-admin(s)
        if ($ticket->status === "resolved" && $ticket->school_id) {
            $schoolAdmins = User::query()
                ->where("school_id", $ticket->school_id)
                ->whereHas("roles", function ($q) {
                    $q->where("name", "school-admin");
                })
                ->get();

            if ($schoolAdmins->isNotEmpty()) {
                $recipients = $recipients
                    ->merge($schoolAdmins)
                    ->unique("id")
                    ->values();
            }
        }

        // If no recipients then exit
        if ($recipients->isEmpty()) {
            return;
        }

        // Create and send Filament (database) notification for UI drawer/modal
        FilamentNotification::make()
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
            ->sendToDatabase($recipients);

        // Also send email notifications (queued) for resolved tickets or important updates.
        // We send the mail for resolved status and for updates when the core recipients exist.
        // Use the TicketResolvedNotification which should implement ShouldQueue and handle mail + database payload.
        try {
            // Only send email if the TicketResolvedNotification exists; sending will fail otherwise.
            // For 'updated' events we may still want to email depending on priority; currently we email for resolved.
            if ($ticket->status === "resolved") {
                NotificationFacade::send(
                    $recipients,
                    new TicketResolvedNotification($ticket),
                );
            }
        } catch (\Throwable $e) {
            // Fail silently but log for later inspection — do not break request flow.
            \Log::error("Failed to send ticket resolved emails", [
                "ticket_id" => $ticket->id,
                "error" => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify school admins that a ticket has been resolved and is ready for pickup
     *
     * This method is focused on resolved-specific logic to ensure both DB and email channels
     * are used for the school's administrators.
     *
     * @param Ticket $ticket
     * @return void
     */
    private function notifySchoolAboutResolved(Ticket $ticket): void
    {
        if (!$ticket->school_id) {
            return;
        }

        $schoolAdmins = User::query()
            ->where("school_id", $ticket->school_id)
            ->whereHas("roles", function ($q) {
                $q->where("name", "school-admin");
            })
            ->get();

        if ($schoolAdmins->isEmpty()) {
            return;
        }

        // Database notification for school admins (shows in Filament notifications drawer)
        FilamentNotification::make()
            ->title("Ticket resolved: {$ticket->ticket_number}")
            ->body(
                "The ticket \"{$ticket->issue_title}\" has been marked as resolved and is ready for pickup. Please coordinate with the technician.",
            )
            ->icon("heroicon-o-truck")
            ->actions([
                Action::make("view")
                    ->button()
                    ->url(
                        route("filament.admin.resources.tickets.edit", [
                            "record" => $ticket->id,
                        ]),
                    ),
            ])
            ->sendToDatabase($schoolAdmins);

        // Send queued email notification to school admins (and keep database notification as primary UI)
        try {
            NotificationFacade::send(
                $schoolAdmins,
                new TicketResolvedNotification($ticket),
            );
        } catch (\Throwable $e) {
            \Log::error(
                "Failed to send ticket resolved email to school admins",
                [
                    "ticket_id" => $ticket->id,
                    "school_id" => $ticket->school_id,
                    "error" => $e->getMessage(),
                ],
            );
        }
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
