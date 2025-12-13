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
        Schema::table('product_images', function (Blueprint $table) {
            // Add updated_at column if it doesn't exist
            if (!Schema::hasColumn('product_images', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
            
            // Add is_primary column if it doesn't exist
            if (!Schema::hasColumn('product_images', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
            if (Schema::hasColumn('product_images', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
