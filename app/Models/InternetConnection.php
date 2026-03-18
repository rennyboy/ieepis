<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternetConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'isp',
        'account_number',
        'plan_name',
        'contracted_download_speed',
        'contracted_upload_speed',
        'actual_download_speed',
        'actual_upload_speed',
        'latency_ms',
        'speed_test_date',
        'ip_address',
        'connection_type',  // Fiber / DSL / Wireless / LTE / Satellite
        'status',           // active / inactive / suspended
        'monthly_cost',
        'subscription_start',
        'subscription_end',
        'remarks',
    ];

    protected $casts = [
        'contracted_download_speed' => 'float',
        'contracted_upload_speed'   => 'float',
        'actual_download_speed'     => 'float',
        'actual_upload_speed'       => 'float',
        'latency_ms'                => 'integer',
        'monthly_cost'              => 'decimal:2',
        'speed_test_date'           => 'date',
        'subscription_start'        => 'date',
        'subscription_end'          => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function getSpeedStatusAttribute(): string
    {
        if (!$this->actual_download_speed) return 'Not Tested';
        $ratio = $this->actual_download_speed / max($this->contracted_download_speed, 1);
        if ($ratio >= 0.8) return 'Excellent';
        if ($ratio >= 0.5) return 'Fair';
        return 'Poor';
    }
}
