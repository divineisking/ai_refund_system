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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id', 32)->unique();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->enum('risk_tier', ['LOW', 'MEDIUM', 'HIGH'])->default('LOW');
            $table->unsignedTinyInteger('fraud_score')->default(0);
            $table->unsignedInteger('total_orders')->default(1);
            $table->decimal('return_rate', 5, 2)->default(0.00);
            $table->date('account_created_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
