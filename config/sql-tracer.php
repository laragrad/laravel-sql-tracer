<?php

return [

    /**
     *
     */
    'enable' => env('SQL_TRACER_ENABLE', false),

    /**
     * Output mode
     * 'full' | 'short'
     */
    'mode' => env('SQL_TRACER_MODE', 'full'),

    /**
     *
     */
    'disk' => env('SQL_TRACER_DISK', 'sql-tracer'),
];
