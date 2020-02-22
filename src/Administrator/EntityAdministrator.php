<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Administrator;

use Doctrine\ORM\EntityManagerInterface;
use Lyssal\Doctrine\Orm\Exception\OrmException;
use Lyssal\Doctrine\Orm\QueryBuilder;
use Lyssal\Doctrine\Orm\Repository\EntityRepository;
use Lyssal\Entity\Setter\PropertySetter;
use Traversable;

/**
 * @see \Lyssal\Doctrine\Orm\Administrator\EntityAdministratorInterface
 */
class EntityAdministrator implements EntityAdministratorInterface
{
    /**
     * @var array|null The default orderBy parameter
     */
    public static $DEFAULT_ORDER_BY = null;


    /**
     * @var \Doctrine\ORM\EntityManagerInterface The Doctrine entity manager
     */
    protected $entityManager;

    /**
     * @var \Lyssal\Doctrine\Orm\Repository\EntityRepository The entity repository
     */
    protected $repository;

    /**
     * @var ?string The entity class
     */
    protected $class;


    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager The entity manager
     * @param ?string                              $class         The entity class
     */
    public function __construct(EntityManagerInterface $entityManager, ?string $class = null)
    {
        $this->entityManager = $entityManager;
        $this->class = $class;

        $this->repository = $this->entityManager->getRepository($this->getClass());
    }


