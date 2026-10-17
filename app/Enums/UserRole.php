<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Planner = 'planner';
    case Operator = 'operator';
    case Sales = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Planner => 'Production planner',
            self::Operator => 'Shop-floor operator',
            self::Sales => 'Sales',
        };
    }

    /**
     * Tailwind colour token used for role badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Admin => 'red',
            self::Planner => 'indigo',
            self::Operator => 'amber',
            self::Sales => 'emerald',
        };
    }

    /**
     * Named route a user lands on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Operator => 'shop-floor.index',
            default => 'dashboard',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
