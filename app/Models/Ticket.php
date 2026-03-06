<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'equipment_id',
        'reporter_id',
        'ticket_number',
        'issue_title',
        'description',
        'priority',       // low / medium / high / critical
        'status',         // open / in-progress / pending / resolved / closed
        'assigned_to_id',
        'resolution_notes',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($ticket) {
            $ticket->ticket_number = 'TKT-' . now()->format('Y') . '-' . str_pad(
                static::whereYear('created_at', now()->year)->count() + 1,
                4, '0', STR_PAD_LEFT
            );
        });
    }

    public function school()  { return $this->belongsTo(School::class); }
    public function equipment() { return $this->belongsTo(Equipment::class); }
    public function reporter() { return $this->belongsTo(Employee::class, 'reporter_id'); }
    public function assignedTo() { return $this->belongsTo(Employee::class, 'assigned_to_id'); }

    public function scopeOpen($query) { return $query->where('status', 'open'); }
    public function scopeInProgress($query) { return $query->where('status', 'in-progress'); }
    public function scopeResolved($query) { return $query->where('status', 'resolved'); }
    public function scopeHighPriority($query) { return $query->where('priority', 'high')->orWhere('priority', 'critical'); }
}
