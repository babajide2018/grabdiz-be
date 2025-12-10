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
        Schema::table('orders', function (Blueprint $table) {
            // Add Stripe payment fields if they don't exist
            if (!Schema::hasColumn('orders', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('stripe_payment_intent_id');
            }
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'succeeded', 'failed', 'canceled'])->default('pending')->after('stripe_customer_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('orders', 'stripe_customer_id')) {
                $table->dropColumn('stripe_customer_id');
            }
            if (Schema::hasColumn('orders', 'stripe_payment_intent_id')) {
                $table->dropColumn('stripe_payment_intent_id');
            }
        });
    }
};
