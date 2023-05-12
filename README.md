# laravel-sql-tracer

## Install

Install a package

```
composer require laragrad/laravel-sql-tracer
```

By default, tracing is disabled. To enable tracing append into `.env` next line

```
SQL_TRACER_ENABLE=true
```

Tracing output to file `./storage/sql-tracer/YYYYMMDD.csv`

## Configuring .env

- **SQL_TRACER_MODE=full** - to change tracing output mode (full or short)
- **SQL_TRACER_DISK=sql-tracer** - to change filesystem disk name
- **SQL_TRACER_PATH=** - to set custom folder on the disk

## Publishing configuration file

To publish package configuration file into project use command

```
php artisan vendor:publish --provider="Laragrad\SqlTracer\SqlTracerProvider"
```

File will be published into `./config/laragrad/sql-tracer.php`
