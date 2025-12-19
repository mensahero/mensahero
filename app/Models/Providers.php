<?php

namespace App\Models;

use App\Concerns\ProviderConnectionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Providers extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'connection_type',
        'scope',
        'client_id',
        'client_secret',
        'refresh_token',
        'access_token',
        'domain',
    ];

    protected $hidden = [
        'refresh_token',
        'access_token',
    ];

    // TODO: Add a mutator/accessor for safe guarding of the token

    protected function casts(): array
    {
        return [
            'connection_type' => ProviderConnectionType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gateways(): HasMany
    {
        return $this->hasMany(Gateway::class, 'device_provider');
    }
}
