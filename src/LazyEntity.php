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

use ReflectionProperty;

class LazyEntity {

    public static function getLazyEntity(object $object, string $fieldName)
    {
        $fieldValue = null;

        $classProperties = [];
        $fieldClassProperty = null;

        ObjectMapper::getClassProperties(get_class($object), $classProperties);
        /** @var ReflectionProperty $classProperty */
        foreach ($classProperties as $fieldClassProperty) {
            if ($fieldClassProperty->name == $fieldName) {
                $fieldValue = $fieldClassProperty->getValue($object);
                break;
            }
        }

        if (isset($fieldValue->___orm_initialized) && ($fieldValue->___orm_initialized)) {
            return $fieldValue;
        } else {
            if ($fieldValue !== null) {
                $entityManager = new EntityManager();
                $classConfiguration = $entityManager->configurationLoader->loadClassConfiguration(get_class($fieldValue));
                $idFieldName = ObjectMapper::getIdFieldName($classConfiguration);

                $classProperties = [];
                ObjectMapper::getClassProperties(get_class($fieldValue), $classProperties);

                /** @var ReflectionProperty $classProperty */
                foreach ($classProperties as $classProperty) {
                    if ($classProperty->name == $idFieldName) {
                        $objectId = $classProperty->getValue($fieldValue);

                        if ($objectId === null) {
                            return $fieldValue;
                        } else {
                            $propertyObject = $entityManager->find(get_class($fieldValue), $objectId);

                            if ($propertyObject !== null) {
                                $fieldClassProperty->setValue($object, $propertyObject);
                            }

                            return $propertyObject;
                        }
                    }
                }
            }
        }

        return null;
    }
}
