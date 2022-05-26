<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Commands;

use Illuminate\Console\Command;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;

class MakeCouponCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:coupon
                            {code : Coupon name to verify and redeem}
                            {--value= : The `value` to perform calculations based on the coupon provided}
                            {--type= : The `type` to point out calculation strategy}
                            {--limit= : Limit how many times the coupon can be applied by the model}
                            {--quantity= : Limit how many coupons are available overall (this value will decrement)}
                            {--expires_at= : Set expiration time for the coupon}
                            {--redeemer_type= : Polymorphic model type. Can as well be morph-mapped value, i.e. `users`}
                            {--redeemer_id= : Redeemer model ID}
                            {--data= : JSON column to store any metadata you want for this particular coupon}';

    /**
     * @var string
     */
    protected $description = 'Add the coupon to database';

    /**
     * @return void
     */
    public function handle(): void
    {
        $coupon = call(CouponContract::class);

        $coupon->create([
            $coupon->getCodeColumn()         => $this->argument('code'),
            $coupon->getValueColumn()        => $this->option('value'),
            $coupon->getTypeColumn()         => $this->option('type'),
            $coupon->getLimitColumn()        => $this->option('limit'),
            $coupon->getQuantityColumn()     => $this->option('quantity'),
            $coupon->getExpiresAtColumn()    => $this->option('expires_at'),
            $coupon->getRedeemerTypeColumn() => $this->option('redeemer_type'),
            $coupon->getRedeemerIdColumn()   => $this->option('redeemer_id'),
            $coupon->getDataColumn()         => $this->option('data'),
        ]);

        $this->info('The coupon was added to the database successfully!');
    }
}
