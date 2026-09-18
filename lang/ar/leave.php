<?php

return [
    'title'               => 'إدارة الإجازات',
    'balance'             => 'رصيد الإجازات',
    'balances'            => 'أرصدة الإجازات',
    'year'                => 'السنة',
    'employee'            => 'الموظف',
    'hire_date'           => 'تاريخ التعيين',
    'years_of_service'    => 'سنوات الخدمة',
    'years'               => 'سنة',
    'months'              => 'شهر',
    'annual_entitlement'  => 'الإجازات المسموحة',
    'annual_entitlement_desc' => 'المستحق هذه السنة: :days يوم',
    'annual_accrued'      => 'المرصّد حتى اليوم',

    // Leave types (short labels for table columns)
    'annual'              => 'السنوية',
    'sick'                => 'المرضية',
    'marriage'            => 'الزواج',
    'maternity'           => 'الأمومة',
    'bereavement'         => 'الوفاة',

    // Balance fields
    'annual_total'        => 'إجمالي السنوية',
    'annual_used'         => 'المستخدم (سنوية)',
    'annual_remaining'    => 'المتبقي (سنوية)',
    'sick_total'          => 'إجمالي المرضية',
    'sick_used'           => 'المستخدم (مرضية)',
    'sick_remaining'      => 'المتبقي (مرضية)',
    'marriage_used'       => 'إجازة الزواج مستخدمة',
    'maternity_used'      => 'أيام الأمومة المستخدمة',
    'bereavement_used'    => 'عدد مرات إجازة الوفاة',

    // Summary labels
    'used'                => 'مستخدم',
    'remaining'           => 'متبقي',
    'total'               => 'الإجمالي',
    'of'                  => 'من أصل',
    'days'                => 'يوم',
    'yes'                 => 'نعم',
    'no'                  => 'لا',

    // Calendar
    'calendar'            => 'جدول الإجازات',
    'calendar_desc'       => 'سجل الإجازات المعتمدة للفريق لهذا الشهر',
    'on_leave'            => 'في إجازة',
    'no_leaves'           => 'لا توجد إجازات مجدولة هذا الشهر',
    'date_range'          => 'الفترة',
    'leave_type'          => 'نوع الإجازة',

    // Actions / messages
    'init_year'           => 'تهيئة رصيد السنة',
    'init_year_desc'      => 'إنشاء أرصدة الإجازات لجميع الموظفين النشطين للسنة المحددة',
    'init_year_confirm'   => 'هل تريد إنشاء أرصدة السنة :year لكل الموظفين النشطين؟',
    'init_year_done'      => 'تم إنشاء :count رصيد إجازة بنجاح',
    'init_year_skipped'   => 'تم تخطي :count سجل موجود مسبقاً',
    'balance_restored'    => 'تم استعادة رصيد الإجازة',
    'balance_deducted'    => 'تم خصم رصيد الإجازة',
    'insufficient'        => 'رصيد الإجازة غير كافٍ',
    'adjust_balance'      => 'تعديل الرصيد',
    'other_types'         => 'أنواع الإجازات الأخرى',
    'other_types_desc'    => 'زواج، أمومة، وفاة — تُخصم مرة واحدة أو لكل حدث',
    'events'              => 'حوادث',
];
