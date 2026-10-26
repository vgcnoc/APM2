<?php

return [
    // Billing dates and clock times are entered in this timezone, independently of VPS time.
    'timezone' => env('BILLING_TIMEZONE', 'Asia/Jakarta'),
    'scheduler_user' => env('BILLING_SCHEDULER_USER', PHP_OS_FAMILY === 'Windows' ? null : 'www-data'),
];
