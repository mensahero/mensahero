<?php

namespace App\Concerns;

enum TeamSessionKeys: string
{
    case CURRENT_TEAM_ID = 'current_team_id';
    case CURRENT_TEAM_NAME = 'current_team_name';
    case CURRENT_TEAM = 'current_team';

    public function key(): string
    {
        return match ($this) {
            self::CURRENT_TEAM_ID    => self::CURRENT_TEAM_ID->value,
            self::CURRENT_TEAM_NAME  => self::CURRENT_TEAM_NAME->value,
            self::CURRENT_TEAM       => self::CURRENT_TEAM->value,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CURRENT_TEAM_ID    => self::CURRENT_TEAM_ID->name,
            self::CURRENT_TEAM_NAME  => self::CURRENT_TEAM_NAME->name,
            self::CURRENT_TEAM       => self::CURRENT_TEAM->name,
        };

    }
}
