<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Models\Coupon;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('couponable_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(config('couponables.model', Coupon::class))
                ->constrained()
                ->onDelete('cascade');
            $table->morphs(
                Str::singular('couponable_tests')
            );
            $table->timestamp('used_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('couponable_tests');
    }
};
