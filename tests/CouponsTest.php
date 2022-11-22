<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MichaelRubel\Couponables\Events\CouponExpired;
use MichaelRubel\Couponables\Events\CouponIsOverLimit;
use MichaelRubel\Couponables\Events\CouponIsOverQuantity;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Events\CouponVerified;
use MichaelRubel\Couponables\Events\FailedToRedeemCoupon;
use MichaelRubel\Couponables\Events\NotAllowedToRedeem;
use MichaelRubel\Couponables\Exceptions\CouponException;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\Couponables\Tests\Stubs\Models\Course;
use MichaelRubel\Couponables\Tests\Stubs\Models\FakeCoupon;
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

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testCanUseCouponByPassedModelInContextOfAnother()
    {
        Coupon::create(['code' => 'test-code']);

        $course = new Course(['id' => 1]);

        $redeemed = $course->redeemBy($this->user, 'test-code');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_type' => $this->user::class,
            'couponable_id'   => $this->user->id,
            'redeemed_type'   => Course::class,
            'redeemed_id'     => 1,
        ]);

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testCanUseWithRedeemedMethodChained()
    {
        Coupon::create(['code' => 'test-code']);

        $course = new Course(['id' => 1]);

        $redeemed = $this->user
            ->redeemCoupon('test-code')
            ->for($course);

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_type' => $this->user::class,
            'couponable_id'   => $this->user->id,
            'redeemed_type'   => Course::class,
            'redeemed_id'     => 1,
        ]);

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testIsThatCouponCodeAlreadyApplied()
    {
        Coupon::create([
            'code' => 'test-code',
        ]);

        Coupon::create([
            'code' => 'applied-code',
        ]);

        $redeemed = $this->user->redeemCoupon('applied-code');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);

        $this->assertTrue($this->user->isCouponAlreadyUsed('applied-code'));

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testReturnsFalseIfCouponIsNotApplied()
    {
        Coupon::create([
            'code' => 'test-code',
        ]);

        Coupon::create([
            'code' => 'applied-code',
        ]);

        $redeemed = $this->user->redeemCoupon('test-code');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);

        $this->assertFalse($this->user->isCouponAlreadyUsed('applied-code'));

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testCanRedeemCouponWhenSpecificModelAssigned()
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

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
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

        Event::assertDispatched(NotAllowedToRedeem::class);
    }

    /** @test */
    public function testCanRedeemCouponByTheModelWithoutMorphId()
    {
        Coupon::create([
            'code'          => 'same-model-coupon',
            'redeemer_type' => $this->user::class,
        ]);

        $coupon = $this->user->redeemCoupon('same-model-coupon');

        $this->assertInstanceOf(Coupon::class, $coupon);

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function testCannotRedeemCouponByTheAnotherModelWithoutMorphId()
    {
        $this->expectException(NotAllowedToRedeemException::class);

        Coupon::create([
            'code'          => 'another-model-coupon',
            'redeemer_type' => FakeCoupon::class,
        ]);

        $this->user->redeemCoupon('another-model-coupon');

        Event::assertDispatched(NotAllowedToRedeem::class);
    }

    /** @test */
    public function testCannotVerifyCouponWhenWrongModelAssigned()
    {
        Coupon::create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => User::class,
            'redeemer_id'   => 7,
        ]);

        $this->expectException(NotAllowedToRedeemException::class);

        $this->user->verifyCoupon('redeemer-coupon');
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

        Event::assertDispatched(CouponExpired::class);
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

        Event::assertDispatched(CouponIsOverLimit::class);
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
            5,
            fn () => $this->user->redeemCoupon('limited-coupon')
        );

        Event::assertDispatched(CouponIsOverLimit::class);
    }

    /** @test */
    public function testIsOverLimitForModel()
    {
        Coupon::create([
            'code'  => 'limited-coupon',
            'limit' => 3,
        ]);

        Collection::times(
            3,
            fn () => $this->user->redeemCoupon('limited-coupon')
        );

        $this->assertTrue(
            $this->user->isCouponOverLimit('limited-coupon')
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

        Event::assertDispatched(CouponIsOverQuantity::class);
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
    public function testCanCheckIsRedeemedByModel()
    {
        Coupon::create([
            'code' => 'redeemed-by-coupon',
        ]);

        $coupon = $this->user->redeemCoupon('redeemed-by-coupon');

        $this->assertTrue($coupon->isRedeemedBy($this->user));
    }

    /** @test */
    public function testSimulatesProductionUsage()
    {
        // code from form request or livewire input
        $code = 'business-coupon';

        Coupon::create([
            'code' => $code,
            'data' => [
                'run-jobs' => [
                    'queue-job-name' => true,
                ],
            ],
        ]);

        $this->be($this->user);

        if (! $this->user->isCouponAlreadyUsed($code)) {
            // show different validation errors
            try {
                $coupon = $this->user->verifyCoupon($code);

                // apply some action if coupon isn't fail
                // for example get the data from the coupon to identify
                // which queue job or action to run after coupon is redeemed.
                if ($coupon->isRedeemedBy($this->user)) {
                    $this->assertArrayHasKey('queue-job-name', $coupon->data->get('run-jobs'));
                }
            } catch (InvalidCouponException $e) {
                $this->assertStringContainsString('The coupon is invalid', $e->getMessage());
            } catch (CouponExpiredException $e) {
                $this->assertStringContainsString('The coupon is expired', $e->getMessage());
            } catch (OverQuantityException $e) {
                $this->assertStringContainsString('The coupon is exhausted', $e->getMessage());
            } catch (OverLimitException $e) {
                $this->assertStringContainsString('Coupon usage limit has been reached', $e->getMessage());
            } catch (NotAllowedToRedeemException $e) {
                $this->assertStringContainsString('You cannot use this coupon', $e->getMessage());
            }

            // If all set.
            $coupon = $this->user->redeemCoupon($code);

            $this->assertSame('business-coupon', $coupon->code);
        }
    }

    /** @test */
    public function testSimulatesProductionUsageWithGenericException()
    {
        $this->be($this->user);

        Coupon::create([
            'code' => 'correct-coupon',
        ]);

        try {
            try {
                $this->user->redeemCoupon('wrong-coupon');
            } catch (CouponException $e) {
                throw ValidationException::withMessages([
                    'coupon' => $e->getMessage(),
                ]);
            }
        } catch (ValidationException $e) {
            $this->assertSame([0 => 'The coupon is invalid.'], $e->errors()['coupon']);
        }
    }

    /** @test */
    public function testNullifyOrRedeemAsNull()
    {
        $this->be($this->user);

        $null = $this->user->redeemCouponOr(null);
        $this->assertNull($null);

        $non_existing = $this->user->redeemCouponOr('non-existing');
        $this->assertNull($non_existing);
    }

    /** @test */
    public function testNullifyOrRedeemAsExistingCoupon()
    {
        $this->be($this->user);

        Coupon::create([
            'code' => 'existing-coupon',
        ]);

        $coupon = $this->user->redeemCouponOr('existing-coupon');

        $this->assertInstanceOf(CouponContract::class, $coupon);

        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function testNullifyOrVerifyAsNull()
    {
        $this->be($this->user);

        $null = $this->user->verifyCouponOr(null);
        $this->assertNull($null);

        $non_existing = $this->user->verifyCouponOr('non-existing');
        $this->assertNull($non_existing);
    }

    /** @test */
    public function testNullifyOrVerifyAsExistingCoupon()
    {
        $this->be($this->user);

        Coupon::create([
            'code' => 'existing-coupon',
        ]);

        $coupon = $this->user->verifyCouponOr('existing-coupon');

        $this->assertInstanceOf(CouponContract::class, $coupon);
    }

    /** @test */
    public function testCanVerifyOrReturn()
    {
        $coupon = $this->user->verifyCouponOr('non-existing-coupon', function () {
            return false;
        });

        $this->assertFalse($coupon);
    }

    /** @test */
    public function testCanRedeemOrReturn()
    {
        $coupon = $this->user->redeemCouponOr('non-existing-coupon', function () {
            return null;
        });

        $this->assertNull($coupon);
    }

    /** @test */
    public function testFailedToRedeemCaseIsHandled()
    {
        $this->expectException(\Exception::class);

        Coupon::create([
            'code' => 'test-code',
        ]);

        bind(User::class)
            ->method()
            ->coupons(
                fn () => throw new \Exception('test exception')
            );

        $this->user->redeemCoupon('test-code');

        Event::assertDispatched(FailedToRedeemCoupon::class);
    }

    /** @test */
    public function testCanGenerateCoupons()
    {
        $service = app(CouponServiceContract::class);
        $coupons = $service->generateCoupons(10);

        $this->assertInstanceOf(Collection::class, $coupons);
        $this->assertDatabaseCount('coupons', 10);
    }

    /** @test */
    public function testCanGenerateCouponForSpecifiedRedeemer()
    {
        $service = app(CouponServiceContract::class);

        $service->generateCouponFor($this->user, 'test-code', [
            'value' => 100,
        ]);

        $this->assertDatabaseHas('coupons', [
            'code'          => 'test-code',
            'value'         => 100,
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
        ]);
    }

    /** @test */
    public function testCanRetrieveRedeemerUsingEagerLoading()
    {
        Coupon::create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
        ]);

        Coupon::create([
            'code'          => 'redeemer-coupon2',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
        ]);

        $coupons = Coupon::with('redeemer')->get();

        $coupons->each(function (Coupon $coupon) {
            $this->assertInstanceOf($this->user::class, $coupon->redeemer);
            $this->assertSame($this->user->id, $coupon->redeemer->id);
        });
    }

    /** @test */
    public function testCanCreateModelInstanceManually()
    {
        $coupon = new Coupon(['code' => 'test']);

        $this->assertSame('test', $coupon->code);
    }
}
