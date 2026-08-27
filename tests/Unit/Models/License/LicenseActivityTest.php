<?php

namespace Tests\Unit\Models\License;

use App\Models\License;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class LicenseActivityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-08-04 12:00:00'));
    }

    public function test_license_without_end_dates_is_active(): void
    {
        $license = new License([
            'expiration_date' => null,
            'termination_date' => null,
        ]);

        $this->assertFalse($license->isInactive());
    }

    public function test_license_expiring_today_is_inactive(): void
    {
        $license = new License([
            'expiration_date' => '2026-08-04',
            'termination_date' => null,
        ]);

        $this->assertTrue($license->isExpired());
        $this->assertTrue($license->isInactive());
    }

    public function test_license_terminated_today_is_inactive(): void
    {
        $license = new License([
            'expiration_date' => '2027-08-04',
            'termination_date' => '2026-08-04',
        ]);

        $this->assertTrue($license->isTerminated());
        $this->assertTrue($license->isInactive());
    }

    public function test_future_end_dates_keep_license_active(): void
    {
        $license = new License([
            'expiration_date' => '2026-08-05',
            'termination_date' => '2026-08-06',
        ]);

        $this->assertFalse($license->isExpired());
        $this->assertFalse($license->isTerminated());
        $this->assertFalse($license->isInactive());
    }
}
