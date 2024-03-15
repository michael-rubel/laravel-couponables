<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests;

use MichaelRubel\Couponables\Exceptions\InvalidCouponTypeException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponValueException;
use MichaelRubel\Couponables\Models\Coupon;

class CalculationsTest extends TestCase
{
    /** @test */
    public function testCanCalcUsingSubtractionStrategy()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'subtraction',
            'value' => '250', // <-- Amount to subtract.
        ]);

        $newPrice = $coupon->calc(using: 500);
        // 500.00 - 250.00 = 250.00

        $this->assertSame(250.00, $newPrice);
    }

    /** @test */
    public function testCanCalcUsingPercentStrategy()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'percentage',
            'value' => '10', // <-- %10.
        ]);

        $newPrice = $coupon->calc(using: 300); // 300 = Item cost.

        $this->assertSame(270.00, $newPrice); // 300 - %10 = 270 left.
    }

    /** @test */
    public function testCanCalcUsingFixedStrategy()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'fixed',
            'value' => '25000', // <-- Fixed price for the item.
        ]);

        $newPrice = $coupon->calc(using: 500000);
        // We're ignoring the item cost in this case ^.

        $this->assertSame(25000.00, $newPrice);
    }

    /** @test */
    public function testReturnsSameValueIfTypeNotFound()
    {
        $this->expectException(InvalidCouponTypeException::class);

        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'not-found',
            'value' => '25',
        ]);

        $coupon->calc(using: 500000);
    }

    /** @test */
    public function testValueCannotBeZero()
    {
        $this->expectException(InvalidCouponValueException::class);

        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'percentage',
            'value' => '0',
        ]);

        $coupon->calc(using: 1000);
    }

    /** @test */
    public function testValueCannotBeLessThanZero()
    {
        $this->expectException(InvalidCouponValueException::class);

        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'percentage',
            'value' => '-1000',
        ]);

        $coupon->calc(using: 1000);
    }

    /** @test */
    public function testDifferentPercentageValues()
    {
        // Base value: 200
        $iterations = [
            5   => 190.00,
            10  => 180.00,
            15  => 170.00,
            20  => 160.00,
            25  => 150.00,
            30  => 140.00,
            35  => 130.00,
            40  => 120.00,
            45  => 110.00,
            50  => 100.00,
            55  => 90.00,
            60  => 80.00,
            65  => 70.00,
            70  => 60.00,
            75  => 50.00,
            80  => 40.00,
            85  => 30.00,
            90  => 20.00,
            95  => 10.00,
            100 => 0.00,
        ];

        collect($iterations)->each(function ($result, $discount) {
            $coupon = Coupon::factory()->create([
                'code'  => (string) ($discount + $result),
                'type'  => 'percentage',
                'value' => $discount,
            ]);

            $newPrice = $coupon->calc(using: 200);

            $this->assertSame($result, $newPrice);
        });
    }

    /** @test */
    public function testCalcMaximumAllowedValue()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'percentage',
            'value' => '122',
        ]);

        config()->offsetUnset('couponables.max');
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(0.00, $newPrice);

        config()->set('couponables.max', 0);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(0.00, $newPrice);

        config()->set('couponables.max', 1);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(1.00, $newPrice);

        config()->set('couponables.max', -1);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(-1.00, $newPrice);
    }

    /** @test */
    public function testCalcMethodRoundsResult()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'percentage',
            'value' => '0.7123123',
        ]);

        config()->offsetUnset('couponables.round');
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame('198.58', (string) $newPrice);

        config()->set('couponables.round', 1);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame('198.6', (string) $newPrice);

        config()->set('couponables.round', 2);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(198.58, $newPrice);

        config()->set('couponables.round', 3);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(198.575, $newPrice);

        config()->set('couponables.round', 4);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(198.5754, $newPrice);

        config()->set('couponables.round', 5);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(198.57538, $newPrice);

        config()->set('couponables.round', 6);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(198.575375, $newPrice);

        config()->set('couponables.round', 7);
        $newPrice = $coupon->calc(using: 200);
        $this->assertSame(198.5753754, $newPrice);
    }

    /** @test */
    public function testCalcRoundMode()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'type'  => 'fixed',
            'value' => '1.5',
        ]);

        config()->set('couponables.round', 0);
        config()->offsetUnset('couponables.round_mode');
        $newPrice = $coupon->calc(using: 10);
        $this->assertSame(2.00, $newPrice);

        config()->set('couponables.round', 0);
        config()->set('couponables.round_mode', PHP_ROUND_HALF_EVEN);
        $newPrice = $coupon->calc(using: 10);
        $this->assertSame(2.00, $newPrice);

        config()->set('couponables.round', 0);
        config()->set('couponables.round_mode', PHP_ROUND_HALF_DOWN);
        $newPrice = $coupon->calc(using: 10);
        $this->assertSame(1.00, $newPrice);

        config()->set('couponables.round', 0);
        config()->set('couponables.round_mode', PHP_ROUND_HALF_ODD);
        $newPrice = $coupon->calc(using: 10);
        $this->assertSame(1.00, $newPrice);
    }

    /** @test */
    public function testCalcUsesSubtractionWhenNull()
    {
        $coupon = Coupon::factory()->create([
            'code'  => 'test-code',
            'value' => '5',
        ]);

        $this->assertNull($coupon->type);
        $newPrice = $coupon->calc(using: 15);
        $this->assertSame(10.00, $newPrice);
    }
}
