<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class BindabilityTest extends TestCase
{
    /**
     * @var User
     */
    private User $user;

    /**
     * @var Coupon
     */
    private Coupon $coupon;

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

        $this->coupon = Coupon::factory()->create([
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

    /** @test */
    public function testCanBindServiceMethods()
    {
        $service = call(CouponServiceContract::class);

        bind(CouponServiceContract::class)->method('verifyCoupon', fn () => true);
        $this->assertTrue($service->verifyCoupon($this->coupon, $this->user));

        bind(CouponServiceContract::class)->method('applyCoupon', fn () => true);
        $this->assertTrue($service->applyCoupon($this->coupon, $this->user));

        bind(CouponServiceContract::class)->method('isOverLimit', fn () => true);
        $this->assertTrue($service->isOverLimit());

        bind(CouponServiceContract::class)->method('isAllowedToRedeemBy', fn () => true);
        $this->assertTrue($service->isAllowedToRedeemBy());
    }

    /** @test */
    public function testCanBindModelMethods()
    {
        $coupon = Coupon::factory()->create([
            'code'          => 'test-bind',
            'limit'         => 1,
            'redeemer_type' => $this->user::class,
        ]);

        $this->user->redeemCoupon('test-bind');

        $coupon->update([
            'quantity'   => 0,
            'expires_at' => now()->subDay(),
        ]);

        bind(CouponContract::class)->method()->getRedeemerTypeColumn(fn () => 'redeemer_type');
        $this->assertTrue($coupon->isSameRedeemerModel($this->user));
        bind(CouponContract::class)->method()->getRedeemerTypeColumn(fn () => 'test');
        $this->assertFalse($coupon->isSameRedeemerModel($this->user));

        bind(CouponContract::class)->method()->getExpiresAtColumn(fn () => 'expires_at');
        $this->assertTrue($coupon->isExpired());
        bind(CouponContract::class)->method()->getExpiresAtColumn(fn () => 'test');
        $this->assertFalse($coupon->isExpired());

        bind(CouponContract::class)->method()->getQuantityColumn(fn () => 'quantity');
        $this->assertTrue($coupon->isOverQuantity());
        bind(CouponContract::class)->method()->getQuantityColumn(fn () => 'test');
        $this->assertFalse($coupon->isOverQuantity());

        bind(CouponContract::class)->method()->getCodeColumn(fn () => 'code');
        $this->assertTrue($coupon->isRedeemedBy($this->user));
        bind(CouponContract::class)->method()->getCodeColumn(fn () => 'test');
        $this->assertFalse($coupon->isRedeemedBy($this->user));

        bind(CouponContract::class)->method()->getLimitColumn(fn () => 'limit');
        $this->assertTrue($coupon->isDisposable());
        bind(CouponContract::class)->method()->getLimitColumn(fn () => 'test');
        $this->assertFalse($coupon->isDisposable());
    }

    /** @test */
    public function testSimulateNoIsEnabledColumnIsPresent()
    {
        bind(CouponContract::class)->method('getIsEnabledColumn', fn () => null);

        $this->assertNull(call(CouponContract::class)->getIsEnabledColumn());

        $coupon = Coupon::factory()->create(['code' => 'coupon-with-no-is-enabled-column']);

        $this->assertTrue($coupon->isEnabled());
    }
}
