<?php

namespace App\Models;

use App\Concerns\ContactSources;
use App\Concerns\MobileCountryCode;
use App\Models\Filters\Contacts\MobileCountryCodeFilter;
use App\Models\Filters\Contacts\SourceTypeFilter;
use App\Models\Scopes\Contacts\UserContactsScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Mattiverse\Userstamps\Traits\Userstamps;

#[ScopedBy([UserContactsScope::class])]
class Contacts extends Model
{
    use HasFilters;
    use HasUuids;
    use IsSearchable;
    use Userstamps;

    protected array $searchable = [
        'name',
        'mobile',
    ];

    protected array $filters = [
        SourceTypeFilter::class,
        MobileCountryCodeFilter::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'mobile',
        'country_code',
        'source',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }

    public function casts(): array
    {
        return [
            'country_code' => MobileCountryCode::class,
            'source'       => ContactSources::class,
        ];
    }
}
