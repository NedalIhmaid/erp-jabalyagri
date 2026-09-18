<?php

return [
    'title'               => 'Leave Management',
    'balance'             => 'Leave Balance',
    'balances'            => 'Leave Balances',
    'year'                => 'Year',
    'employee'            => 'Employee',
    'hire_date'           => 'Hire Date',
    'years_of_service'    => 'Years of Service',
    'years'               => 'yrs',
    'months'              => 'months',
    'annual_entitlement'  => 'Allowed Leave',
    'annual_entitlement_desc' => 'Entitled this year: :days days',
    'annual_accrued'      => 'Accrued to Date',

    // Leave types (short labels for table columns)
    'annual'              => 'Annual',
    'sick'                => 'Sick',
    'marriage'            => 'Marriage',
    'maternity'           => 'Maternity',
    'bereavement'         => 'Bereavement',

    // Balance fields
    'annual_total'        => 'Annual Total',
    'annual_used'         => 'Annual Used',
    'annual_remaining'    => 'Annual Remaining',
    'sick_total'          => 'Sick Total',
    'sick_used'           => 'Sick Used',
    'sick_remaining'      => 'Sick Remaining',
    'marriage_used'       => 'Marriage Leave Used',
    'maternity_used'      => 'Maternity Days Used',
    'bereavement_used'    => 'Bereavement Events Used',

    // Summary labels
    'used'                => 'Used',
    'remaining'           => 'Remaining',
    'total'               => 'Total',
    'of'                  => 'of',
    'days'                => 'days',
    'yes'                 => 'Yes',
    'no'                  => 'No',

    // Calendar
    'calendar'            => 'Leave Calendar',
    'calendar_desc'       => 'Approved team leaves for this month',
    'on_leave'            => 'On Leave',
    'no_leaves'           => 'No leaves scheduled this month',
    'date_range'          => 'Period',
    'leave_type'          => 'Leave Type',

    // Actions / messages
    'init_year'           => 'Initialise Year Balance',
    'init_year_desc'      => 'Create leave balances for all active employees for the given year',
    'init_year_confirm'   => 'Create :year leave balances for all active employees?',
    'init_year_done'      => ':count leave balance(s) created successfully',
    'init_year_skipped'   => ':count record(s) already existed and were skipped',
    'balance_restored'    => 'Leave balance restored',
    'balance_deducted'    => 'Leave balance deducted',
    'insufficient'        => 'Insufficient leave balance',
    'adjust_balance'      => 'Adjust Balance',
    'other_types'         => 'Other Leave Types',
    'other_types_desc'    => 'Marriage, maternity, bereavement — deducted once or per event',
    'events'              => 'events',
];
