<?php

return [

    /**
     * Enabling sql-tracing
     */
    'enable' => env('SQL_TRACER_ENABLE', false),

    /**
     * Output mode
     *
     * 'full' || 'short'
     */
    'mode' => env('SQL_TRACER_MODE', 'full'),

    /**
     * Disk where creating tracing files
     */
    'disk' => env('SQL_TRACER_DISK', 'sql-tracer'),
    'path' => env('SQL_TRACER_PATH', '/'),
];
