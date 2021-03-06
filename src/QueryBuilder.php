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

class QueryBuilder
{
    private string $className;
    private string $tableName;
    private string|null $tableAlias;
    private array $fields = [];
    private array $joins = [];
    private array $whereConditions = [];
    private array $havingConditions = [];
    private array $groupBy = [];
    private QuerySorting|null $sorting = null;
    private string|int|null $limit = null;
    private string|int|null $offset = null;
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager, string $className)
    {
        $this->entityManager = $entityManager;
        $this->className = $className;
    }

    public function clearFields() : void
    {
        $this->fields = [];
    }

    public function clear() : void
    {
        $this->fields = [];
        $this->joins = [];
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManager $entityManager): self
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    public function from(string $tableName, ?string $tableAlias) : self
    {
        $this->tableName = $tableName;
        $this->tableAlias = $tableAlias;
        return $this;
    }

    public function setTableAlias(?string $tableAlias): self
    {
        $this->tableAlias = $tableAlias;
        return $this;
    }

    public function getTableAlias() : string|null
    {
        return $this->tableAlias;
    }

    public function getFields() : array
    {
        return $this->fields;
    }

    public function getJoins() : array
    {
        return $this->joins;
    }

    public function getWhere() : array
    {
        return $this->whereConditions;
    }

    public function getHaving() : array
    {
        return $this->havingConditions;
    }

    public function getGroupBy() : array
    {
        return $this->groupBy;
    }

    public function getSorting() : ?QuerySorting
    {
        return $this->sorting;
    }

    public function getLimit() : ?int
    {
        return $this->limit;
    }

    public function getOffset() : ?int
    {
        return $this->offset;
    }

    public function addField($fieldName, string $alias = null): QueryBuilder
    {
        if (($fieldName instanceof self) && ($alias == '')) {
            throw new Exception('If you pass a value that is a class "' . self::class . '" object, you must pass its alias.');
        } else {
            $queryField['fieldName'] = $fieldName;
        }

        $queryField['alias'] = $alias;

        $this->fields[] = $queryField;

        return $this;
    }

    public function addJoin($repositoryOrTableName, string $alias, QueryCondition $queryCondition): QueryBuilder
    {
        $this->joins[$alias] = [
            'table' => ($repositoryOrTableName instanceof Repository) ? $repositoryOrTableName->getTableName() : $repositoryOrTableName,
            'alias' => $alias,
            'queryCondition' => $queryCondition
        ];

        return $this;
    }

    public function addWhere(?QueryCondition $queryCondition): QueryBuilder
    {
        if (null !== $queryCondition) {
            $this->whereConditions[] = $queryCondition;
        }
        return $this;
    }

    public function addHaving(?QueryCondition $queryCondition): QueryBuilder
    {
        if (null !== $queryCondition) {
            $this->havingConditions[] = $queryCondition;
        }
        return $this;
    }

    public function addGroupBy($fieldName): QueryBuilder
    {
        $this->groupBy[] = $fieldName;
        return $this;
    }

    public function setSorting(?QuerySorting $sorting): QueryBuilder
    {
        $this->sorting = $sorting;
        return $this;
    }

    public function setLimit(?int $limit): QueryBuilder
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOffset(?int $offset): QueryBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    public function getTableResult(HydrationMode $hydrationMode = HydrationMode::Object): array
    {
        $dbAdapter = $this->entityManager->getDbConnection()->getDbAdapter();
        $query = $dbAdapter->getSelectQuery($this);

        $parameters = [];
        /** @var QueryCondition $queryCondition */
        foreach ($this->getWhere() as $queryCondition) {
            $parameters = array_merge($parameters, $queryCondition->parameters);
        }

        /** @var QueryCondition $queryCondition */
        foreach ($this->getHaving() as $queryCondition) {
            $parameters = array_merge($parameters, $queryCondition->parameters);
        }

        foreach ($this->getJoins() as $join) {
            foreach ($join['queryCondition'] as $queryConditions) {
                /** @var QueryCondition $queryCondition */
                foreach ($queryConditions as $queryCondition) {
                    if (isset($queryCondition['condition'])) {
                        $parameters = array_merge($parameters, $queryCondition['condition']->parameters);
                    }
                }
            }
        }

        $table = $this->entityManager->getDbConnection()->getTable($query, $parameters);

        switch ($hydrationMode) {
            case HydrationMode::Array:
                return $table;
            case HydrationMode::Object:
                $resultTable = [];

                foreach ($table as $row) {
                    $entity = ObjectFactory::create($this->className, $this->entityManager);
                    $resultTable[] = ObjectMapper::mapEntity($entity, $row, $this->entityManager);
                }

                return $resultTable;
        }

        throw new Exception('Unexpected error');
    }

    public function getCount(): int
    {
        $queryBuilderWithoutLimit = clone $this;
        $queryBuilderWithoutLimit->clearFields();
        $queryBuilderWithoutLimit->setLimit(null);
        $queryBuilderWithoutLimit->setOffset(null);
        $queryBuilderWithoutLimit->addField('COUNT(*) as count');


        $dbAdapter = $this->entityManager->getDbConnection()->getDbAdapter();
        $query = $dbAdapter->getSelectQuery($queryBuilderWithoutLimit);

        $parameters = [];
        /** @var QueryCondition $queryCondition */
        foreach ($this->getWhere() as $queryCondition) {
            $parameters = array_merge($parameters, $queryCondition->parameters);
        }

        /** @var QueryCondition $queryCondition */
        foreach ($this->getHaving() as $queryCondition) {
            $parameters = array_merge($parameters, $queryCondition->parameters);
        }

        foreach ($this->getJoins() as $join) {
            foreach ($join['queryCondition'] as $queryConditions) {
                /** @var QueryCondition $queryCondition */
                foreach ($queryConditions as $queryCondition) {
                    if (isset($queryCondition['condition'])) {
                        $parameters = array_merge($parameters, $queryCondition['condition']->parameters);
                    }
                }
            }
        }

        return $this->entityManager->getDbConnection()->getValue($query, $parameters);
    }

    public function getSingleResult(HydrationMode $hydrationMode = HydrationMode::Object): array|object|null
    {
        $dbAdapter = $this->entityManager->getDbConnection()->getDbAdapter();
        $query = $dbAdapter->getSelectQuery($this);

        $parameters = [];
        /** @var QueryCondition $queryCondition */
        foreach ($this->getWhere() as $queryCondition) {
            $parameters = array_merge($parameters, $queryCondition->parameters);
        }

        $row = $this->entityManager->getDbConnection()->getSingleRow($query, $parameters);

        switch ($hydrationMode) {
            case HydrationMode::Array:
                return $row;
            case HydrationMode::Object:

                if ($row === null) {
                    return null;
                } else {
                    $entity = ObjectFactory::create($this->className, $this->entityManager);
                    return ObjectMapper::mapEntity($entity, $row, $this->entityManager);
                }
        }

        throw new Exception('Unexpected error');
    }

    public function getValue(): ?string
    {
        $dbAdapter = $this->entityManager->getDbConnection()->getDbAdapter();
        $query = $dbAdapter->getSelectQuery($this);

        $parameters = [];
        /** @var QueryCondition $queryCondition */
        foreach ($this->getWhere() as $queryCondition) {
            $parameters = array_merge($parameters, $queryCondition->parameters);
        }

        return $this->entityManager->getDbConnection()->getValue($query, $parameters);
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
