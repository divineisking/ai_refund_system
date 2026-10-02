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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 32)->unique();
            $table->string('customer_id', 32);
            $table->string('order_id', 32);
            $table->text('customer_message');
            $table->enum('decision', ['APPROVED', 'DENIED', 'ESCALATED']);
            $table->string('policy_clause_triggered', 255);
            $table->decimal('confidence_score', 4, 2)->default(1.00);
            $table->text('customer_explanation');
            $table->text('internal_reasoning');
            $table->string('suggested_action', 255);
            $table->boolean('prompt_injection_detected')->default(false);
            $table->json('prompt_injection_flags')->nullable();
            $table->enum('evaluation_mode', ['GEMINI_AI', 'DETERMINISTIC_FALLBACK']);
            $table->json('raw_model_response')->nullable();
            $table->enum('status', ['RESOLVED_AUTOMATIC', 'PENDING_HUMAN_REVIEW', 'MANUALLY_OVERRIDDEN'])->default('RESOLVED_AUTOMATIC');
            $table->text('admin_notes')->nullable();
            $table->enum('manual_decision', ['APPROVED', 'DENIED'])->nullable();
            $table->string('reviewed_by', 100)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('customers')
                ->onDelete('cascade');

            $table->foreign('order_id')
                ->references('order_id')
                ->on('orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
