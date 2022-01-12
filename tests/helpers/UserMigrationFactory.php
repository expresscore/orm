<?php
namespace test\orm\helpers;

use expresscore\orm\MigrationFactoryAbstract;

class UserMigrationFactory extends MigrationFactoryAbstract
{
    public function createObject(array $yamlRecord) : User
    {
        $user = new User();
        $user->setId($yamlRecord['id']);
        $user->setName($yamlRecord['name']);

        return $user;
    }
}
