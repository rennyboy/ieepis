<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PpePhysicalCountItem — individual PPE line item within a physical count session.
 *
 * @property int $id
 * @property int $physical_count_id
 * @property int|null $equipment_id
 * @property string $article
 * @property string|null $description
 * @property string $property_number
 * @property string $unit_of_measure
 * @property float $unit_value
 * @property int $quantity_property_card
 * @property int $quantity_physical_count
 * @property int $shortage_quantity
 * @property float $shortage_value
 * @property int $overage_quantity
 * @property float $overage_value
 * @property string|null $remarks
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PpePhysicalCountItem extends Model
{
    use HasFactory;

    protected $table = 'ppe_physical_count_items';

    protected $fillable = [
        'physical_count_id',
        'equipment_id',
        'article',
        'description',
        'property_number',
        'unit_of_measure',
        'unit_value',
        'quantity_property_card',
        'quantity_physical_count',
        'shortage_quantity',
        'shortage_value',
        'overage_quantity',
        'overage_value',
        'remarks',
    ];

    protected $casts = [
        'unit_value' => 'decimal:2',
        'shortage_value' => 'decimal:2',
        'overage_value' => 'decimal:2',
        'quantity_property_card' => 'integer',
        'quantity_physical_count' => 'integer',
        'shortage_quantity' => 'integer',
        'overage_quantity' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $item): void {
            $card = (int) $item->quantity_property_card;
            $count = (int) $item->quantity_physical_count;
            $unitValue = (float) $item->unit_value;

            if ($count < $card) {
                $item->shortage_quantity = $card - $count;
                $item->shortage_value = $item->shortage_quantity * $unitValue;
                $item->overage_quantity = 0;
                $item->overage_value = 0;
            } elseif ($count > $card) {
                $item->overage_quantity = $count - $card;
                $item->overage_value = $item->overage_quantity * $unitValue;
                $item->shortage_quantity = 0;
                $item->shortage_value = 0;
            } else {
                $item->shortage_quantity = 0;
                $item->shortage_value = 0;
                $item->overage_quantity = 0;
                $item->overage_value = 0;
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\PpePhysicalCount, self>
     */
    public function physicalCount(): BelongsTo
    {
        return $this->belongsTo(PpePhysicalCount::class, 'physical_count_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Equipment, self>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
