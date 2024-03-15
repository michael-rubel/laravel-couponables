<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons_test', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->json('data_test')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('limit')->default(1);
            $table->nullableMorphs('redeemer');
            $table->timestamp('expires_at')->nullable();
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
        Schema::dropIfExists('coupons_test');
    }
};
