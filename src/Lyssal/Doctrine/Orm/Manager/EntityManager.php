<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Lyssal\Doctrine\Orm\Exception\OrmException;
use Lyssal\Doctrine\Orm\QueryBuilder;
use Lyssal\Entity\Setter\PropertySetter;
use Traversable;

/**
 * A base manager to use with the LyssalDoctrineOrm entity repository.
 */
class EntityManager
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface The Doctrine entity manager
     */
    protected $entityManager;

    /**
     * @var \Lyssal\Doctrine\Orm\Repository\EntityRepository The entity repository
     */
    protected $repository;

    /**
     * @var string The entity class
     */
    protected $class;


    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager The entity manager
     * @param string                               $class         The entity class
     */
    public function __construct(EntityManagerInterface $entityManager, $class)
    {
        $this->entityManager = $entityManager;
        $this->class = $class;

        $this->repository = $this->entityManager->getRepository($this->class);
    }


    /**
     * Get the entity repository.
     *
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository The repository
     */
    public function getRepository()
    {
        return $this->repository;
    }


    /**
     * Get entities.
     *
     * @param array    $conditions The conditions of the search
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @param array    $extras     The extras (see the documentation for more informations)
     * @return array The entities
     */
    public function findBy(array $conditions, array $orderBy = null, $limit = null, $offset = null, array $extras = array())
    {
        return $this->getRepository()->getQueryBuilderFindBy($conditions, $orderBy, $limit, $offset, $extras)->getQuery()->getResult();
    }

    /**
     * Get entities using LIKE instead of = (equal) in conditions (do not forget the % if you want to use it).
     *
     * @param array    $conditions The conditions of the search using LIKE
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @return array The entities
     */
    public function findLikeBy(array $conditions, array $orderBy = null, $limit = null, $offset = null)
    {
        $likes = array(QueryBuilder::AND_WHERE => array());
        foreach ($conditions as $i => $condition) {
            $likes[QueryBuilder::AND_WHERE][] = array(QueryBuilder::WHERE_LIKE => array($i => $condition));
        }

        return $this->getRepository()->getQueryBuilderFindBy($likes, $orderBy, $limit, $offset)->getQuery()->getResult();
    }

    /**
     * Get one entity.
     *
     * @param array $conditions The conditions of the search
     * @param array $orderBy    The order of the results
     * @param array $extras     The extras
     * @return object|null The entity or NULL if not found
     */
    public function findOneBy(array $conditions, array $orderBy = null, $extras = array())
    {
        if (count($extras) > 0) {
            return $this->getRepository()->getQueryBuilderFindBy($conditions, $orderBy, 1, null, $extras)->getQuery()->getOneOrNullResult();
        }

        return $this->getRepository()->findOneBy($conditions, $orderBy);
    }

    /**
     * Get one entity according to its identifier.
     *
     * @param int|mixed $id     The identifier value
     * @param array     $extras The extras
     * @return object|null The entity of NULL if not found
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     */
    public function findOneById($id, $extras = array())
    {
        $identifierFieldName = $this->getSingleIdentifierFieldName();

        if (count($extras) > 0) {
            return $this->getRepository()->getQueryBuilderFindBy(array($identifierFieldName => $id), null, 1, null, $extras)->getQuery()->getOneOrNullResult();
        }

        return $this->entityManager->find($this->class, $id);
    }

    /**
     * Get all the entities.
     *
     * @param array $orderBy The order of the results
     *
     * @return object[] The entities
     */
    public function findAll(array $orderBy = null)
    {
        return $this->getRepository()->findBy([], $orderBy);
    }


    /**
     * Get the entities keyed by their identifier.
     *
     * @param array    $conditions The conditions of the search using LIKE
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @param array    $extras     The extras
     * @return object[] The entities
     */
    public function findByKeyedById(array $conditions, array $orderBy = null, $limit = null, $offset = null, $extras = array())
    {
        return $this->getEntitiesKeyedById($this->findBy($conditions, $orderBy, $limit, $offset, $extras));
    }

    /**
     * Get the entities using LIKE instead of = (equal) keyed by their identifier.
     *
     * @param array    $conditions The conditions of the search using LIKE
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @return object[] The entities
     */
    public function findLikeByKeyedById(array $conditions, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getEntitiesKeyedById($this->findLikeBy($conditions, $orderBy, $limit, $offset));
    }

    /**
     * Get all the entities keyed by their identifier.
     *
     * @param array $orderBy The order of the results
     * @return object[] The entities
     */
    public function findAllKeyedById(array $orderBy = null)
    {
        return $this->getEntitiesKeyedById($this->findAll($orderBy));
    }

    /**
     * Get an array of entites keyed by their identifier.
     *
     * @param object[] $entities The entities
     * @return object[] The entities keyed by their identifier
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the parameter is not an array or a Traversable
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entities have not the identifier getter method
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If at least one entity is not an instance of the managed class
     */
    public function getEntitiesKeyedById(array $entities)
    {
        if (!is_array($entities) && !($entities instanceof Traversable)) {
            throw new OrmException('The entities parameter must be an array or a Traversable.');
        }

        $identifier = $this->getSingleIdentifierFieldName();
        $identifierGetter = 'get'.ucfirst($identifier);
        if (!method_exists($this->class, $identifierGetter)) {
            throw new OrmException('The entity "'.$this->class.'" does not have the "'.$identifierGetter.'()" method.');
        }

        $entitiesById = [];
        foreach ($entities as $entity) {
            if (!($entity instanceof $this->class)) {
                throw new OrmException('All the entities must be objects of type "'.$this->class.'" (type "'.(is_object($entity) ? get_class($entity) : gettype($entity)).'" found).');
            }
            $entitiesById[$entity->$identifierGetter()] = $entity;
        }

        return $entitiesById;
    }


    /**
     * Get the entities count.
     *
     * @return int The entities count
     */
    public function count()
    {
        return $this->getRepository()->count();
    }

    /**
     * Get a new entity.
     *
     * @param array $propertyValues An associative array with values for each property
     *
     * @return object The new entity
     *
     * @throws \Lyssal\Entity\Exception\EntityException If the setter method is not found
     */
    public function create($propertyValues = [])
    {
        $entity = new $this->class;

        $entityGetter = new PropertySetter($entity);
        return $entityGetter->set($propertyValues);
    }

    /**
     * Save one or many entities and flush.
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function save($oneOrManyEntities)
    {
        $this->persist($oneOrManyEntities);
        $this->flush();
    }

    /**
     * Persist one or many entities.
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function persist($oneOrManyEntities)
    {
        if (is_array($oneOrManyEntities) || $oneOrManyEntities instanceof Traversable) {
            foreach ($oneOrManyEntities as $entity) {
                $this->entityManager->persist($entity);
            }
        } else {
            $this->entityManager->persist($oneOrManyEntities);
        }
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->entityManager->flush();
    }

    /**
     * Clear all the entities.
     */
    public function clear()
    {
        $this->entityManager->clear($this->class);
    }

    /**
     * Remove one or many entities (without flush).
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function remove($oneOrManyEntities)
    {
        if (is_array($oneOrManyEntities) || $oneOrManyEntities instanceof Traversable) {
            foreach ($oneOrManyEntities as $entity) {
                $this->entityManager->remove($entity);
            }
        } else {
            $this->entityManager->remove($oneOrManyEntities);
        }
    }

    /**
     * Delete one or many entities (with flush).
     *
     * @param object|object[] $oneOrManyEntities One or many entities
     */
    public function delete($oneOrManyEntities)
    {
        $this->remove($oneOrManyEntities);
        $this->flush();
    }

    /**
     * Remove all the entities.
     *
     * @param bool $initAutoIncrement Init the AUTO_INCREMENT at 1
     */
    public function removeAll($initAutoIncrement = false)
    {
        $this->remove($this->findAll());
        if ($initAutoIncrement) {
            $this->initAutoIncrement();
        }
    }

    /**
     * Delete all the entities.
     *
     * @param bool $initAutoIncrement Init the AUTO_INCREMENT at 1
     */
    public function deleteAll($initAutoIncrement = false)
    {
        $this->removeAll($initAutoIncrement);
        $this->flush();
    }

    /**
     * Verify if the entity exists by checking that its identifiers are not NULL.
     * Please not that this method always will return true if the entity has many primary foreign keys even if the entity has not been saved.
     *
     * @param object $entity The entity
     * @return bool If the entity exists
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entity is not an instance of the managed class
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entity have not the identifier getter methods
     */
    public function exists($entity)
    {
        if (!($entity instanceof $this->class)) {
            throw new OrmException('The entity must be an object of type "'.$this->class.'" (type "'.(is_object($entity) ? get_class($entity) : gettype($entity)).'" found).');
        }

        foreach ($this->getIdentifierFieldNames() as $identifierFieldName) {
            $identifierGetter = 'get'.ucfirst($identifierFieldName);
            if (!method_exists($this->class, $identifierGetter)) {
                throw new OrmException('The entity "'.$this->class.'" does not have the "'.$identifierGetter.'()" method.');
            }
            if (null === call_user_func_array(array($entity, $identifierGetter), array())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Perform a TRUNCATE on the data table.
     * Note this method will not success if the table has a constraint.
     *
     * @param bool $initAutoIncrement Init the AUTO_INCREMENT at 1
     * @throws \Doctrine\DBAL\DBALException If the query failed
     */
    public function truncate($initAutoIncrement = false)
    {
        $this->entityManager->getConnection()->prepare('TRUNCATE TABLE '.$this->getTableName())->execute();
        if ($initAutoIncrement) {
            $this->initAutoIncrement();
        }
    }

    /**
     * Init the AUTO_INCREMENT to 1.
     */
    public function initAutoIncrement()
    {
        $this->setAutoIncrement(1);
    }

    /**
     * Specify a new AUTO_INCREMENT.
     *
     * @param int $autoIncrementValue The value of the AUTO_INCREMENT
     * @throws \Doctrine\DBAL\DBALException If the query failed
     */
    public function setAutoIncrement($autoIncrementValue)
    {
        $this->entityManager->getConnection()->prepare('ALTER TABLE '.$this->getTableName().' auto_increment = '.$autoIncrementValue)->execute();
    }


    /**
     * Get the name of the database table of the managed entity.
     *
     * @return string The table name
     */
    public function getTableName()
    {
        return $this->entityManager->getMetadataFactory()->getMetadataFor($this->repository->getClassName())->getTableName();
    }

    /**
     * Get the identifier field names.
     *
     * @return string[] The identifier field names
     */
    public function getIdentifierFieldNames()
    {
        return $this->getRepository()->getIdentifierFieldNames();
    }

    /**
     * Get the single identifier field name of the entity.
     *
     * @return string The identifier field name
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     */
    public function getSingleIdentifierFieldName()
    {
        return $this->getRepository()->getSingleIdentifierFieldName();
    }

    /**
     * Get if the entity has the field.
     *
     * @param string $fieldName The field name
     * @return bool If the field exists
     */
    public function hasField($fieldName)
    {
        return $this->getRepository()->hasField($fieldName);
    }

    /**
     * Get if the entity has the association.
     *
     * @param string $fieldName The association's field name
     * @return bool If the association exists
     */
    public function hasAssociation($fieldName)
    {
        return $this->getRepository()->hasAssociation($fieldName);
    }
}
