<?php

declare(strict_types=1);

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
    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name'     => 'Tester',
            'email'    => 'test@example.com',
            'password' => Hash::make('pass'),
        ]);
    }

    /** @test */
    public function test_coupon_is_generated()
    {
        Coupon::factory()->create();

        $this->assertDatabaseHas(
            'coupons',
            ['code' => 'test-code']
        );
    }

    /** @test */
    public function test_user_is_created()
    {
        $this->assertDatabaseHas(
            'users',
            ['email' => 'test@example.com']
        );
    }

    /** @test */
    public function test_is_disabled()
    {
        $coupon = Coupon::factory()->create([
            'code'       => 'disabled-coupon',
            'is_enabled' => false,
        ]);

        $this->assertTrue($coupon->isDisabled());
    }

    /** @test */
    public function test_is_enabled()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'not-disabled-coupon2',
            'is_enabled' => true,
        ]);

        $this->assertTrue($coupon->isEnabled());
    }

    /** @test */
    public function test_is_disposable()
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
    public function test_is_enabled_still_true_when_not_set()
    {
        $coupon = Coupon::factory()->create([
            'code' => 'not-disabled-coupon2',
        ]);

        $this->assertTrue($coupon->isEnabled());
    }

    /** @test */
    public function test_is_expired()
    {
        $coupon = Coupon::factory()->create([
            'code'       => 'expired-coupon',
            'expires_at' => now()->subMonth(),
        ]);

        $this->assertTrue($coupon->isExpired());
    }

    /** @test */
    public function test_is_not_expired()
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
    public function test_type_column_is_accessible()
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
    public function test_value_column_is_accessible()
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
    public function test_can_get_coupon_from_couponable()
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
