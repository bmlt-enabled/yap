<?php

namespace App\Services;

use App\Repositories\DatabaseMigrationRepository;

class DatabaseMigrationsService
{
    private DatabaseMigrationRepository $databaseMigrationRepository;

    public function __construct(DatabaseMigrationRepository $databaseMigrationRepository)
    {
        $this->databaseMigrationRepository = $databaseMigrationRepository;
    }

    public function getVersion()
    {
        return $this->databaseMigrationRepository->getVersion();
    }

    public function catchup()
    {
        $currentVersion = $this->getVersion()+1;
        while (file_exists("migrations/$currentVersion.sql")) {
            $queries = array_filter(explode(";\n", file_get_contents("migrations/$currentVersion.sql")));
            foreach ($queries as $query) {
                $this->databaseMigrationRepository->deploy($query);
            }
            $this->databaseMigrationRepository->setVersion($currentVersion);
            $currentVersion++;
        }
    }
}
