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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('password');
            $table->unsignedBigInteger('manager_id')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('manager_id');
            $table->string('locale', 5)->default('ar')->after('is_active');

            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['phone', 'manager_id', 'is_active', 'locale']);
        });
    }
};
