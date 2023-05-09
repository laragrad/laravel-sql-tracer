<?php

namespace Laragrad\SqlTracer;

class SqlTracer
{
    protected static $column_sep = "\t";

    /**
     * @param $query
     * @return void
     */
    public static function traceQuery($query) {

        $logName = '/logs/query' . \Carbon\Carbon::now()->format('_Ymd') . '.log';

        $request = \App::make(\Illuminate\Http\Request::class);
        if (!$request->input('x-debug-request-id')) {
            $request->merge(['x-debug-request-id' => mt_rand(0, 999999)]);
        }

        $sql = str_replace(["\n", "\r", "\t"], [" "], $query->sql);

        $tm = str_replace('.', ',', sprintf("%' 6.2f", $query->time));

        foreach ($query->bindings as &$item) {
            if (is_object($item) && get_class($item) == 'DateTime') {
                $item = $item->format('Y-m-d H:i:s.u');
            }
        }

        $backtrace = str_replace(["\n", "\r", "\t"], [" "], self::prepareBacktrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 40)));

        $msg = '';
        $msg .= now()->format('Y-m-d H:i:s.u');
        $msg .= self::$column_sep . $request->input('x-debug-request-id');     // Случайный идентификатор API-реквеста
        $msg .= self::$column_sep . $tm;                                       // Длительность запроса в ms
        $msg .= self::$column_sep . self::eloquentSqlWithBindings($query->sql, $query->bindings);
        $msg .= self::$column_sep . $request->url();                           // URL API-реквеста
        if (config('app.sql_trace_mode') == 'full') {
            $msg .= self::$column_sep . $sql;                                  // SQL запрос
            $msg .= self::$column_sep . implode(', ', $query->bindings);       // Параметры SQL-запроса
            $msg .= self::$column_sep . $request->fullUrl();                   // Полный URL API-реквеста
            $msg .= self::$column_sep . json_encode($request->all());          // Параметры API-реквеста в виде JSON-объекта
            $msg .= self::$column_sep . json_encode($backtrace);               // Параметры API-реквеста в виде JSON-объекта
        }

        \File::append(
            storage_path($logName),
            $msg . PHP_EOL
        );
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return string
     */
    public static function eloquentSqlWithBindings(string $sql, array $binds)
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
    public static function prepareBacktrace(array $backtrace)
    {
        return array_filter(array_map(function ($item) {
            $filePath = $item['file'] ?? null;
            if (stripos($filePath, 'vendor') || !$filePath) {
                return null;
            }
            $line = $item['line'] ?? null;
            return $filePath . ": " . $line;
        }, $backtrace));
    }
}
