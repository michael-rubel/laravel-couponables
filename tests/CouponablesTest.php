<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\CouponableServiceProvider;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Tests\Stubs\Models\FakeCoupon;
use MichaelRubel\Couponables\Tests\Stubs\Models\FakeCouponable;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class CouponablesTest extends TestCase
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

        Event::fake();
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
    public function testCanRedeemTheCoupon()
    {
        Coupon::create([
            'code' => 'test-code',
        ]);

        $redeemed = $this->user->redeemCoupon('test-code');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);

        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testCanRedeemCouponToUseOnlyForSpecificModel()
    {
        Coupon::create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
        ]);

        $redeemed = $this->user->redeemCoupon('redeemer-coupon');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function testCannotRedeemCouponAssignedToAnotherModel()
    {
        $this->expectException(NotAllowedToRedeemException::class);

        Coupon::create([
            'code'          => 'alien-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => 100,
        ]);

        $this->user->redeemCoupon('alien-coupon');
    }

    /** @test */
    public function testCouponIsExpired()
    {
        $this->expectException(CouponExpiredException::class);

        Coupon::create([
            'code'       => 'expired-coupon',
            'expires_at' => now()->subMonth(),
        ]);

        $this->user->redeemCoupon('expired-coupon');
    }

    /** @test */
    public function testCouponIsDisposable()
    {
        $this->expectException(OverLimitException::class);

        Coupon::create([
            'code'  => 'disposable-coupon',
            'limit' => 1,
        ]);

        $redeemed = $this->user->redeemCoupon('disposable-coupon');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);

        $this->user->redeemCoupon('disposable-coupon');
    }

    /** @test */
    public function testCouponsAreLimited()
    {
        $this->expectException(OverLimitException::class);

        Coupon::create([
            'code'  => 'limited-coupon',
            'limit' => 3,
        ]);

        Collection::times(
            4,
            fn () => $this->user->redeemCoupon('limited-coupon')
        );
    }

    /** @test */
    public function testCouponIsOverQuantity()
    {
        $this->expectException(OverQuantityException::class);

        Coupon::create([
            'code'     => 'quantity-coupon',
            'quantity' => 1,
        ]);

        $redeemed = $this->user->redeemCoupon('quantity-coupon');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('coupons', [
            'code'     => 'quantity-coupon',
            'quantity' => 0,
        ]);

        $this->user->redeemCoupon('quantity-coupon');
    }

    /** @test */
    public function testCanUseDataAsCollection()
    {
        $coupon = Coupon::create([
            'code' => 'business-coupon',
            'data' => [
                'run-actions' => [
                    'queue-job' => true,
                ],
            ],
        ]);

        $this->assertInstanceOf(Collection::class, $coupon->data);

        $redeemed = $this->user->redeemCoupon('business-coupon');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('coupons', [
            'code' => 'business-coupon',
            'data' => json_encode([
                'run-actions' => [
                    'queue-job' => true,
                ],
            ]),
        ]);
    }

    /** @test */
    public function testCanOverrideModelAttributes()
    {
        config([
            'couponables.table' => 'coupons_test',
            'couponables.model' => FakeCoupon::class,
        ]);

        $coupon = FakeCoupon::create([
            'code' => 'fake-coupon',
            'data_test' => [
                'run-actions' => [
                    'queue-job' => true,
                ],
            ],
        ]);

        $this->assertInstanceOf(Collection::class, $coupon->data_test);
    }

    /** @test */
    public function testCanOverridePivotAttributes()
    {
        config([
            'couponables.pivot_table' => 'couponable_tests',
            'couponables.pivot'       => FakeCouponable::class,
        ]);

        app()->register(CouponableServiceProvider::class, true);

        $coupon = FakeCoupon::create([
            'code' => 'fake-coupon',
        ]);

        $redeemed_at = app(CouponPivotContract::class)->getRedeemedAtColumn();
        $now = now();

        $this->user->coupons()->syncWithPivotValues($coupon->id, [
            $redeemed_at => $now,
        ], false);

        $fakePivotModel = FakeCouponable::where($redeemed_at, $now)->first();

        $this->assertStringContainsString($fakePivotModel->{$redeemed_at}, $now->toDateTimeString());
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
