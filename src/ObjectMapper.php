<?php
/**
 * This file is part of the ExpressCore package.
 *
 * (c) Marcin Stodulski <marcin.stodulski@devsprint.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace expresscore\orm;

use DateTime;
use Exception;
use ReflectionClass;
use ReflectionProperty;

class ObjectMapper {

    private static function createMockObject(string $mockObjectClass, mixed $entityId, EntityManager $entityManager) : object
    {
        $mockObjectConfiguration = $entityManager->configurationLoader->loadClassConfiguration($mockObjectClass);
        $mockObject = ObjectFactory::create($mockObjectClass, $entityManager);

        $idField = self::getIdFieldName($mockObjectConfiguration);
        if ($idField !== null) {
            $reflectionClass = new ReflectionClass($mockObjectClass);
            $reflectionObjectIdField = $reflectionClass->getProperty($idField);
            $reflectionObjectIdField->setValue($mockObject, $entityId);

            return $mockObject;

        } else {
            throw new Exception('Mock object class dont have field defined as its id');
        }
    }

    public static function getClassProperties(string $className, array &$reflectionObjectFields, array $specificProperties = []) : void
    {
        $reflectionClass = new ReflectionClass($className);

        $newObjectClassFields = [];
        $reflectionObjectFields = array_merge($reflectionObjectFields, $reflectionClass->getProperties());
        foreach ($reflectionObjectFields as $reflectionObjectField) {
            if (empty($specificProperties) || (in_array($reflectionObjectField->name, $specificProperties))) {
                $newObjectClassFields[$reflectionObjectField->name] = $reflectionObjectField;
            }
        }
        $reflectionObjectFields = $newObjectClassFields;

        if ($reflectionClass->getParentClass()) {
            $reflectionObjectFields = array_merge($reflectionObjectFields, $reflectionClass->getParentClass()->getProperties());

            $newObjectClassFields = [];
            foreach ($reflectionObjectFields as $reflectionObjectField) {
                if (empty($specificProperties) || (in_array($reflectionObjectField->name, $specificProperties))) {
                    $newObjectClassFields[$reflectionObjectField->name] = $reflectionObjectField;
                }
            }
            $reflectionObjectFields = $newObjectClassFields;

            self::getClassProperties($reflectionClass->getParentClass()->getName(), $reflectionObjectFields, $specificProperties);
        }
    }

    public static function mapEntity(object $entity, array $dbObject, EntityManager $entityManager, string $fieldName = null, object $parent = null) : object
    {
        if (property_exists($entity, '___orm_initialized')) {
            $entity->___orm_initialized = true;
        }

        $entityConfiguration = $entityManager->configurationLoader->loadClassConfiguration(get_class($entity));

        $reflectionObjectFields = [];
        self::getClassProperties(get_class($entity), $reflectionObjectFields);

        /** @var ReflectionProperty $reflectionObjectField */
        foreach ($reflectionObjectFields as $reflectionObjectField) {
            if (isset($dbObject[$reflectionObjectField->getName()])) {

                if ($entityConfiguration['fields'][$reflectionObjectField->getName()]['type'] == 'entity') {

                    $isLazy = !isset($entityConfiguration['fields'][$reflectionObjectField->getName()]['lazy']) || $entityConfiguration['fields'][$reflectionObjectField->getName()]['lazy'];

                    $entityClassName = $entityConfiguration['fields'][$reflectionObjectField->getName()]['entityClass'];
                    $entityId = $dbObject[$reflectionObjectField->getName()];

                    if (($fieldName == $reflectionObjectField->getName()) && ($parent !== null)) {
                        $reflectionObjectField->setValue($entity, $parent);
                    } else {
                        if ($isLazy) {
                            $reflectionObjectField->setValue($entity, self::createMockObject($entityClassName, $entityId, $entityManager));
                        } else {
                            $reflectionObjectField->setValue($entity, $entityManager->find($entityClassName, $entityId));
                        }
                    }

                } else {
                    $preparedValue = $dbObject[$reflectionObjectField->getName()];
                    $preparedValue = self::translatePreparedValueForMap($entityConfiguration, $reflectionObjectField, $preparedValue);
                    $reflectionObjectField->setValue($entity, $preparedValue);
                }
            } else {
                if (isset($entityConfiguration['fields'][$reflectionObjectField->getName()])) {
                    if ($entityConfiguration['fields'][$reflectionObjectField->getName()]['type'] == 'collection') {

                        $isLazy = !isset($entityConfiguration['fields'][$reflectionObjectField->getName()]['lazy']) || $entityConfiguration['fields'][$reflectionObjectField->getName()]['lazy'];

                        $entityClassName = $entityConfiguration['fields'][$reflectionObjectField->getName()]['entityClass'];

                        $joiningField = $entityConfiguration['fields'][$reflectionObjectField->getName()]['joiningField'];
                        if ($isLazy) {
                            $reflectionObjectField->setValue($entity, new LazyCollection($entity, $entityClassName, $joiningField, $reflectionObjectField->getName()));
                        } else {
                            $lazyCollection = new LazyCollection($entity, $entityClassName, $joiningField, $reflectionObjectField->getName());
                            $reflectionObjectField->setValue($entity, $lazyCollection->getCollection());
                        }
                    }
                }
            }
        }

        if (isset($entityConfiguration['lifecycle']['postLoad'])) {
            $staticMethodName = $entityConfiguration['lifecycle']['postLoad'];
            $staticMethodName($entity);
        }

        return $entity;
    }

    public static function unmapEntity(object $entity, EntityManager $entityManager) : array
    {
        $entityConfiguration = $entityManager->configurationLoader->loadClassConfiguration(get_class($entity));

        $entitiesToSave = [];
        $reflectionClass = new ReflectionClass(get_class($entity));

        foreach ($entityConfiguration['fields'] as $fieldName => $fieldProperties) {
            $classProperties = [];
            self::getClassProperties($reflectionClass->name, $classProperties);

            $reflectionObjectField = null;
            /** @var ReflectionProperty $classProperty */
            foreach ($classProperties as $classProperty) {
                if ($classProperty->name == $fieldName) {
                    $reflectionObjectField = $classProperty;
                    break;
                }
            }

            if ($reflectionObjectField == null) {
                throw new Exception('Field "' . $fieldName . '" exists in orm file, but not exist in ' . get_class($entity) . ' class.');
            }

            if ($fieldProperties['type'] == 'entity') {
                $fieldObjectConfiguration = $entityManager->configurationLoader->loadClassConfiguration($fieldProperties['entityClass']);
                $idField = self::getIdFieldName($fieldObjectConfiguration);

                if ($idField !== null) {
                    if (!$reflectionObjectField->isInitialized($entity)) {
                        $preparedValue = null;
                    } else {
                        $preparedValue = $reflectionObjectField->getValue($entity);
                    }

                    if ($preparedValue !== null) {
                        $preparedValueClassProperties = [];
                        self::getClassProperties(get_class($preparedValue), $preparedValueClassProperties);

                        $reflectionObjectIdField = null;
                        /** @var ReflectionProperty $classProperty */
                        foreach ($preparedValueClassProperties as $preparedValueClassProperty) {
                            if ($preparedValueClassProperty->name == $idField) {
                                $reflectionObjectIdField = $preparedValueClassProperty;
                                break;
                            }
                        }

                        $objectId = $reflectionObjectIdField->getValue($preparedValue);

                        $entitiesToSave[get_class($entity)][spl_object_id($entity)]['data'][$fieldName] = $objectId;
                    } else {
                        $entitiesToSave[get_class($entity)][spl_object_id($entity)]['data'][$fieldName] = null;
                    }

                } else {
                    throw new Exception('Mock object class dont have field defined as its id');
                }
            } elseif ($fieldProperties['type'] != 'collection') {

                if ($reflectionObjectField->isInitialized($entity)) {
                    $preparedValue = $reflectionObjectField->getValue($entity);
                } else {
                    $preparedValue = null;
                }

                if (isset($fieldProperties['id']) && ($fieldProperties['id'] === true)) {
                    $entitiesToSave[get_class($entity)][spl_object_id($entity)]['identifier']['fieldName'] = $fieldName;
                    $entitiesToSave[get_class($entity)][spl_object_id($entity)]['identifier']['value'] = $preparedValue;
                } else {
                    $preparedValue = self::translatePreparedValueForUnmap($fieldProperties, $preparedValue);
                    $entitiesToSave[get_class($entity)][spl_object_id($entity)]['data'][$fieldName] = $preparedValue;
                }
            }
        }

        return $entitiesToSave;
    }

    private static function translatePreparedValueForMap(array $entityConfiguration, $reflectionObjectField, mixed $preparedValue)
    {
        switch ($entityConfiguration['fields'][$reflectionObjectField->getName()]['type']) {
            case 'datetime':
                if ($preparedValue !== null) {
                    $preparedValue = date('Y-m-d H:i:s', strtotime($preparedValue));
                    $preparedValue = DateTime::createFromFormat('Y-m-d H:i:s', $preparedValue);
                }
                break;
            case 'date':
                if ($preparedValue !== null) {
                    $preparedValue = date('Y-m-d', strtotime($preparedValue));
                    $preparedValue = DateTime::createFromFormat('Y-m-d', $preparedValue);
                }
                break;
            case 'boolean':
                if ($preparedValue !== null) {
                    $preparedValue = boolval($preparedValue);
                }
                break;
        }

        return $preparedValue;
    }

    private static function translatePreparedValueForUnmap(array $fieldProperties, mixed $preparedValue)
    {
        return match ($fieldProperties['type']) {
            'datetime' => ($preparedValue !== null) ? $preparedValue->format('Y-m-d H:i:s') : null,
            'date' => ($preparedValue !== null) ? $preparedValue->format('Y-m-d') : null,
            'boolean' => ($preparedValue) ? 1 : 0,
            default => $preparedValue
        };
    }

    public static function getIdFieldName(array $classConfiguration) : string|null
    {
        $idField = null;
        foreach ($classConfiguration['fields'] as $fieldName => $fieldConfiguration) {
            if (isset($fieldConfiguration['id']) && ($fieldConfiguration['id'] === true)) {
                $idField = $fieldName;
                break;
            }
        }

        return $idField;
    }
}
