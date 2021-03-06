<?php
namespace test\orm\helpers;

use JetBrains\PhpStorm\Pure;
use expresscore\orm\MigrationFactoryAbstract;

class EntityOneMigrationFactory extends MigrationFactoryAbstract
{
    #[Pure] public function createObject(array $yamlRecord) : EntityOne
    {
        $entityOne = new EntityOne();
        $entityOne->id = $yamlRecord['id'];
        $entityOne->name = $yamlRecord['name'];

        return $entityOne;
    }
}
