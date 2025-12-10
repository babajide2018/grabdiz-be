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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('status', 50); // order status (pending, processing, etc.)
            $table->string('delivery_status', 50)->nullable(); // delivery status
            $table->unsignedInteger('changed_by')->nullable(); // user ID who made the change
            $table->string('changed_by_type', 20)->nullable(); // 'admin', 'logistics', 'system'
            $table->text('notes')->nullable(); // optional notes about the status change
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('delivery_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
