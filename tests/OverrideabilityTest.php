<?php

declare(strict_types=1);

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
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'     => 'Tester',
            'email'    => 'test@example.com',
            'password' => Hash::make('pass'),
        ]);
    }

    /** @test */
    public function test_can_override_model_attributes()
    {
        config([
            'couponables.table' => 'coupons_test',
            'couponables.model' => FakeCoupon::class,
        ]);

        $coupon = FakeCoupon::factory()->create([
            'data_test' => [
                'run-actions' => [
                    'queue-job' => true,
                ],
            ],
        ]);

        $this->assertInstanceOf(Collection::class, $coupon->data_test);
    }

    /** @test */
    public function test_can_override_pivot_attributes()
    {
        config([
            'couponables.pivot_table' => 'couponable_tests',
            'couponables.pivot'       => FakeCouponable::class,
        ]);

        app()->register(CouponableServiceProvider::class, true);

        $coupon = FakeCoupon::factory()->create();

        $redeemed_at = app(CouponPivotContract::class)->getRedeemedAtColumn();
        $now         = now();

        $this->user->coupons()->syncWithPivotValues($coupon->id, [
            $redeemed_at => $now,
        ], false);

        $fakePivotModel = FakeCouponable::where($redeemed_at, $now)->first();

        $this->assertEquals($fakePivotModel->{$redeemed_at}, $now->toDateTimeString());
    }

    /** @test */
    public function test_can_override_constructor()
    {
        $coupon = FakeCoupon::factory()->create([
            'redeemer_type' => User::class,
            'redeemer_id'   => 1,
        ]);

        $secondUser = User::create([
            'name'     => 'Tester',
            'email'    => 'test@test.com',
            'password' => 'test',
            'id'       => 2,
        ]);

        $this->assertFalse(
            $coupon->isAllowedToRedeemBy($secondUser)
        );
    }
}
