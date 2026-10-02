<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 32)->unique();
            $table->string('customer_id', 32);
            $table->date('order_date');
            $table->date('delivered_date');
            $table->string('item_name', 255);
            $table->string('category', 100);
            $table->decimal('price', 10, 2);
            $table->boolean('is_final_sale')->default(false);
            $table->boolean('is_damaged')->default(false);
            $table->unsignedSmallInteger('return_window_days')->default(30);
            $table->string('tracking_number', 64)->nullable();
            $table->string('status', 50)->default('DELIVERED');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('customers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
