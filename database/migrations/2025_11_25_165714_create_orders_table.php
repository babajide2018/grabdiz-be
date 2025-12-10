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
            $table->unsignedInteger('user_id')->nullable();
            $table->string('order_number', 50)->unique();
            $table->string('status', 20)->default('pending'); // pending, processing, completed, cancelled
            $table->decimal('total_amount', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->string('shipping_method', 50)->nullable();
            $table->string('payment_method', 50)->nullable();

            // Stripe payment fields
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->enum('payment_status', ['pending', 'succeeded', 'failed', 'canceled'])->default('pending');

            // Billing information
            $table->string('billing_first_name', 100)->nullable();
            $table->string('billing_last_name', 100)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('billing_phone', 20)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_postcode', 20)->nullable();
            $table->string('billing_country', 100)->nullable();

            // Shipping information
            $table->string('shipping_first_name', 100)->nullable();
            $table->string('shipping_last_name', 100)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_postcode', 20)->nullable();
            $table->string('shipping_country', 100)->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('stripe_payment_intent_id');
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
