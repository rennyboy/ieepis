<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EquipmentAssignment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'equipment_id',
        'employee_id',
        'new_accountable_id',
        'custodian_id',
        'assigned_at',
        'custodian_received_at',
        'returned_at',
        'new_accountable_received_at',
        'assigned_by',
        'transaction_type',
        'supporting_doc_type',
        'supporting_doc_no',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'assigned_at'                => 'date',
        'custodian_received_at'      => 'date',
        'returned_at'                => 'date',
        'new_accountable_received_at'=> 'date',
        'is_active'                  => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function newAccountable()
    {
        return $this->belongsTo(Employee::class, 'new_accountable_id');
    }

    public function custodian()
    {
        return $this->belongsTo(Employee::class, 'custodian_id');
    }
}
