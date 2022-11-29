<?php

namespace MichaelRubel\Couponables\Tests;

use MichaelRubel\Couponables\Exceptions\InvalidCouponTypeException;
use MichaelRubel\Couponables\Models\Coupon;

class CalculationsTest extends TestCase
{
    /** @test */
    public function testCanCalcUsingSubtractionStrategy()
    {
        $coupon = Coupon::create([
            'code'  => 'test-code',
            'type'  => 'subtract',
            'value' => '250', // <-- Amount to subtract.
        ]);

        $newPrice = $coupon->calcByType(ofValue: 500);
        // 500.00 - 250.00 = 250.00

        $this->assertSame(250.00, $newPrice);
    }

    /** @test */
    public function testCanCalcUsingPercentStrategy()
    {
        $coupon = Coupon::create([
            'code'  => 'test-code',
            'type'  => 'percent',
            'value' => '50', // <-- %50.
        ]);

        $newPrice = $coupon->calcByType(ofValue: 25002.30);
        // 25002.30 = Item cost.

        $this->assertSame(12501.15, $newPrice);
    }

    /** @test */
    public function testCanCalcUsingFixedStrategy()
    {
        $coupon = Coupon::create([
            'code'  => 'test-code',
            'type'  => 'fixed',
            'value' => '25000', // <-- Fixed price for the item.
        ]);

        $newPrice = $coupon->calcByType(ofValue: 500000);
        // We're ignoring the item cost in this case ^.

        $this->assertSame(25000.00, $newPrice);
    }

    /** @test */
    public function testReturnsSameValueIfTypeNotFound()
    {
        $this->expectException(InvalidCouponTypeException::class);

        $coupon = Coupon::create([
            'code'  => 'test-code',
            'type'  => 'not-found',
            'value' => '25',
        ]);

        $coupon->calcByType(ofValue: 500000);
    }
}
