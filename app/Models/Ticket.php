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
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = self::generateTicketNumber(
                    $ticket->school_id,
                );
            }
        });
    }

    /**
     * Generate a unique ticket number
     * Format: TKT-YYYY-NNNNN where NNNNN increments
     *
     * Uses PostgreSQL advisory locks for concurrent-safe generation.
     */

    public static function generateTicketNumber($schoolId): string
    {
        if (empty($schoolId)) {
            Log::error("generateTicketNumber called with empty school_id.");
            throw new \InvalidArgumentException(
                "School ID cannot be empty for ticket number generation.",
            );
        }

        $year = now()->year;
        $lockKey = (int) ("{$schoolId}{$year}" % 2147483647);

        DB::statement("SELECT pg_advisory_xact_lock(?)", [$lockKey]);

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
                "Generated ticket number with pg_advisory_xact_lock for school_id {$schoolId}: {$ticketNumber}",
            );

            return $ticketNumber;
        });
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
