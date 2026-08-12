<?php

namespace App\Models;

use Database\Factories\TrackableLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $trackable_id
 * @property float $value
 * @property string|null $note
 * @property Carbon $logged_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['value', 'note', 'logged_at'])]
class TrackableLog extends Model
{
    /** @use HasFactory<TrackableLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'float',
            'logged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Trackable, $this>
     */
    public function trackable(): BelongsTo
    {
        return $this->belongsTo(Trackable::class);
    }
}
