<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_category_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $driver = Schema::getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                // MySQL refuses to drop an index still referenced by a foreign
                // key, so drop the FK first, then the composite index, then the column.
                $table->dropForeign(['product_category_id']);
                $table->dropIndex(['product_category_id', 'is_active']);
                $table->dropColumn('product_category_id');
            } else {
                // SQLite rebuilds the table, so dropping the index then the
                // constrained column is sufficient (and required for the rebuild).
                $table->dropIndex(['product_category_id', 'is_active']);
                $table->dropConstrainedForeignId('product_category_id');
            }
        });

        Schema::dropIfExists('product_categories');
    }

    public function down(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')
                ->nullable()
                ->after('id')
                ->constrained('product_categories')
                ->cascadeOnDelete();

            $table->index(['product_category_id', 'is_active']);
        });

        Schema::table('sales_request_items', function (Blueprint $table) {
            $table->foreignId('product_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();
        });
    }
};
