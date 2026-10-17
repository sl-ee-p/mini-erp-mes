<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Support\DocumentNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_increment_per_prefix_and_year(): void
    {
        $numbers = app(DocumentNumber::class);

        $this->assertSame('SO-2026-0001', $numbers->next('SO', 2026));
        $this->assertSame('SO-2026-0002', $numbers->next('SO', 2026));
        $this->assertSame('PO-2026-0001', $numbers->next('PO', 2026));
        $this->assertSame('SO-2027-0001', $numbers->next('SO', 2027));
    }

    public function test_current_year_is_used_by_default(): void
    {
        $this->travelTo('2031-03-01');

        $this->assertSame('SO-2031-0001', app(DocumentNumber::class)->next('SO'));
    }
}
