<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Support\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_contains_escapes_wildcards_and_backslashes(): void
    {
        $this->assertSame('%100\%%', Like::contains('100%'));
        $this->assertSame('%a\_b%', Like::contains('a_b'));
        $this->assertSame('%a\\\\b%', Like::contains('a\\b'));
    }

    public function test_percent_matches_literally(): void
    {
        Customer::factory()->create(['name' => 'Plain Co']);
        $hundred = Customer::factory()->create(['name' => '100% Wood']);

        $this->assertSame(['100% Wood'], Customer::search('%')->pluck('name')->all());
        $this->assertTrue(Customer::search('100%')->get()->contains($hundred));
    }

    public function test_underscore_matches_literally(): void
    {
        Product::factory()->create(['sku' => 'FGAB', 'name' => 'Table']);
        $match = Product::factory()->create(['sku' => 'FG_AB', 'name' => 'Chair']);

        $this->assertSame([$match->id], Product::search('G_A')->pluck('id')->all());
    }

    public function test_materials_are_searched_literally_too(): void
    {
        Material::factory()->create(['name' => 'Oak plank', 'sku' => 'M-OAK']);
        $match = Material::factory()->create(['name' => '50% recycled', 'sku' => 'M-REC']);

        $this->assertSame([$match->id], Material::search('%')->pluck('id')->all());
    }

    public function test_user_filter_treats_percent_literally(): void
    {
        User::factory()->create(['name' => 'Aaron Admin']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => '%']))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
    }
}
