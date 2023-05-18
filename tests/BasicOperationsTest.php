<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Models\Couponable;
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
        Coupon::factory()->create();

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
    public function testIsDisabled()
    {
        $coupon = Coupon::factory()->create([
            'code'       => 'disabled-coupon',
            'is_enabled' => false,
        ]);

        $this->assertTrue($coupon->isDisabled());
    }

    /** @test */
    public function testIsEnabled()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'not-disabled-coupon2',
            'is_enabled' => true,
        ]);

        $this->assertTrue($coupon->isEnabled());
    }

    /** @test */
    public function testIsDisposable()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'first',
            'limit' => 0,
        ]);
        $this->assertFalse($coupon->isDisposable());

        $coupon = Coupon::factory()->create([
            'code' => 'second',
            'limit' => 1,
        ]);
        $this->assertTrue($coupon->isDisposable());

        $coupon = Coupon::factory()->create([
            'code' => 'third',
            'limit' => 2,
        ]);
        $this->assertFalse($coupon->isDisposable());
    }

    /** @test */
    public function testIsEnabledStillTrueWhenNotSet()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'not-disabled-coupon2',
        ]);

        $this->assertTrue($coupon->isEnabled());
    }

    /** @test */
    public function testIsExpired()
    {
        $coupon = Coupon::factory()->create([
            'code'       => 'expired-coupon',
            'expires_at' => now()->subMonth(),
        ]);

        $this->assertTrue($coupon->isExpired());
    }

    /** @test */
    public function testIsNotExpired()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'not-expired-coupon',
        ]);
        $this->assertTrue($coupon->isNotExpired());

        $coupon = Coupon::factory()->create([
            'code' => 'not-expired-coupon2',
            'expires_at' => now()->addDay(),
        ]);
        $this->assertTrue($coupon->isNotExpired());
    }

    /** @test */
    public function testTypeColumnIsAccessible()
    {
        $coupon = Coupon::factory()->create([
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
        $coupon = Coupon::factory()->create([
            'code'  => 'coupon',
            'value' => '1000',
        ]);

        $this->assertStringContainsString(
            '1000',
            $coupon->{$coupon->getValueColumn()}
        );
    }

    /** @test */
    public function testCanGetCouponFromCouponable()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'coupon',
        ]);

        $couponable = Couponable::create([
            'coupon_id'       => $coupon->id,
            'couponable_type' => $coupon::class,
            'couponable_id'   => $coupon->id,
            'redeemed_at'     => now(),
        ]);

        $this->assertInstanceOf(Coupon::class, $couponable->coupon);
    }
}
