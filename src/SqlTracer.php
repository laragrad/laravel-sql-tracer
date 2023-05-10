<?php

namespace Laragrad\SqlTracer;

use Carbon\Carbon;
use Illuminate\Http\Request;

class SqlTracer
{
    /**
     * @var string
     */
    protected $columnSeparator = "\t";

    /**
     * @var string
     */
    protected string $fileName;

    /**
     *
     */
    public function __construct()
    {
        $this->fileName = $this->makeFileName();
    }

    /**
     * @return string
     */
    protected function makeFileName()
    {
        $dt = now()->format('Ymd');
        return "{$dt}.log";
    }

    /**
     * @param $query
     * @return void
     */
    public function traceQuery($query) {

        $request = \App::make(Request::class);
        if (!$request->input('x-request-debug-id')) {
            $request->merge(['x-request-debug-id' => mt_rand(0, 999999)]);
        }

        $querySql = str_replace(["\n", "\r", "\t"], [" "], $query->sql);
        $queryDuration = str_replace('.', ',', sprintf("%' 6.2f", $query->time));
        $queryBindings = $this->getBindings($query);
        $backtrace = str_replace(["\n", "\r", "\t"], [" "], $this->pruneBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 40)));

        $data = [];
        $data[] = now()->format('Y-m-d H:i:s.u');
        $data[] = $queryDuration;                                       // Query duration (ms)
        $data[] = $this->mapSqlBindings($querySql, $queryBindings); // SQL-query
        $data[] = $request->input('x-debug-request-id');                // Random request identifier
        $data[] = $request->url();                                      // URL

        if (config('laragrad.sql-tracer.mode') == 'full') {
            $data[] = $querySql;                                        // Original SQL query
            $data[] = implode(', ', $queryBindings);                         // Параметры SQL-запроса
            $data[] = $request->fullUrl();                              // Full URL
            $data[] = json_encode($request->all());                     // Requet parameters
            $data[] = json_encode($backtrace);                          // Backtrace
        }

        \Storage::disk(config('laragrad.sql-tracer.disk'))
            ->append(config('laragrad.sql-tracer.path') . '/' . $this->fileName, implode($this->columnSeparator, $data));
    }

    /**
     * @param $query
     * @return void
     */
    protected function getBindings($query)
    {
        $bindings = $query->bindings;
        foreach ($bindings as &$item) {
            if (is_object($item) && ($item instanceof \DateTime)) {
                $item = $item->format('Y-m-d H:i:s.u');
            }
        }

        return $bindings;
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return string
     */
    public function mapSqlBindings(string $sql, array $binds)
    {
        $sql = str_replace('?', '%s', $sql);

        $handledBindings = array_map(function ($binding) {
            if (is_numeric($binding)) {
                return $binding;
            }

            if (is_bool($binding)) {
                return ($binding) ? 'true' : 'false';
            }

            return "'{$binding}'";
        }, $binds);

        return vsprintf($sql, $handledBindings);
    }

    /**
     * @param array $backtrace
     * @return array
     */
    public function pruneBacktrace(array $backtrace)
    {
        return str_replace(["\n", "\r", "\t"], [" "],
            array_filter(
                array_map(function ($item) {

                    $file = $item['file'] ?? '';
                    if (stripos($file, 'vendor') || !$file) {
                        return null;
                    }
                    $line = $item['line'] ?? '';

                    return $file . ": " . $line;
                }, $backtrace)
            )
        );
    }
}
