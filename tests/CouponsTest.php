<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class CouponsTest extends TestCase
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
}
