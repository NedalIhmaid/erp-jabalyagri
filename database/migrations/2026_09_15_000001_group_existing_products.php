<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('products')->whereNull('product_family_id')->exists()) {
            return;
        }

        $familyId = DB::table('product_families')->insertGetId([
            'name' => 'غير مصنف',
            'description' => 'منتجات سابقة تحتاج إلى ربطها بمجموعة رئيسية.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')
            ->whereNull('product_family_id')
            ->update(['product_family_id' => $familyId]);
    }

    public function down(): void
    {
        $familyIds = DB::table('product_families')
            ->where('name', 'غير مصنف')
            ->pluck('id');

        DB::table('products')
            ->whereIn('product_family_id', $familyIds)
            ->update(['product_family_id' => null]);

        DB::table('product_families')->whereIn('id', $familyIds)->delete();
    }
};
