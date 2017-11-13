<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Repository;

use Doctrine\ORM\EntityRepository as DoctrineEntityRepository;
use Lyssal\Doctrine\Orm\Repository\Traits\QueryBuilderTrait;

/**
 * A Doctrine entity repository.
 */
class EntityRepository extends DoctrineEntityRepository
{
    use QueryBuilderTrait;


    /**
     * Get the identifier field names.
     *
     * @return string[] The identifier field names
     */
    public function getIdentifierFieldNames()
    {
        return $this->getClassMetadata()->getIdentifier();
    }

    /**
     * Return the single identifier field name of the entity.
     *
     * @return string The identifier field name
     * @throws \Doctrine\ORM\Mapping\MappingException If the identifier is not unique
     */
    public function getSingleIdentifierFieldName()
    {
        return $this->getClassMetadata()->getSingleIdentifierFieldName();
    }

    /**
     * Get if the entity has the field.
     *
     * @param string $fieldName The field name
     * @return bool If the field exists
     */
    public function hasField($fieldName)
    {
        foreach ($this->_em->getMetadataFactory()->getAllMetadata() as $entityMetadata) {
            if ($entityMetadata->hasField($fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get if the entity has the association.
     *
     * @param string $fieldName The association's field name
     * @return bool If the association exists
     */
    public function hasAssociation($fieldName)
    {
        foreach ($this->_em->getMetadataFactory()->getAllMetadata() as $entityMetadata) {
            if ($entityMetadata->hasAssociation($fieldName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the entities count.
     *
     * @param array $conditions The conditions
     *
     * @return int The entities count
     */
    public function count(array $conditions = [])
    {
        $class = $this->getClassName();

        $queryBuilder =
            $this->_em->createQueryBuilder()
            ->select('COUNT(entity)')
            ->from($class, 'entity')
        ;

        $this
            ->processQueryBuilderConditions($queryBuilder, $conditions)
            ->processQueryBuilderHavings($queryBuilder, $conditions)
        ;

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
