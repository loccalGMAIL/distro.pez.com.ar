<?php

namespace App\Models;

use Database\Factories\UserTableColumnPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTableColumnPreference extends Model
{
    /** @use HasFactory<UserTableColumnPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table_key',
        'columns',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
