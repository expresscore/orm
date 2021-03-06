<?php
namespace test\orm\helpers;

use expresscore\orm\MigrationFactoryAbstract;

class InvoiceMigrationFactory extends MigrationFactoryAbstract
{
    public function createObject(array $yamlRecord) : Invoice
    {
        $invoice = new Invoice();
        $invoice->setId($yamlRecord['id']);
        $invoice->setNumber($yamlRecord['number']);

        return $invoice;
    }
}
