<?php

namespace App\Models;

use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        "school_id",
        "equipment_id",
        "reporter_id",
        "ticket_number",
        "issue_title",
        "description",
        "ticket_type",
        "priority",
        "status",
        "assigned_to_id",
        "resolution_notes",
        "resolved_at",
        "closed_at",
    ];

    protected $casts = [
        "resolved_at" => "datetime",
        "closed_at" => "datetime",
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            $ticket->ticket_number = self::generateTicketNumber(
                $ticket->school_id,
            );
        });
    }

    /**
     * Generate a unique ticket number
     * Format: TKT-YYYY-NNNNN where NNNNN increments
     */

    public static function generateTicketNumber($schoolId): string
    {
        if (empty($schoolId)) {
            Log::error("generateTicketNumber called with empty school_id.");
            throw new \InvalidArgumentException(
                "School ID cannot be empty for ticket number generation.",
            );
        }

        // Use a MySQL-level named lock to serialize generation per school+year.
        $year = now()->year;
        $lockName = "ticket_number_gen_school_{$schoolId}_{$year}";
        $lockAcquired = false;

        try {
            // Try to acquire the lock with a 10-second timeout.
            $res = DB::select("SELECT GET_LOCK(?, 10) as got", [$lockName]);
            $lockAcquired = isset($res[0]->got) && intval($res[0]->got) === 1;

            if (!$lockAcquired) {
                // Could not get lock in time.
                Log::warning(
                    "Could not acquire MySQL GET_LOCK({$lockName}) for ticket generation.",
                );
                throw new \RuntimeException(
                    "Could not acquire lock to generate ticket number. Try again.",
                );
            }

            // Inside the lock, run a short transaction to read the last ticket and create the next number.
            return DB::transaction(function () use ($schoolId, $year) {
                $lastTicket = Ticket::whereYear("created_at", $year)
                    ->where("school_id", $schoolId)
                    ->latest("id")
                    ->first();

                $next = $lastTicket
                    ? ((int) substr($lastTicket->ticket_number, -5)) + 1
                    : 1;

                $ticketNumber = sprintf("TKT-%s-%05d", $year, $next);

                Log::info(
                    "Generated ticket number with GET_LOCK for school_id {$schoolId}: {$ticketNumber}",
                );

                return $ticketNumber;
            });
        } finally {
            // Always attempt to release the lock if we acquired it.
            if ($lockAcquired) {
                try {
                    DB::select("SELECT RELEASE_LOCK(?)", [$lockName]);
                } catch (\Throwable $e) {
                    // Log but don't prevent the main flow from continuing.
                    Log::warning(
                        "Failed to release GET_LOCK({$lockName}): " .
                            $e->getMessage(),
                    );
                }
            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\School, self>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Equipment, self>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "reporter_id");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Employee, self>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, "assigned_to_id");
    }

    public function scopeOpen($query)
    {
        return $query->where("status", "open");
    }

    public function scopeInProgress($query)
    {
        return $query->where("status", "in-progress");
    }

    public function scopeResolved($query)
    {
        return $query->where("status", "resolved");
    }

    public function scopeHighPriority($query)
    {
        return $query
            ->where("priority", "high")
            ->orWhere("priority", "critical");
    }
}
