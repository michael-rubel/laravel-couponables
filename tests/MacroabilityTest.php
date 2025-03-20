<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests;

use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\Couponables\Services\CouponService;

class MacroabilityTest extends TestCase
{
    /**
     * @var void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Coupon::factory()->create([
            'code' => 'coupon',
            'type' => 'macro',
        ]);
    }

    /** @test */
    public function test_can_macro_into_the_service()
    {
        CouponService::macro('getCouponUsing', function (string $column, string $value) {
            return $this->model
                ->where($this->model->{'get' . ucfirst($column) . 'Column'}(), $value)
                ->first();
        });

        $coupon = app(CouponServiceContract::class)
            ->getCouponUsing('type', 'macro');

        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertStringContainsString('macro', $coupon->{$coupon->getTypeColumn()});
    }
}
