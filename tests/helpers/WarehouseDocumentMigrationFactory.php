<?php
namespace test\orm\helpers;

use JetBrains\PhpStorm\Pure;
use expresscore\orm\MigrationFactoryAbstract;

class WarehouseDocumentMigrationFactory extends MigrationFactoryAbstract
{
    public function createObject(array $yamlRecord) : WarehouseDocument
    {
        $warehouseDocument = new WarehouseDocument();
        $warehouseDocument->setId($yamlRecord['id']);
        $warehouseDocument->setNumber($yamlRecord['number']);

        return $warehouseDocument;
    }
}
