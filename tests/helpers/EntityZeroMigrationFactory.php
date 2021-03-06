<?php
namespace test\orm\helpers;

use JetBrains\PhpStorm\Pure;
use expresscore\orm\MigrationFactoryAbstract;

class EntityZeroMigrationFactory extends MigrationFactoryAbstract
{
    public function createObject(array $yamlRecord) : EntityZero
    {
        $entityTwo = new EntityZero();
        $entityTwo->setId($yamlRecord['id']);
        $entityTwo->setName($yamlRecord['name']);

        return $entityTwo;
    }
}
