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

use Exception;
use ReflectionProperty;

class LazyCollection {

    public object $parent;
    public string $class;
    public string $joiningField;
    public string $fieldName;

    public function __construct(object $parent, string $class, string $joiningField, string $fieldName)
    {
        $this->parent = $parent;
        $this->class = $class;
        $this->joiningField = $joiningField;
        $this->fieldName = $fieldName;
    }

    public function getCollection() : Collection
    {
        $entityManager = new EntityManager();
        $classConfiguration = $entityManager->configurationLoader->loadClassConfiguration(get_class($this->parent));
        $idFieldName = ObjectMapper::getIdFieldName($classConfiguration);

        $classProperties = [];
        ObjectMapper::getClassProperties(get_class($this->parent), $classProperties, [$idFieldName, $this->fieldName]);

        $fieldData = $classConfiguration['fields'][$this->fieldName];

        /** @var ReflectionProperty $idProperty */
        $idProperty = $classProperties[$idFieldName];
        /** @var ReflectionProperty $fieldProperty */
        $fieldProperty = $classProperties[$this->fieldName];

        if (isset($fieldData['relatedObjectField']) || isset($fieldData['joiningClass'])) {
            if (isset($fieldData['relatedObjectField']) && !isset($fieldData['joiningClass'])) {
                throw new Exception('Field "joiningClass" is required when field "relatedObjectField" is defined in entity configuration (orm file)');
            } elseif (!isset($fieldData['relatedObjectField']) && isset($fieldData['joiningClass'])) {
                throw new Exception('Field "relatedObjectField" is required when field "joiningClass" is defined in entity configuration (orm file)');
            } else {
                $collection = new Collection($this->class);
                $parentId = $idProperty->getValue($this->parent);

                $elements = $entityManager->findForParent($fieldData['joiningClass'], [$fieldData['joiningField'] => $parentId], $fieldData['joiningField'], $this->parent);

                $getter = 'get' . $fieldData['relatedObjectField'];
                foreach ($elements as $element) {
                    $collection->add($element->$getter());
                }

                $fieldProperty->setValue($this->parent, $collection);
            }
        } else {
            $parentId = $idProperty->getValue($this->parent);

            $elements = $entityManager->findForParent($this->class, [$this->joiningField => $parentId], $this->joiningField, $this->parent);
            $collection = new Collection($this->class);
            $collection->setCollectionArray($elements);
            $collection->setRecordsCount(count($elements));

            $fieldProperty->setValue($this->parent, $collection);
        }

        return $collection;
    }
}
