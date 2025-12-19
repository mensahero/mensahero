<?php

namespace App\Models;

use App\Concerns\GatewayType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gateway extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'device_provider',
        'user_id',
        'name',
        'type',
        'share',
        'team_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function devices(): BelongsTo
    {
        return $this->belongsTo(Devices::class, 'device_provider');
    }

    public function providers(): BelongsTo
    {
        return $this->belongsTo(Providers::class, 'device_provider');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'device_provider' => 'string',
            'share'           => 'boolean',
            'team_id'         => 'string',
            'type'            => GatewayType::class,
        ];
    }
}
