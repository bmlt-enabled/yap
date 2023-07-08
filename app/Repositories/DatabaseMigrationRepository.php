<?php

namespace App\Repositories;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DatabaseMigrationRepository
{
    public function getVersion()
    {
        try {
            $res = DB::select("SELECT version FROM migrations ORDER BY id DESC LIMIT 1");
            $version = count($res) > 0 ? $res[0]->version : -1;
        } catch (QueryException $queryException) {
            $version = -1;
        }

        return $version;
    }

    public function deploy($query): void
    {
        DB::statement($query);
    }

    public function setVersion($version)
    {
        DB::insert("INSERT INTO migrations (version) VALUES (?)", [$version]);
    }
}
