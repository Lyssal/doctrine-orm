<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Administrator;

use Lyssal\Doctrine\Orm\Repository\EntityRepository;

/**
 * An administrator to manage Doctrine entities.
 * The repository entity has to extends the LyssalDoctrineOrm entity repository.
 */
interface EntityAdministratorInterface
{
    /**
     * Get the entity repository.
     *
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository The repository
     */
    public function getRepository(): EntityRepository;

    /**
     * Get the entity class.
     *
     * @return string The class
     */
    public function getClass(): string;

    /**
     * Get entities.
     *
     * @param array    $conditions The conditions of the search
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @param array    $extras     The extras (see the documentation for more informations)
     * 
     * @return array The entities
     */
    public function findBy(array $conditions, array $orderBy = null, $limit = null, $offset = null, array $extras = array()): array;

    /**
     * Get entities using LIKE instead of = (equal) in conditions (do not forget the % if you want to use it).
     *
     * @param array    $conditions The conditions of the search using LIKE
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * 
     * @return array The entities
     */
    public function findLikeBy(array $conditions, array $orderBy = null, $limit = null, $offset = null): array;

    /**
     * Get one entity.
     * Some extras parameters are not managed (as JOINs) because of the LIMIT 1.
     *
     * @param array $conditions The conditions of the search
     * @param array $orderBy    The order of the results
     * @param array $extras     The extras (see the documentation for more informations)
     *
     * @return object|null The entity or NULL if not found
     */
    public function findOneBy(array $conditions, array $orderBy = null, array $extras = array());

    /**
     * Get one entity according to its identifier.
     *
     * @param int|mixed $id     The identifier value
     * @param array     $extras The extras
     * 
     * @return object|null The entity of NULL if not found
     * 
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     */
    public function findOneById($id, $extras = array());

    /**
     * Get all the entities.
     *
     * @param array $orderBy The order of the results
     *
     * @return object[] The entities
     */
    public function findAll(array $orderBy = null): array;


    /**
     * Get the entities keyed by their identifier.
     *
     * @param array    $conditions The conditions of the search using LIKE
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @param array    $extras     The extras
     * 
     * @return object[] The entities
     */
    public function findByKeyedById(array $conditions, array $orderBy = null, $limit = null, $offset = null, $extras = array()): array;

    /**
     * Get the entities using LIKE instead of = (equal) keyed by their identifier.
     *
     * @param array    $conditions The conditions of the search using LIKE
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * 
     * @return object[] The entities
     */
    public function findLikeByKeyedById(array $conditions, array $orderBy = null, $limit = null, $offset = null): array;

    /**
     * Get all the entities keyed by their identifier.
     *
     * @param array $orderBy The order of the results
     * 
     * @return object[] The entities
     */
    public function findAllKeyedById(array $orderBy = null): array;

    /**
     * Get an array of entites keyed by their identifier.
     *
     * @param object[] $entities The entities
     * 
     * @return object[] The entities keyed by their identifier
     * 
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the parameter is not an array or a Traversable
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entities have not the identifier getter method
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If at least one entity is not an instance of the managed class
     */
    public function getEntitiesKeyedById(array $entities): array;


    /**
     * Get the entities count.
     *
     * @param array $conditions The conditions
     *
     * @return int The entities count
     */
    public function count(array $conditions = []): int;

    /**
     * Get a new entity.
     *
     * @param array $propertyValues An associative array with values for each property
     *
     * @return object The new entity
     *
     * @throws \Lyssal\Entity\Exception\EntityException If the setter method is not found
     */
    public function create($propertyValues = []);

    /**
     * Save one or many entities and flush.
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function save($oneOrManyEntities): void;

    /**
     * Persist one or many entities.
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function persist($oneOrManyEntities): void;

    /**
     * Flush.
     */
    public function flush(): void;

    /**
     * Clear all the entities.
     */
    public function clear(): void;

    /**
     * Detach the entity.
     *
     * @param object $entity The entity
     */
    public function detach($entity): void;

    /**
     * Remove one or many entities (without flush).
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function remove($oneOrManyEntities): void;

    /**
     * Delete one or many entities (with flush).
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function delete($oneOrManyEntities): void;

    /**
     * Remove all the entities.
     *
     * @param bool $initAutoIncrement Init the AUTO_INCREMENT at 1
     */
    public function removeAll($initAutoIncrement = false): void;

    /**
     * Delete all the entities.
     *
     * @param bool $initAutoIncrement Init the AUTO_INCREMENT at 1
     */
    public function deleteAll($initAutoIncrement = false): void;

    /**
     * Verify if the entity exists by checking that its identifiers are not NULL.
     * Note that this method always will return true if the entity has many primary foreign keys even if the entity has not been saved.
     *
     * @param object $entity The entity
     * 
     * @return bool If the entity exists
     * 
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entity is not an instance of the managed class
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entity have not the identifier getter methods
     */
    public function exists($entity): bool;

    /**
     * Perform a TRUNCATE on the data table.
     * Note this method will not success if the table has a constraint.
     *
     * @param bool $initAutoIncrement Init the AUTO_INCREMENT at 1
     * 
     * @throws \Doctrine\DBAL\DBALException If the query failed
     */
    public function truncate($initAutoIncrement = false): void;

    /**
     * Init the AUTO_INCREMENT to 1.
     */
    public function initAutoIncrement(): void;

    /**
     * Specify a new AUTO_INCREMENT.
     *
     * @param int $autoIncrementValue The value of the AUTO_INCREMENT
     * 
     * @throws \Doctrine\DBAL\DBALException If the query failed
     */
    public function setAutoIncrement($autoIncrementValue): void;


    /**
     * Get the name of the database table of the managed entity.
     *
     * @return string The table name
     */
    public function getTableName(): string;

    /**
     * Get the identifier field names.
     *
     * @return string[] The identifier field names
     */
    public function getIdentifierFieldNames(): array;

    /**
     * Get the single identifier field name of the entity.
     *
     * @return string The identifier field name
     * 
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     */
    public function getSingleIdentifierFieldName(): string;

    /**
     * Get if the entity has the field.
     *
     * @param string $fieldName The field name
     * 
     * @return bool If the field exists
     */
    public function hasField($fieldName): bool;

    /**
     * Get if the entity has the association.
     *
     * @param string $fieldName The association's field name
     * 
     * @return bool If the association exists
     */
    public function hasAssociation($fieldName): bool;
}
