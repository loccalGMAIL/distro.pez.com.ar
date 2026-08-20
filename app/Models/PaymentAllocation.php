<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'allocatable_type',
        'allocatable_id',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }
}
