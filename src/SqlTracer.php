<?php

namespace Laragrad\SqlTracer;

use Carbon\Carbon;
use Illuminate\Http\Request;

class SqlTracer
{
    const MODE_SHORT = 'short';
    const MODE_FULL = 'full';

    const UTF8_BOM = "\xEF\xBB\xBF";
    const CSV_COL_SEP = ";";

    /**
     * @var string
     */
    protected string $fileName;

    /**
     * @var string
     */
    protected string $mode;

    /**
     * @var string
     */
    protected string $disk;

    /**
     * @var string
     */
    protected string $path;

    protected $outFile;

    /**
     *
     */
    public function __construct()
    {
        $this->mode = config('laragrad.sql-tracer.mode', 'full');
        $this->disk = config('laragrad.sql-tracer.disk', 'sql-tracer');
        $this->path = config('laragrad.sql-tracer.path', '/');
        $this->fileName = $this->makeFileName();

    }

    protected function toCsv(array $lineData)
    {
        $f = fopen('php://memory', 'r+');
        fputcsv($f, $lineData, self::CSV_COL_SEP);
        rewind($f);
        $result = str_replace("\n", "", stream_get_contents($f));
        fclose($f);

        return $result;

    }

    /**
     * @return string
     */
    protected function makeFileName()
    {
        $dt = now()->format('Ymd');
        return $this->path . '/' . "{$dt}.csv";
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
        $data[] = $request->input('x-request-debug-id');                // Random request identifier
        $data[] = $queryDuration;                                       // Query duration (ms)
        $data[] = $this->mapSqlBindings($querySql, $queryBindings);     // SQL query with mapped parameters
        $data[] = $request->url();                                      // URL

        if ($this->mode == self::MODE_FULL) {
            $data[] = $querySql;                                        // Original SQL query
            $data[] = json_encode(array_values($queryBindings));        // SQL query parameters
            $data[] = $request->fullUrl();                              // Full URL
            $data[] = json_encode($request->all());                     // Requet parameters
            $data[] = json_encode($backtrace);                          // Backtrace
        }

        if (! \Storage::disk($this->disk)->exists($this->fileName)) {
            $this->makeHeader();
        }

        \Storage::disk($this->disk)
            ->append($this->fileName, $this->toCsv($data));


    }

    protected function makeHeader()
    {
        $headerData = [
            'Date/Time',
            'Request ID',
            'ms',
            'SQL',
            'URL',
        ];

        if ($this->mode == self::MODE_FULL) {
            $headerData = array_merge($headerData, [
                'Original SQL',
                'SQL parameters',
                'Full URL',
                'Request parameters',
                'Backtrace',
            ]);
        }

        \Storage::disk($this->disk)
            ->append($this->fileName, self::UTF8_BOM . $this->toCsv($headerData));
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
