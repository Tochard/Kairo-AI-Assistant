<?php

namespace App\Models;

use App\Enums\TrackableDirection;
use Database\Factories\TrackableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $goal_id
 * @property string $name
 * @property string $unit
 * @property TrackableDirection $direction
 * @property float|null $target_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'unit', 'direction', 'target_value', 'goal_id'])]
class Trackable extends Model
{
    /** @use HasFactory<TrackableFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => TrackableDirection::class,
            'target_value' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * @return HasMany<TrackableLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TrackableLog::class);
    }
}
