<?php declare(strict_types=1);

namespace DeRidderDenHertog\Tests\GetDayTurnover;

use DeRidderDenHertog\Core\Type\Parameter\PerPage;
use DeRidderDenHertog\GetDayTurnover\Pagination\Cursor;
use DeRidderDenHertog\GetDayTurnover\Pagination\CursorPaginator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CursorPaginatorTest extends TestCase
{
    #[Test]
    #[Group('get-day-turnover')]
    public function has_more_when_count_equals_per_page(): void
    {
        $paginator = CursorPaginator::of(Cursor::at(10), 25, PerPage::count(25));

        $this->assertTrue($paginator->hasMore());
        $this->assertFalse($paginator->onLastPage());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function has_more_when_count_exceeds_per_page(): void
    {
        $paginator = CursorPaginator::of(Cursor::at(10), 30, PerPage::count(25));

        $this->assertTrue($paginator->hasMore());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function has_no_more_when_count_less_than_per_page(): void
    {
        $paginator = CursorPaginator::of(Cursor::at(10), 15, PerPage::count(25));

        $this->assertFalse($paginator->hasMore());
        $this->assertTrue($paginator->onLastPage());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function on_first_page_at_position_zero(): void
    {
        $paginator = CursorPaginator::of(Cursor::start(), 25, PerPage::count(25));

        $this->assertTrue($paginator->onFirstPage());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function on_first_page_within_first_page_range(): void
    {
        $paginator = CursorPaginator::of(Cursor::at(10), 25, PerPage::count(25));

        $this->assertTrue($paginator->onFirstPage());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function not_on_first_page_beyond_first_page_range(): void
    {
        $paginator = CursorPaginator::of(Cursor::at(50), 25, PerPage::count(25));

        $this->assertFalse($paginator->onFirstPage());
    }

    #[Test]
    #[Group('get-day-turnover')]
    public function exposes_cursor(): void
    {
        $cursor = Cursor::at(42);
        $paginator = CursorPaginator::of($cursor, 25, PerPage::count(25));

        $this->assertSame(42, $paginator->cursor->toInteger());
    }
}
