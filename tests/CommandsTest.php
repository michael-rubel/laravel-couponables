<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Tests;

use Carbon\Carbon;
use MichaelRubel\Couponables\Tests\Stubs\Models\User;

class CommandsTest extends TestCase
{
    /**
     * @var void
     */
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2022-06-25 10:00:00');
    }

    /** @test */
    public function testCanSeedCouponUsingArtisanCommand()
    {
        $this->artisan('make:coupon', [
            'code'            => 'my-test-coupon',
            '--value'         => 50,
            '--type'          => 'percentage',
            '--limit'         => 3,
            '--quantity'      => 10,
            '--expires_at'    => '2022-06-25 10:00:00',
            '--redeemer_type' => User::class,
            '--redeemer_id'   => 1,
            '--data'          => 'json',
        ])->expectsOutput('The coupon was added to the database successfully!');

        $this->assertDatabaseHas('coupons', [
            'id'            => 1,
            'code'          => 'my-test-coupon',
            'value'         => 50,
            'type'          => 'percentage',
            'limit'         => 3,
            'quantity'      => 10,
            'expires_at'    => '2022-06-25 10:00:00',
            'redeemer_type' => User::class,
            'redeemer_id'   => 1,
            'data'          => json_encode('json'),
        ]);
    }
}
