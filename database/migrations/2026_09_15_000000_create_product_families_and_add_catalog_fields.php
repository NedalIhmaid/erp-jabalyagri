<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_family_id')->nullable()->after('id')
                ->constrained('product_families')->nullOnDelete();
            $table->text('description')->nullable()->after('sku');
            $table->string('image_path')->nullable()->after('description');
            $table->string('pdf_url', 2048)->nullable()->after('image_path');
            $table->string('google_drive_url', 2048)->nullable()->after('pdf_url');
            $table->index(['product_family_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_family_id', 'is_active']);
            $table->dropConstrainedForeignId('product_family_id');
            $table->dropColumn(['description', 'image_path', 'pdf_url', 'google_drive_url']);
        });

        Schema::dropIfExists('product_families');
    }
};
