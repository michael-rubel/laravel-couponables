<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class BindabilityTest extends TestCase
{
    /**
     * @var User
     */
    private User $user;

    /**
     * @var void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'     => 'Tester',
            'email'    => 'test@example.com',
            'password' => Hash::make('pass'),
        ]);

        Coupon::create([
            'code'     => 'bound-coupon',
            'quantity' => 2,
            'limit'    => 3,
        ]);
    }

    /** @test */
    public function testCanOverrideMethodsThroughContainer()
    {
        bind(CouponContract::class)->method('isOverLimitFor', function ($model, $app, $parameters) {
            if (! $isOverLimit = $model->isOverLimitFor($parameters['redeemer'])) {
                $parameters['redeemer']->name = 'Modified';
                $parameters['redeemer']->save();
            }

            return $isOverLimit;
        });

        $this->user->redeemCoupon('bound-coupon');

        $this->assertStringContainsString('Modified', $this->user->fresh()->name);
    }

    /** @test */
    public function testCanOverrideCodeColumnThroughContainer()
    {
        $this->expectException(InvalidCouponException::class);

        bind(CouponContract::class)->method(
            'getCodeColumn',
            fn ($model) => $model->getCodeColumn() . '_changed_column_name'
        );

        $this->user->redeemCoupon('bound-coupon');
    }

    /** @test */
    public function testCanOverrideQuantityColumnThroughContainer()
    {
        $coupon = $this->user->redeemCoupon('bound-coupon');
        $this->assertSame(1, $coupon->quantity);

        bind(CouponContract::class)->method(
            'getQuantityColumn',
            fn ($model) => $model->getQuantityColumn() . '_changed'
        );

        $coupon = $this->user->redeemCoupon('bound-coupon');
        $this->assertSame(1, $coupon->quantity);

        bind(CouponContract::class)->method(
            'getQuantityColumn',
            fn ($model) => $model->getQuantityColumn()
        );

        $coupon = $this->user->redeemCoupon('bound-coupon');
        $this->assertSame(0, $coupon->quantity);
    }
}
