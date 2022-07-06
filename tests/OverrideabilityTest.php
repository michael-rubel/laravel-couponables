<?php

namespace MichaelRubel\Couponables\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use MichaelRubel\Couponables\CouponableServiceProvider;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Tests\Stubs\Models\FakeCoupon;
use MichaelRubel\Couponables\Tests\Stubs\Models\FakeCouponable;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class OverrideabilityTest extends TestCase
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
        $now         = now();

        $this->user->coupons()->syncWithPivotValues($coupon->id, [
            $redeemed_at => $now,
        ], false);

        $fakePivotModel = FakeCouponable::where($redeemed_at, $now)->first();

        $this->assertStringContainsString($fakePivotModel->{$redeemed_at}, $now->toDateTimeString());
    }
}
