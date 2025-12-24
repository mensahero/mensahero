<?php

namespace App\Models;

use App\Concerns\DeviceStatus;
use App\Models\Scopes\OwnedDevicesScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([OwnedDevicesScope::class])]
class Devices extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'device_name',
        'manufacturer',
        'modelName',
        'osName',
        'status',
        'last_seen',
        'extras',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Gateway, $this>
     */
    public function gateways(): HasMany
    {
        return $this->hasMany(Gateway::class, 'device_provider');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'last_seen' => 'timestamp',
            'extras'    => 'array',
            'status'    => DeviceStatus::class,
        ];
    }
}
