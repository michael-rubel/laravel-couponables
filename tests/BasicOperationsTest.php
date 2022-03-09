<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class BasicOperationsTest extends TestCase
{
    /**
     * @var void
     */
    public function setUp(): void
    {
        parent::setUp();

        User::create([
            'name'     => 'Tester',
            'email'    => 'test@example.com',
            'password' => Hash::make('pass'),
        ]);
    }

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

    /** @test */
    public function testTypeColumnIsAccessible()
    {
        $coupon = Coupon::create([
            'code' => 'coupon',
            'type' => 'percent',
        ]);

        $this->assertStringContainsString(
            'percent',
            $coupon->{$coupon->getTypeColumn()}
        );
    }

    /** @test */
    public function testValueColumnIsAccessible()
    {
        $coupon = Coupon::create([
            'code'  => 'coupon',
            'value' => '1000',
        ]);

        $this->assertStringContainsString(
            '1000',
            $coupon->{$coupon->getValueColumn()}
        );
    }
}