    /**
     * {@inheritDoc}
     */
    public function getRepository(): EntityRepository
    {
        return $this->repository;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the entity class is not found
     */
    public function getClass(): string
    {
        if (null === $this->class) {
            throw new OrmException('You have to inject the $class property or redefine the getClass() method.');
        }

        return $this->class;
    }


    /**
     * {@inheritDoc}
     */
    public function findBy(array $conditions, array $orderBy = null, $limit = null, $offset = null, array $extras = array()): array
    {
        if (null === $orderBy) {
            $orderBy = static::$DEFAULT_ORDER_BY;
        }

        return $this->getRepository()->getQueryBuilderFindBy($conditions, $orderBy, $limit, $offset, $extras)->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findLikeBy(array $conditions, array $orderBy = null, $limit = null, $offset = null): array
    {
        if (null === $orderBy) {
            $orderBy = static::$DEFAULT_ORDER_BY;
        }

        $likes = array(QueryBuilder::AND_WHERE => array());
        foreach ($conditions as $i => $condition) {
            $likes[QueryBuilder::AND_WHERE][] = array(QueryBuilder::WHERE_LIKE => array($i => $condition));
        }

        return $this->getRepository()->getQueryBuilderFindBy($likes, $orderBy, $limit, $offset)->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBy(array $conditions, array $orderBy = null, array $extras = array())
    {
        if (null === $orderBy) {
            $orderBy = static::$DEFAULT_ORDER_BY;
        }

        return $this->getRepository()->getQueryBuilderFindBy($conditions, $orderBy, 1, null, $extras)->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneById($id, $extras = array())
    {
        if (count($extras) > 0) {
            $identifierFieldName = $this->getSingleIdentifierFieldName();
            return $this->getRepository()->getQueryBuilderFindBy(array($identifierFieldName => $id), null, null, null, $extras)->getQuery()->getOneOrNullResult();
        }

        return $this->entityManager->find($this->getClass(), $id);
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(array $orderBy = null): array
    {
        if (null === $orderBy) {
            $orderBy = static::$DEFAULT_ORDER_BY;
        }

        return $this->getRepository()->findBy([], $orderBy);
    }


    /**
     * {@inheritDoc}
     */
    public function findByKeyedById(array $conditions, array $orderBy = null, $limit = null, $offset = null, $extras = array()): array
    {
        return $this->getEntitiesKeyedById($this->findBy($conditions, $orderBy, $limit, $offset, $extras));
    }

    /**
     * {@inheritDoc}
     */
    public function findLikeByKeyedById(array $conditions, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->getEntitiesKeyedById($this->findLikeBy($conditions, $orderBy, $limit, $offset));
    }

    /**
     * {@inheritDoc}
     */
    public function findAllKeyedById(array $orderBy = null): array
    {
        return $this->getEntitiesKeyedById($this->findAll($orderBy));
    }

    /**
     * {@inheritDoc}
     */
    public function getEntitiesKeyedById(array $entities): array
    {
        if (!is_array($entities) && !($entities instanceof Traversable)) {
            throw new OrmException('The entities parameter must be an array or a Traversable.');
        }

        $class = $this->getClass();
        $identifier = $this->getSingleIdentifierFieldName();
        $identifierGetter = 'get'.ucfirst($identifier);
        if (!method_exists($class, $identifierGetter)) {
            throw new OrmException('The entity "'.$class.'" does not have the "'.$identifierGetter.'()" method.');
        }

        $entitiesById = [];
        foreach ($entities as $entity) {
            if (!($entity instanceof $class)) {
                throw new OrmException('All the entities must be objects of type "'.$class.'" (type "'.(is_object($entity) ? get_class($entity) : gettype($entity)).'" found).');
            }
            $entitiesById[$entity->$identifierGetter()] = $entity;
        }

        return $entitiesById;
    }


    /**
     * {@inheritDoc}
     */
    public function count(array $conditions = []): int
    {
        return $this->getRepository()->count($conditions);
    }

    /**
     * {@inheritDoc}
     */
    public function create($propertyValues = [])
    {
        $class = $this->getClass();
        $entity = new $class;

        $entityGetter = new PropertySetter($entity);
        return $entityGetter->set($propertyValues);
    }

    /**
     * {@inheritDoc}
     */
    public function save($oneOrManyEntities): void
    {
        $this->persist($oneOrManyEntities);
        $this->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function persist($oneOrManyEntities): void
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
     * {@inheritDoc}
     */
    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->entityManager->clear($this->getClass());
    }

    /**
     * {@inheritDoc}
     */
    public function detach($entity): void
    {
        $this->entityManager->detach($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($oneOrManyEntities): void
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
     * {@inheritDoc}
     */
    public function delete($oneOrManyEntities): void
    {
        $this->remove($oneOrManyEntities);
        $this->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function removeAll($initAutoIncrement = false): void
    {
        $this->remove($this->findAll());
        if ($initAutoIncrement) {
            $this->initAutoIncrement();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAll($initAutoIncrement = false): void
    {
        $this->removeAll($initAutoIncrement);
        $this->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function exists($entity): bool
    {
        $class = $this->getClass();

        if (!($entity instanceof $class)) {
            throw new OrmException('The entity must be an object of type "'.$class.'" (type "'.(is_object($entity) ? get_class($entity) : gettype($entity)).'" found).');
        }

        foreach ($this->getIdentifierFieldNames() as $identifierFieldName) {
            $identifierGetter = 'get'.ucfirst($identifierFieldName);
            if (!method_exists($class, $identifierGetter)) {
                throw new OrmException('The entity "'.$class.'" does not have the "'.$identifierGetter.'()" method.');
            }
            if (null === call_user_func_array(array($entity, $identifierGetter), array())) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function truncate($initAutoIncrement = false): void
    {
        $this->entityManager->getConnection()->prepare('TRUNCATE TABLE '.$this->getTableName())->execute();
        if ($initAutoIncrement) {
            $this->initAutoIncrement();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function initAutoIncrement(): void
    {
        $this->setAutoIncrement(1);
    }

    /**
     * {@inheritDoc}
     */
    public function setAutoIncrement($autoIncrementValue): void
    {
        $this->entityManager->getConnection()->prepare('ALTER TABLE '.$this->getTableName().' auto_increment = '.$autoIncrementValue)->execute();
    }


    /**
     * {@inheritDoc}
     */
    public function getTableName(): string
    {
        return $this->entityManager->getMetadataFactory()->getMetadataFor($this->repository->getClassName())->getTableName();
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFieldNames(): array
    {
        return $this->getRepository()->getIdentifierFieldNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getSingleIdentifierFieldName(): string
    {
        return $this->getRepository()->getSingleIdentifierFieldName();
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName): bool
    {
        return $this->getRepository()->hasField($fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAssociation($fieldName): bool
    {
        return $this->getRepository()->hasAssociation($fieldName);
    }
}
