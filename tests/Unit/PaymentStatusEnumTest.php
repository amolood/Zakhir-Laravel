<?php

namespace Zakhir\LaravelZakhir\Tests\Unit;

use Zakhir\LaravelZakhir\Enums\PaymentStatus;
use Zakhir\LaravelZakhir\Tests\TestCase;

class PaymentStatusEnumTest extends TestCase
{
    public function test_from_string_handles_uppercase(): void
    {
        $this->assertSame(PaymentStatus::Completed, PaymentStatus::fromString('COMPLETED'));
    }

    public function test_from_string_handles_lowercase(): void
    {
        $this->assertSame(PaymentStatus::Pending, PaymentStatus::fromString('pending'));
    }

    public function test_from_string_returns_unknown_for_invalid(): void
    {
        $this->assertSame(PaymentStatus::Unknown, PaymentStatus::fromString('GARBAGE'));
    }

    public function test_completed_is_terminal(): void
    {
        $this->assertTrue(PaymentStatus::Completed->isTerminal());
    }

    public function test_rejected_is_terminal(): void
    {
        $this->assertTrue(PaymentStatus::Rejected->isTerminal());
    }

    public function test_pending_is_not_terminal(): void
    {
        $this->assertFalse(PaymentStatus::Pending->isTerminal());
    }
}
