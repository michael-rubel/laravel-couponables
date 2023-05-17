<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests\Stubs\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MichaelRubel\Couponables\Tests\Stubs\Models\FakeCoupon;

/**
 * @extends Factory<FakeCoupon>
 */
class FakeCouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<FakeCoupon>
     */
    protected $model = FakeCoupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'fake-coupon',
        ];
    }
}
