<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_operators_land_on_the_shop_floor(): void
    {
        $this->assertSame('shop-floor.index', UserRole::Operator->homeRoute());
    }

    public function test_office_roles_land_on_the_dashboard(): void
    {
        foreach ([UserRole::Admin, UserRole::Planner, UserRole::Sales] as $role) {
            $this->assertSame('dashboard', $role->homeRoute());
        }
    }

    public function test_options_cover_every_role(): void
    {
        $this->assertSame(['admin', 'planner', 'operator', 'sales'], array_keys(UserRole::options()));
    }
}
