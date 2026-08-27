<?php

namespace Tests\Unit\Mail;

use App\Mail\ExpiringAssetsMail;
use App\Mail\ExpiringLicenseMail;
use PHPUnit\Framework\TestCase;

class QueuedMailableSerializationTest extends TestCase
{
    public function test_expiring_asset_payload_survives_queue_serialization(): void
    {
        $restored = unserialize(serialize(new ExpiringAssetsMail(['asset-1'], 30)));

        $this->assertSame(['asset-1'], $restored->assets);
        $this->assertSame(30, $restored->threshold);
    }

    public function test_expiring_license_payload_survives_queue_serialization(): void
    {
        $restored = unserialize(serialize(new ExpiringLicenseMail(['license-1'], 14)));

        $this->assertSame(['license-1'], $restored->licenses);
        $this->assertSame(14, $restored->threshold);
    }
}
