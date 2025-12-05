<?php

namespace App\Concerns;

use Illuminate\Support\Str;

enum RolesPermissions: string
{
    case Administrator = 'admin';
    case Standard = 'standard';
    case Lite = 'lite';

    public function label(): string
    {
        return match ($this) {
            self::Administrator    => 'Administrator',
            self::Standard         => 'Standard',
            self::Lite             => 'Lite',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Administrator    => 'Administrator user can perform any action on the team records and settings.',
            self::Standard         => 'Standard user have the ability to read, create, and update.',
            self::Lite             => 'Lite can only read data.',
        };
    }

    public function id(): string
    {
        return $this->value;
    }

    public function permissionSets(): array
    {
        return match ($this) {
            self::Administrator    => $this->permissionForAdmin(),
            self::Standard         => $this->permissionForStandard(),
            self::Lite             => $this->permissionForLite(),
        };
    }

    private function permissionSetPerFeatures(): array
    {
        return [
            'Teams' => [
                'team:create', 'team:read', 'team:update', 'team:delete', 'team:remove', 'team:invite', 'team:api',
            ],
            'Contacts' => [
                'contact:create', 'contact:read', 'contact:update', 'contact:delete',
            ],
        ];
    }

    private function permissionForAdmin(): array
    {
        return collect($this->permissionSetPerFeatures())
            ->flatten()
            ->toArray();

    }

    private function permissionForStandard(): array
    {
        return collect($this->permissionSetPerFeatures())
            ->flatten()
            ->filter(fn ($permission) => Str::of($permission)->doesntContain([':delete', ':invite', ':api', 'team:delete']))
            ->toArray();
    }

    private function permissionForLite(): array
    {
        return collect($this->permissionSetPerFeatures())
            ->flatten()
            ->filter(fn ($permission) => Str::of($permission)->contains(':read'))
            ->toArray();
    }
}
