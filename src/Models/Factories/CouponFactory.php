<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MichaelRubel\Couponables\Models\Coupon;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Coupon>
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'test-code',
        ];
    }
}
