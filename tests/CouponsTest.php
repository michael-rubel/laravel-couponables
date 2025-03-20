<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use MichaelRubel\Couponables\Events\CouponDisabled;
use MichaelRubel\Couponables\Events\CouponExpired;
use MichaelRubel\Couponables\Events\CouponIsOverLimit;
use MichaelRubel\Couponables\Events\CouponIsOverQuantity;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Events\CouponVerified;
use MichaelRubel\Couponables\Events\NotAllowedToRedeem;
use MichaelRubel\Couponables\Exceptions\CouponDisabledException;
use MichaelRubel\Couponables\Exceptions\CouponException;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Coupon;
use MichaelRubel\Couponables\Models\Couponable;
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
    protected function setUp(): void
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
    public function test_can_redeem_the_coupon()
    {
        Coupon::factory()->create();

        $redeemed = $this->user->redeemCoupon('test-code');

        $this->assertInstanceOf(Coupon::class, $redeemed);
        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function test_can_use_coupon_by_passed_model_in_context_of_another()
    {
        Coupon::factory()->create();

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
    public function test_can_use_with_redeemed_method_chained()
    {
        Coupon::factory()->create();

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
    public function test_is_that_coupon_code_already_applied()
    {
        Coupon::factory()->create();

        Coupon::factory()->create([
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
    public function test_returns_false_if_coupon_is_not_applied()
    {
        Coupon::factory()->create();

        Coupon::factory()->create([
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
    public function test_can_verify_coupon_when_specific_model_assigned()
    {
        Coupon::factory()->create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
        ]);

        $redeemer = User::find($this->user->id);

        $redeemed = $redeemer->verifyCoupon('redeemer-coupon');

        $this->assertInstanceOf(Coupon::class, $redeemed);

        Event::assertDispatched(CouponVerified::class);
    }

    /** @test */
    public function test_verify_coupon_throws_exception_when_specific_model_assigned_and_limit_is_set()
    {
        $this->expectException(NotAllowedToRedeemException::class);

        Coupon::factory()->create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
            'limit'         => 1,
            'quantity'      => 1,
        ]);

        $redeemer = User::create([
            'name'     => 'Tester2',
            'email'    => 'test2@example.com',
            'password' => Hash::make('pass2'),
        ]);

        $redeemer->verifyCoupon('redeemer-coupon');
    }

    /** @test */
    public function test_can_redeem_coupon_when_specific_model_assigned()
    {
        Coupon::factory()->create([
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
    public function test_cannot_redeem_coupon_assigned_to_another_model()
    {
        $this->expectException(NotAllowedToRedeemException::class);

        Coupon::factory()->create([
            'code'          => 'alien-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => 100,
        ]);

        $this->user->redeemCoupon('alien-coupon');
    }

    /** @test */
    public function test_event_fired_when_coupon_assigned_to_another_model()
    {
        Coupon::factory()->create([
            'code'          => 'alien-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => 100,
        ]);

        try {
            $this->user->redeemCoupon('alien-coupon');
        } catch (NotAllowedToRedeemException) {
        }

        Event::assertDispatched(NotAllowedToRedeem::class);
    }

    /** @test */
    public function test_can_redeem_coupon_by_the_model_without_morph_id()
    {
        Coupon::factory()->create([
            'code'          => 'same-model-coupon',
            'redeemer_type' => $this->user::class,
        ]);

        $coupon = $this->user->redeemCoupon('same-model-coupon');

        $this->assertInstanceOf(Coupon::class, $coupon);

        Event::assertDispatched(CouponVerified::class);
        Event::assertDispatched(CouponRedeemed::class);
    }

    /** @test */
    public function test_cannot_redeem_coupon_by_the_another_model_without_morph_id()
    {
        $this->expectException(NotAllowedToRedeemException::class);

        Coupon::factory()->create([
            'code'          => 'another-model-coupon',
            'redeemer_type' => FakeCoupon::class,
        ]);

        $this->user->redeemCoupon('another-model-coupon');

        Event::assertDispatched(NotAllowedToRedeem::class);
    }

    /** @test */
    public function test_cannot_verify_coupon_when_wrong_model_assigned()
    {
        Coupon::factory()->create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => User::class,
            'redeemer_id'   => 7,
        ]);

        $this->expectException(NotAllowedToRedeemException::class);

        $this->user->verifyCoupon('redeemer-coupon');
    }

    /** @test */
    public function test_coupon_is_disabled()
    {
        $this->expectException(CouponDisabledException::class);

        Coupon::factory()->create([
            'code'       => 'disabled-coupon',
            'is_enabled' => false,
        ]);

        $this->user->redeemCoupon('disabled-coupon');
    }

    /** @test */
    public function test_event_fired_when_coupon_is_disabled()
    {
        Coupon::factory()->create([
            'code'       => 'disabled-coupon',
            'is_enabled' => false,
        ]);

        try {
            $this->user->redeemCoupon('disabled-coupon');
        } catch (CouponDisabledException $e) {
            $this->assertSame('The coupon is disabled.', $e->getMessage());
        }

        Event::assertDispatched(CouponDisabled::class);
    }

    /** @test */
    public function test_coupon_is_expired()
    {
        $this->expectException(CouponExpiredException::class);

        Coupon::factory()->create([
            'code'       => 'expired-coupon',
            'expires_at' => now()->subMonth(),
        ]);

        $this->user->redeemCoupon('expired-coupon');

        Event::assertDispatched(CouponExpired::class);
    }

    /** @test */
    public function test_event_fired_when_coupon_is_expired()
    {
        Coupon::factory()->create([
            'code'       => 'expired-coupon',
            'expires_at' => now()->subMonth(),
        ]);

        try {
            $this->user->redeemCoupon('expired-coupon');
        } catch (CouponExpiredException) {
        }

        Event::assertDispatched(CouponExpired::class);
    }

    /** @test */
    public function test_coupon_is_disposable()
    {
        $this->expectException(OverLimitException::class);

        Coupon::factory()->create([
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
    public function test_event_fired_when_coupon_is_over_limit()
    {
        Coupon::factory()->create([
            'code'  => 'disposable-coupon',
            'limit' => 1,
        ]);

        $this->user->redeemCoupon('disposable-coupon');

        try {
            $this->user->redeemCoupon('disposable-coupon');
        } catch (OverLimitException) {
        }

        Event::assertDispatched(CouponIsOverLimit::class);
    }

    /** @test */
    public function test_coupons_are_limited()
    {
        $this->expectException(OverLimitException::class);

        Coupon::factory()->create([
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
    public function test_is_over_limit_for_model()
    {
        Coupon::factory()->create([
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
    public function test_is_over_limit_when_no_coupons_available()
    {
        $this->assertFalse($this->user->isCouponOverLimit('limited-coupon'));
    }

    /** @test */
    public function test_coupon_is_over_quantity()
    {
        $this->expectException(OverQuantityException::class);

        Coupon::factory()->create([
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
    public function test_event_fired_when_coupon_is_over_quantity()
    {
        Coupon::factory()->create([
            'code'     => 'quantity-coupon',
            'quantity' => 1,
        ]);

        $this->user->redeemCoupon('quantity-coupon');

        try {
            $this->user->redeemCoupon('quantity-coupon');
        } catch (OverQuantityException) {
        }

        Event::assertDispatched(CouponIsOverQuantity::class);
    }

    /** @test */
    public function test_can_use_data_as_collection()
    {
        $coupon = Coupon::factory()->create([
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
    public function test_can_check_is_redeemed_by_model()
    {
        Coupon::factory()->create([
            'code' => 'redeemed-by-coupon',
        ]);

        $coupon = $this->user->redeemCoupon('redeemed-by-coupon');

        $this->assertTrue($coupon->isRedeemedBy($this->user));
    }

    /** @test */
    public function test_simulates_production_usage()
    {
        // code from form request or livewire input
        $code = 'business-coupon';

        Coupon::factory()->create([
            'code'  => $code,
            'type'  => 'percentage',
            'value' => '50',
        ]);

        $this->be($this->user);

        if (! $this->user->isCouponAlreadyUsed($code)) {
            // show different validation errors
            try {
                $this->user->verifyCoupon($code);
            } catch (InvalidCouponException $e) {
                $this->assertStringContainsString('The coupon is invalid', $e->getMessage());
            } catch (CouponDisabledException $e) {
                $this->assertStringContainsString('The coupon is disabled', $e->getMessage());
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

            $newPrice = $coupon->calc(using: 150);
            $this->assertSame(75.0, $newPrice);
        }
    }

    /** @test */
    public function test_simulates_production_usage_with_generic_exception()
    {
        $this->be($this->user);

        Coupon::factory()->create([
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
    public function test_nullify_or_redeem_as_null()
    {
        $this->be($this->user);

        $null = $this->user->redeemCouponOr(null, function () {
            return null;
        });
        $this->assertNull($null);

        $non_existing = $this->user->redeemCouponOr('non-existing', function () {
            return null;
        });
        $this->assertNull($non_existing);
    }

    /** @test */
    public function test_throws_exception_when_no_closure_passed_to_redeem_coupon_or()
    {
        $this->expectException(InvalidCouponException::class);

        $this->user->redeemCouponOr(null);
    }

    /** @test */
    public function test_nullify_or_redeem_as_existing_coupon()
    {
        $this->be($this->user);

        Coupon::factory()->create([
            'code' => 'existing-coupon',
        ]);

        $coupon = $this->user->redeemCouponOr('existing-coupon');

        $this->assertInstanceOf(CouponContract::class, $coupon);

        $this->assertDatabaseHas('couponables', [
            'couponable_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_nullify_or_verify_as_null()
    {
        $this->be($this->user);

        $null = $this->user->verifyCouponOr(null, function () {
            return null;
        });
        $this->assertNull($null);

        $non_existing = $this->user->verifyCouponOr('non-existing', function () {
            return null;
        });
        $this->assertNull($non_existing);

        $isCouponInvalid = $this->user->verifyCouponOr(null, function ($code, $exception) {
            return $exception instanceof InvalidCouponException;
        });
        $this->assertTrue($isCouponInvalid);
    }

    /** @test */
    public function test_throws_exception_when_no_closure_passed_to_verify_coupon_or()
    {
        $this->expectException(InvalidCouponException::class);

        $this->user->verifyCouponOr(null);
    }

    /** @test */
    public function test_nullify_or_verify_as_existing_coupon()
    {
        $this->be($this->user);

        Coupon::factory()->create([
            'code' => 'existing-coupon',
        ]);

        $coupon = $this->user->verifyCouponOr('existing-coupon');

        $this->assertInstanceOf(CouponContract::class, $coupon);
    }

    /** @test */
    public function test_can_verify_or_return()
    {
        $coupon = $this->user->verifyCouponOr('non-existing-coupon', function () {
            return false;
        });

        $this->assertFalse($coupon);
    }

    /** @test */
    public function test_can_redeem_or_return()
    {
        $coupon = $this->user->redeemCouponOr('non-existing-coupon', function () {
            return null;
        });

        $this->assertNull($coupon);
    }

    /** @test */
    public function test_failed_to_redeem_case_is_handled()
    {
        $this->expectException(\Exception::class);

        Coupon::factory()->create();

        $this->user->redeemCoupon('non-existing-code');
    }

    /** @test */
    public function test_can_generate_coupons()
    {
        $service = app(CouponServiceContract::class);
        $coupons = $service->generateCoupons();

        $this->assertInstanceOf(Collection::class, $coupons);
        $this->assertDatabaseCount('coupons', 5);

        Coupon::all()->each(function ($coupon) {
            $this->assertTrue(strlen($coupon->code) === 7);
        });
    }

    /** @test */
    public function test_can_generate_coupon_for_specified_redeemer()
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
    public function test_can_retrieve_redeemer_using_eager_loading()
    {
        Coupon::factory()->create([
            'code'          => 'redeemer-coupon',
            'redeemer_type' => $this->user::class,
            'redeemer_id'   => $this->user->id,
        ]);

        Coupon::factory()->create([
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
    public function test_can_create_model_instance_manually()
    {
        $coupon = new Coupon(['code' => 'test']);

        $this->assertSame('test', $coupon->code);
    }

    /** @test */
    public function test_can_create_morph_model_instance_manually()
    {
        $coupon = new Couponable(['redeemed_at' => '2022-11-28 09:10:45']);

        $this->assertEquals('2022-11-28 09:10:45', $coupon->redeemed_at);
    }

    /** @test */
    public function test_can_get_couponables_from_coupon_model()
    {
        $coupon = Coupon::factory()->create(['code' => 'test']);

        $redeemed = $this->user->redeemCoupon('test');

        $this->assertInstanceOf(Couponable::class, $coupon->couponables()->first());
        $this->assertEquals($redeemed->couponables()->first(), $coupon->couponables()->first());
    }

    /** @test */
    public function test_can_get_coupon_from_pivot_model()
    {
        Coupon::factory()->create(['code' => 'test']);

        $coupon = $this->user->redeemCoupon('test');

        $pivot = $coupon->couponables()->first();

        $this->assertEquals($coupon, $pivot->coupon()->first());
    }

    /** @test */
    public function test_pivot_attributes_are_correct()
    {
        $this->assertSame([
            0 => 'redeemed_type',
            1 => 'redeemed_id',
            2 => 'redeemed_at',
            3 => 'created_at',
            4 => 'updated_at',
        ], $this->user->coupons()->getPivotColumns());
    }

    /** @test */
    public function test_can_perform_checks_on_coupon_without_model()
    {
        Coupon::factory()->create();

        $service = app(CouponServiceContract::class);

        $coupon = $service->getCoupon('test-code');

        $couponAfterChecks = $service->performBasicChecksOn($coupon);

        $this->assertSame($coupon, $couponAfterChecks);
    }

    /** @test */
    public function test_can_perform_checks_on_coupon_without_model_and_coupon()
    {
        $service = app(CouponServiceContract::class);

        $coupon = $service->getCoupon('non-existing-code');

        try {
            $service->performBasicChecksOn($coupon);
        } catch (CouponException $exception) {
            $this->assertInstanceOf(InvalidCouponException::class, $exception);
        }

        try {
            $service->performRedeemerChecksOn($coupon, new User);
        } catch (CouponException $exception) {
            $this->assertInstanceOf(InvalidCouponException::class, $exception);
        }
    }
}
