<?php

namespace App\Models;

use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "school_id",
        "equipment_id",
        "reporter_id",
        "ticket_number",
        "issue_title",
        "description",
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

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope());
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            // Generate unique ticket number with timestamp to avoid duplicates
            $ticket->ticket_number = self::generateUniqueTicketNumber();
        });
    }

    /**
     * Generate a unique ticket number
     * Format: TKT-YYYY-NNNNN where NNNNN increments
     */
    public static function generateUniqueTicketNumber(): string
    {
        $year = now()->format("Y");
        $prefix = "TKT-{$year}-";
        
        // Get the highest number for this year
        $lastTicket = static::whereYear("created_at", $year)
            ->orderBy("id", "desc")
            ->first();
        
        if ($lastTicket && preg_match("/TKT-{$year}-(\d+)/", $lastTicket->ticket_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 5, "0", STR_PAD_LEFT);
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
