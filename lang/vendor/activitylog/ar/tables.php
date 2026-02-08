<?php

return [
    'columns' => [
        'log_name' => [
            'label' => 'النوع',
        ],
        'event' => [
            'label' => 'الحدث',
        ],
        'subject_type' => [
            'label' => 'الموضوع',
            'soft_deleted' => ' (محذوف مؤقتاً)',
            'deleted' => ' (محذوف)',
        ],
        'causer' => [
            'label' => 'المستخدم',
        ],
        'properties' => [
            'label' => 'الخصائص',
        ],
        'created_at' => [
            'label' => 'تاريخ التسجيل',
        ],
    ],
    'filters' => [
        'created_at' => [
            'label' => 'تاريخ التسجيل',
            'created_from' => 'من تاريخ',
            'created_from_indicator' => 'من تاريخ: :created_from',
            'created_until' => 'حتى تاريخ',
            'created_until_indicator' => 'حتى تاريخ: :created_until',
        ],
        'event' => [
            'label' => 'الحدث',
        ],
        'log_name' => [
            'label' => 'اسم السجل',
        ],
    ],
];
