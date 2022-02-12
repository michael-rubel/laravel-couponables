<?php

namespace MichaelRubel\Couponables\Tests;

use MichaelRubel\Couponables\Models\Coupon;

class BasicOperationsTest extends TestCase
{
    /** @test */
    public function testCouponIsGenerated()
    {
        Coupon::create([
            'code' => 'test-code',
        ]);

        $this->assertDatabaseHas(
            'coupons',
            ['code' => 'test-code']
        );
    }

    /** @test */
    public function testUserIsCreated()
    {
        $this->assertDatabaseHas(
            'users',
            ['email' => 'test@example.com']
        );
    }

    /** @test */
    public function testIsNotExpired()
    {
        $coupon = Coupon::create([
            'code' => 'not-expired-coupon',
        ]);

        $this->assertTrue($coupon->isNotExpired());
    }

    /** @test */
    public function testIsExpired()
    {
        $coupon = Coupon::create([
            'code'       => 'expired-coupon',
            'expires_at' => now()->subMonth(),
        ]);

        $this->assertTrue($coupon->isExpired());
    }
}
