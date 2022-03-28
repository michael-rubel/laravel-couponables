<?php

namespace MichaelRubel\Couponables\Tests;

use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\Couponables\Services\CouponService;

class MacroabilityTest extends TestCase
{
    /**
     * @var void
     */
    public function setUp(): void
    {
        parent::setUp();

        Coupon::create([
            'code' => 'coupon',
            'type' => 'macro',
        ]);
    }

    /** @test */
    public function testCanMacroIntoTheService()
    {
        CouponService::macro('getCouponUsing', function (string $column, string $value) {
            return $this->model
                ->where($this->model->{'get' . ucfirst($column) . 'Column'}(), $value)
                ->first();
        });

        $coupon = call(CouponServiceContract::class)
            ->getCouponUsing('type', 'macro');

        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertStringContainsString('macro', $coupon->{$coupon->getTypeColumn()});
    }
}
