<?php 

return [
    
    'enable' => env('SQL_TRACER_ENABLE', false),
    
    /**
     * 'full' or 'short'
     */
    'mode' => env('SQL_TRACER_MODE', 'full'),
];