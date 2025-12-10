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
            // Delivery tracking fields
            $table->enum('delivery_status', ['pending', 'preparing', 'ready_for_pickup', 'assigned', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'])->default('pending')->after('status');
            $table->string('tracking_number', 50)->nullable()->after('delivery_status');
            $table->unsignedInteger('assigned_to')->nullable()->after('tracking_number'); // logistics user ID
            $table->dateTime('scheduled_delivery_date')->nullable()->after('assigned_to');
            $table->dateTime('delivered_at')->nullable()->after('scheduled_delivery_date');
            $table->text('delivery_notes')->nullable()->after('delivered_at');
            $table->string('delivery_proof_image')->nullable()->after('delivery_notes'); // URL to delivery proof photo

            $table->index('delivery_status');
            $table->index('assigned_to');
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_status',
                'tracking_number',
                'assigned_to',
                'scheduled_delivery_date',
                'delivered_at',
                'delivery_notes',
                'delivery_proof_image',
            ]);
        });
    }
};
