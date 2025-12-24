<?php

namespace App\Models;

use App\Concerns\ProviderConnectionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

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
        'client_secret',
        'refresh_token',
        'access_token',
    ];

    protected function client_secret(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => Crypt::encryptString($value),
            set: fn ($value) => Crypt::decryptString($value),
        );
    }

    protected function refresh_token(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => Crypt::encryptString($value),
            set: fn ($value) =>  Crypt::decryptString($value),
        );
    }

    protected function access_token(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => Crypt::encryptString($value),
            set: fn ($value) =>  Crypt::decryptString($value),
        );
    }

    protected function casts(): array
    {
        return [
            'connection_type' => ProviderConnectionType::class,
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
     * @return HasMany<Gateway, $this>
     */
    public function gateways(): HasMany
    {
        return $this->hasMany(Gateway::class, 'device_provider');
    }
}
