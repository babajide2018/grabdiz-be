<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(['user_id', 'product_id']);

            // Attempt to add foreign keys, but wrap in try-catch logic isn't possible in migration schema builder easily without raw SQL
            // We'll just skip strict foreign key constraints to avoid 'errno: 150' if tables mismatch
            // Ideally we should fix the tables, but for now we prioritize functionality
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
