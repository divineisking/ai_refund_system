<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            // 'pending' = deterministic decision returned, AI queued
            // 'completed' = AI response received and record updated
            // 'failed' = AI job exhausted all retries, deterministic result is final
            // 'skipped' = no API key configured, deterministic is intentional
            $table->string('ai_status')->default('skipped')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn('ai_status');
        });
    }
};
