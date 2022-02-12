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
    }

    /** @test */
    public function testCanOverrideMethodsThroughContainer()
    {
        Coupon::create([
            'code' => 'bound-coupon',
            'limit' => 1,
        ]);

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
    public function testCanOverrideColumnsThroughContainer()
    {
        $this->expectException(InvalidCouponException::class);

        Coupon::create([
            'code' => 'column-coupon',
            'limit' => 1,
        ]);

        bind(CouponContract::class)->method(
            'getCodeColumn',
            fn ($model) => $model->getCodeColumn() . 's'
        );

        $this->user->redeemCoupon('column-coupon');
    }
}
