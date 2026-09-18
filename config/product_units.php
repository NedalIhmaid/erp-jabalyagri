<?php

use App\Enums\ProductUnitType;

/*
|--------------------------------------------------------------------------
| Product Unit Normalization Map
|--------------------------------------------------------------------------
|
| Closed lookup of every raw unit string that appears in the source
| catalogue (مواد الشركة.xlsx, 856 rows, 31 distinct strings) to the
| structured shape we store on product_units.
|
| Keys are whitespace-trimmed source strings, exactly as they appear in
| the workbook (internal whitespace and punctuation preserved so that
| lookups remain deterministic).
|
| Each entry yields:
|   - unit_type:  ProductUnitType enum value
|   - unit_value: numeric quantity in the base of that unit_type
|                 (seeds for seed packets, grams for gram packets, etc.)
|   - label:      canonical Arabic display label (trimmed, single-spaced)
|
| Whenever new rows are added to the workbook and a unit string is not
| present here, the importer raises — the policy is: every unit must be
| mapped deliberately, not guessed.
|
*/

return [

    'شوال (25كغم )' => [
        'unit_type'  => ProductUnitType::Sack->value,
        'unit_value' => 25,
        'label'      => 'شوال 25 كغم',
    ],

    'باكيت 1000 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 1000,
        'label'      => 'باكيت 1000 بذرة',
    ],

    '1 لتر' => [
        'unit_type'  => ProductUnitType::Liter->value,
        'unit_value' => 1,
        'label'      => '1 لتر',
    ],

    'عدد' => [
        'unit_type'  => ProductUnitType::Unit->value,
        'unit_value' => 1,
        'label'      => 'عدد',
    ],

    'باكيت' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 1,
        'label'      => 'باكيت',
    ],

    '1 كغم' => [
        'unit_type'  => ProductUnitType::Kilogram->value,
        'unit_value' => 1,
        'label'      => '1 كغم',
    ],

    'علبه' => [
        'unit_type'  => ProductUnitType::Unit->value,
        'unit_value' => 1,
        'label'      => 'علبة',
    ],

    'باكيت 5000 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 5000,
        'label'      => 'باكيت 5000 بذرة',
    ],

    'رول' => [
        'unit_type'  => ProductUnitType::Unit->value,
        'unit_value' => 1,
        'label'      => 'رول',
    ],

    '5 لتر' => [
        'unit_type'  => ProductUnitType::Liter->value,
        'unit_value' => 5,
        'label'      => '5 لتر',
    ],

    'جالون20لتر' => [
        'unit_type'  => ProductUnitType::Liter->value,
        'unit_value' => 20,
        'label'      => 'جالون 20 لتر',
    ],

    'باكيت 2500 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 2500,
        'label'      => 'باكيت 2500 بذرة',
    ],

    '5 كغم' => [
        'unit_type'  => ProductUnitType::Kilogram->value,
        'unit_value' => 5,
        'label'      => '5 كغم',
    ],

    'طن' => [
        'unit_type'  => ProductUnitType::Ton->value,
        'unit_value' => 1,
        'label'      => 'طن',
    ],

    'باكيت 500 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 500,
        'label'      => 'باكيت 500 بذرة',
    ],

    'باكيت 100بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 100,
        'label'      => 'باكيت 100 بذرة',
    ],

    'باكيت 100 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 100,
        'label'      => 'باكيت 100 بذرة',
    ],

    'جالون10لتر' => [
        'unit_type'  => ProductUnitType::Liter->value,
        'unit_value' => 10,
        'label'      => 'جالون 10 لتر',
    ],

    'شوال 20كغم' => [
        'unit_type'  => ProductUnitType::Sack->value,
        'unit_value' => 20,
        'label'      => 'شوال 20 كغم',
    ],

    // European thousands-dot: "100.000" means 100 000, not 100
    'باكيت 100.000 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 100000,
        'label'      => 'باكيت 100,000 بذرة',
    ],

    'شوال (50 كغم )' => [
        'unit_type'  => ProductUnitType::Sack->value,
        'unit_value' => 50,
        'label'      => 'شوال 50 كغم',
    ],

    'باكيت (1/2 كغم)' => [
        'unit_type'  => ProductUnitType::Kilogram->value,
        'unit_value' => 0.5,
        'label'      => 'باكيت 1/2 كغم',
    ],

    'باكيت 25000 بذرة' => [
        'unit_type'  => ProductUnitType::Packet->value,
        'unit_value' => 25000,
        'label'      => 'باكيت 25000 بذرة',
    ],

    'شوال 10 كغم' => [
        'unit_type'  => ProductUnitType::Sack->value,
        'unit_value' => 10,
        'label'      => 'شوال 10 كغم',
    ],

    'متر مربع' => [
        'unit_type'  => ProductUnitType::Unit->value,
        'unit_value' => 1,
        'label'      => 'متر مربع',
    ],

    'جالون 30لتر' => [
        'unit_type'  => ProductUnitType::Liter->value,
        'unit_value' => 30,
        'label'      => 'جالون 30 لتر',
    ],

    'ملليتر' => [
        'unit_type'  => ProductUnitType::Milliliter->value,
        'unit_value' => 1,
        'label'      => 'ملليتر',
    ],

    'انبوب 750 غم' => [
        'unit_type'  => ProductUnitType::Gram->value,
        'unit_value' => 750,
        'label'      => 'أنبوب 750 غم',
    ],

    'سطل' => [
        'unit_type'  => ProductUnitType::Unit->value,
        'unit_value' => 1,
        'label'      => 'سطل',
    ],

    'متر طولي' => [
        'unit_type'  => ProductUnitType::Unit->value,
        'unit_value' => 1,
        'label'      => 'متر طولي',
    ],

    'باكيت 400 غم' => [
        'unit_type'  => ProductUnitType::Gram->value,
        'unit_value' => 400,
        'label'      => 'باكيت 400 غم',
    ],

    'باكيت 250 غم' => [
        'unit_type'  => ProductUnitType::Gram->value,
        'unit_value' => 250,
        'label'      => 'باكيت 250 غم',
    ],

];
