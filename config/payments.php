<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment gateways enabled
    |--------------------------------------------------------------------------
    | When false, the student-facing payment/checkout step is disabled: paid
    | subscriptions and enrollments are recorded as "pending" and must be
    | activated by an admin from the dashboard (Filament). Flip to true once
    | real gateway credentials are configured.
    */
    'enabled' => (bool) env('PAYMENTS_ENABLED', false),
];
