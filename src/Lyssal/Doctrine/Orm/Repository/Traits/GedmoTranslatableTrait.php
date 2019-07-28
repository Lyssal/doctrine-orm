<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Repository\Traits;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;

/**
 * A trait to use GedmoTranslatable functionalities with the EntityRepository.
 */
trait GedmoTranslatableTrait
{
    /**
     * Get one translated result or NULL if not found.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder  The query builder
     * @param string                     $locale        The locale
     * @param int                        $hydrationMode The hydration mode
     * @return mixed|null The result
     * @throws \Doctrine\ORM\NonUniqueResultException If more than one result found
     */
    public function getOneOrNullTranslatedResult(QueryBuilder $queryBuilder, $locale, $hydrationMode = null)
    {
        return $this->getTranslatedQuery($queryBuilder, $locale)->getOneOrNullResult($hydrationMode);
    }

    /**
     * Get the translated result.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder  The query builder
     * @param string                     $locale        The locale
     * @param int                        $hydrationMode The hydration mode
     * @return mixed The result
     */
    public function getTranslatedResult(QueryBuilder $queryBuilder, $locale, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getTranslatedQuery($queryBuilder, $locale)->getResult($hydrationMode);
    }

    /**
     * Get the translated result in an array.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param string                     $locale       The locale
     * @return array The result
     */
    public function getArrayTranslatedResult(QueryBuilder $queryBuilder, $locale)
    {
        return $this->getTranslatedQuery($queryBuilder, $locale)->getArrayResult();
    }

    /**
     * Get a single translated result.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder  The query builder
     * @param string                     $locale        The locale
     * @param int                        $hydrationMode The hydration mode
     * @return mixed The single result
     * @throws \Doctrine\ORM\NonUniqueResultException If more than one result found
     * @throws \Doctrine\ORM\NoResultException If no result found
     */
    public function getSingleTranslatedResult(QueryBuilder $queryBuilder, $locale, $hydrationMode = null)
    {
        return $this->getTranslatedQuery($queryBuilder, $locale)->getSingleResult($hydrationMode);
    }

    /**
     * Get a scalar translated result.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param string                     $locale       The locale
     * @return mixed The scalar result
     */
    public function getScalarTranslatedResult(QueryBuilder $queryBuilder, $locale)
    {
        return $this->getTranslatedQuery($queryBuilder, $locale)->getScalarResult();
    }

    /**
     * Get a single scalar translated result.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param string                     $locale       The locale
     * @return mixed The single scalar result
     * @throws \Doctrine\ORM\NonUniqueResultException If more than one result found
     * @throws \Doctrine\ORM\NoResultException If no result found
     */
    public function getSingleScalarTranslatedResult(QueryBuilder $queryBuilder, $locale)
    {
        return $this->getTranslatedQuery($queryBuilder, $locale)->getSingleScalarResult();
    }

    /**
     * Get the translated query.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param string                     $locale       The locale
     * @return \Doctrine\ORM\Query The query
     */
    public function getTranslatedQuery(QueryBuilder $queryBuilder, $locale)
    {
        $query = $queryBuilder->getQuery();
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

        return $query;
    }
}
