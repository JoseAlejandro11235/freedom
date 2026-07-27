<?php

namespace Tests\Unit;

use App\Support\CatalogSearch;
use PHPUnit\Framework\TestCase;

class CatalogSearchTest extends TestCase
{
    public function test_normalize_removes_apostrophes_and_case(): void
    {
        $this->assertSame('victorias secret', CatalogSearch::normalize("Victoria's Secret"));
    }

    public function test_like_pattern_allows_flexible_spacing(): void
    {
        $this->assertSame('%victorias%secret%', CatalogSearch::likePattern('victorias secret'));
    }
}
