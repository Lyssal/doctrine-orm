<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Repository\Traits;

use Doctrine\ORM\QueryBuilder;
use Lyssal\Doctrine\Orm\Exception\OrmException;
use Lyssal\Doctrine\Orm\QueryBuilder as LyssalQueryBuilder;

/**
 * A trait to use QueryBuilder functionalities with the EntityRepository.
 */
trait QueryBuilderTrait
{
    /**
     * @var int Parameters counter used to have unic parameters in QueryBuilder
     */
    protected static $parametersCounter = 1;


    /**
     * Return the queyr builder for the findBy method.
     *
     * @param array    $conditions The conditions of the search
     * @param array    $orderBy    The order of the results
     * @param int|null $limit      The maximum number of results
     * @param int|null $offset     The offset of the first result
     * @param array    $extras     The extras (see the documentation for more informations)
     * @return \Doctrine\ORM\QueryBuilder The query builder
     */
    public function getQueryBuilderFindBy(array $conditions, array $orderBy = null, $limit = null, $offset = null, array $extras = array())
    {
        $queryBuilder = $this->createQueryBuilder('entity');

        if (null === $orderBy) {
            $orderBy = [];
        }

        $this
            ->processQueryBuilderExtras($queryBuilder, $extras)
            ->processQueryBuilderConditions($queryBuilder, $conditions)
            ->processQueryBuilderHavings($queryBuilder, $conditions)
            ->processQueryBuilderOrderBy($queryBuilder, $orderBy)
            ->processQueryBuilderMaxResults($queryBuilder, $limit)
            ->processQueryBuilderFirstResult($queryBuilder, $offset)
        ;

        return $queryBuilder;
    }

    /**
     * Process the extras of the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param array                      $extras       Extras
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository This
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If a parameter is not an array
     */
    protected function processQueryBuilderExtras(QueryBuilder $queryBuilder, array $extras)
    {
        if (array_key_exists(LyssalQueryBuilder::SELECTS, $extras)) {
            if (!is_array($extras[LyssalQueryBuilder::SELECTS])) {
                throw new OrmException('The SELECTS parameter must be an array.');
            }
            $selects = [];
            foreach ($extras[LyssalQueryBuilder::SELECTS] as $select => $selectAlias) {
                if (is_int($select)) {
                    $selects[] = $selectAlias;
                } else {
                    $selects[] = $this->getCompleteProperty($select).' AS '.$selectAlias;
                }
            }
            $queryBuilder->select($selects);
        }

        if (array_key_exists(LyssalQueryBuilder::LEFT_JOINS, $extras)) {
            if (!is_array($extras[LyssalQueryBuilder::LEFT_JOINS])) {
                throw new OrmException('The LEFT_JOINS parameter must be an array.');
            }
            foreach ($extras[LyssalQueryBuilder::LEFT_JOINS] as $leftJoin => $leftJoinAlias) {
                $queryBuilder->leftJoin($this->getCompleteProperty($leftJoin), $leftJoinAlias);
            }
        }

        if (array_key_exists(LyssalQueryBuilder::INNER_JOINS, $extras)) {
            if (!is_array($extras[LyssalQueryBuilder::INNER_JOINS])) {
                throw new OrmException('The INNER_JOINS parameter must be an array.');
            }
            foreach ($extras[LyssalQueryBuilder::INNER_JOINS] as $innerJoin => $innerJoinAlias) {
                $queryBuilder->innerJoin($this->getCompleteProperty($innerJoin), $innerJoinAlias);
            }
        }

        if (array_key_exists(LyssalQueryBuilder::GROUP_BYS, $extras)) {
            if (!is_array($extras[LyssalQueryBuilder::GROUP_BYS])) {
                throw new OrmException('The GROUP_BYS parameter must be an array.');
            }
            foreach ($extras[LyssalQueryBuilder::GROUP_BYS] as $groupBy) {
                if (array_key_exists(LyssalQueryBuilder::SELECTS, $extras) && in_array($groupBy, array_values($extras[LyssalQueryBuilder::SELECTS]), true)) {
                    $queryBuilder->addGroupBy($groupBy);
                } else {
                    $queryBuilder->addGroupBy($this->getCompleteProperty($groupBy));
                }
            }
        }

        return $this;
    }

    /**
     * Process the conditions of the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param array                      $conditions   The conditions of the search
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository This
     */
    protected function processQueryBuilderConditions(QueryBuilder $queryBuilder, array $conditions)
    {
        foreach ($conditions as $conditionProperty => $conditionValue) {
            if (!$this->conditionIsHaving($conditionProperty)) {
                $queryBuilder->andWhere($this->processQueryBuilderCondition($queryBuilder, $conditionProperty, $conditionValue));
            }
        }

        return $this;
    }

    /**
     * Process a condition in the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder      The query builder
     * @param string|int                 $conditionProperty The condition property name
     * @param string|array|null          $conditionValue    The value(s) of the condition
     * @return mixed The condition
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the condition value is not valid
     */
    protected function processQueryBuilderCondition(QueryBuilder $queryBuilder, $conditionProperty, $conditionValue)
    {
        if (is_int($conditionProperty)) {
            if (!is_array($conditionValue) || count($conditionValue) !== 1) {
                throw new OrmException('The condition value must be an associative array with only one value.');
            }
            foreach ($conditionValue as $condition => $value) {
                return $this->processQueryBuilderCondition($queryBuilder, $condition, $value);
            }
        }

        if (LyssalQueryBuilder::OR_WHERE === $conditionProperty) {
            if (!is_array($conditionValue)) {
                throw new OrmException('The condition value of an OR_WHERE must be an associative array.');
            }
            $conditionsOr = array();
            foreach ($conditionValue as $conditionOrPropriete => $conditionOrValeur) {
                $conditionsOr[] = $this->processQueryBuilderCondition($queryBuilder, $conditionOrPropriete, $conditionOrValeur);
            }
            return call_user_func_array(array($queryBuilder->expr(), 'orX'), $conditionsOr);
        }

        if (LyssalQueryBuilder::AND_WHERE === $conditionProperty) {
            if (!is_array($conditionValue)) {
                throw new OrmException('The condition value of an AND_WHERE must be an associative array.');
            }
            $conditionsAnd = array();
            foreach ($conditionValue as $conditionOrPropriete => $conditionOrValeur) {
                $conditionsAnd[] = $this->processQueryBuilderCondition($queryBuilder, $conditionOrPropriete, $conditionOrValeur);
            }
            return call_user_func_array(array($queryBuilder->expr(), 'andX'), $conditionsAnd);
        }

        if (LyssalQueryBuilder::WHERE_LIKE === $conditionProperty) {
            if (!is_array($conditionValue) || count($conditionValue) != 1) {
                throw new OrmException('The condition value of an WHERE_LIKE must be an associative array with one value.');
            }
            foreach ($conditionValue as $likePropriete => $likeValeur) {
                $conditionValueLabel = $this->addParameterInQueryBuilder($queryBuilder, $likeValeur);
                return $this->getCompleteProperty($likePropriete).' LIKE :'.$conditionValueLabel;
            }
        }

        if (LyssalQueryBuilder::WHERE_IN === $conditionProperty) {
            if (!is_array($conditionValue) || count($conditionValue) != 1) {
                throw new OrmException('The condition value of an WHERE_IN must be an associative array with one value.');
            }
            foreach ($conditionValue as $inPropriete => $inValeur) {
                return call_user_func_array(array($queryBuilder->expr(), 'in'), array($this->getCompleteProperty($inPropriete), $inValeur));
            }
        }

        if (LyssalQueryBuilder::WHERE_NOT_IN === $conditionProperty) {
            if (!is_array($conditionValue) || count($conditionValue) != 1) {
                throw new OrmException('The condition value of an WHERE_NOT_IN must be an associative array with one value.');
            }
            foreach ($conditionValue as $notInPropriete => $notInValeur) {
                return call_user_func_array(array($queryBuilder->expr(), 'notIn'), array($this->getCompleteProperty($notInPropriete), $notInValeur));
            }
        }

        if (in_array($conditionProperty, array(LyssalQueryBuilder::WHERE_EQUAL, LyssalQueryBuilder::WHERE_NOT_EQUAL, LyssalQueryBuilder::WHERE_LESS, LyssalQueryBuilder::WHERE_LESS_OR_EQUAL, LyssalQueryBuilder::WHERE_GREATER, LyssalQueryBuilder::WHERE_GREATER_OR_EQUAL))) {
            if (!is_array($conditionValue) || count($conditionValue) != 1) {
                throw new OrmException('The condition value of an { WHERE_EQUAL | WHERE_NOT_EQUAL | WHERE_LESS | WHERE_LESS_OR_EQUAL | WHERE_GREATER | WHERE_GREATER_OR_EQUAL } must be an associative array with one value.');
            }

            foreach ($conditionValue as $property => $value) {
                $conditionValueLabel = $this->addParameterInQueryBuilder($queryBuilder, $value);
                return $this->getCompleteProperty($property).' '.$this->getDqlSymbol($conditionProperty).' :'.$conditionValueLabel;
            }
        }

        if (LyssalQueryBuilder::WHERE_NULL === $conditionProperty) {
            return call_user_func_array(array($queryBuilder->expr(), 'isNull'), array($this->getCompleteProperty($conditionValue)));
        }

        if (null === $conditionValue) {
            return call_user_func_array(array($queryBuilder->expr(), 'isNull'), array($this->getCompleteProperty($conditionProperty)));
        }

        if (LyssalQueryBuilder::WHERE_NOT_NULL === $conditionProperty) {
            return call_user_func_array(array($queryBuilder->expr(), 'isNotNull'), array($this->getCompleteProperty($conditionValue)));
        }

        return $this->getQueryBuilderConditionString($queryBuilder, $conditionProperty, $conditionValue);
    }

    /**
     * Process the HAVING conditions in the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param array                      $conditions   The conditions of the search
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository This
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If a HAVING condition value is not valid
     */
    protected function processQueryBuilderHavings(QueryBuilder $queryBuilder, array $conditions)
    {
        foreach ($conditions as $conditionProperty => $conditionValue) {
            if ($this->conditionIsHaving($conditionProperty)) {
                $queryBuilder->andHaving($this->processQueryBuilderHaving($queryBuilder, $conditionProperty, $conditionValue));
            }
        }

        return $this;
    }

    /**
     * Get if the condition is HAVING.
     *
     * @param string $conditionProperty The condition property
     * @return boolean If HAVING
     */
    protected function conditionIsHaving($conditionProperty)
    {
        return in_array($conditionProperty, array(LyssalQueryBuilder::AND_HAVING, LyssalQueryBuilder::OR_HAVING, LyssalQueryBuilder::HAVING_EQUAL, LyssalQueryBuilder::HAVING_LESS, LyssalQueryBuilder::HAVING_LESS_OR_EQUAL, LyssalQueryBuilder::HAVING_GREATER, LyssalQueryBuilder::HAVING_GREATER_OR_EQUAL), true);
    }

    /**
     * Process an HAVING condition in the query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder      The query builder
     * @param string                     $conditionProperty The condition property name
     * @param string|array               $conditionValue    The value(s) of the condition
     * @return mixed The condition
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the condition value is not valid
     */
    protected function processQueryBuilderHaving(QueryBuilder &$queryBuilder, $conditionProperty, $conditionValue)
    {
        if (LyssalQueryBuilder::OR_HAVING === $conditionProperty) {
            $conditionsOr = array();
            foreach ($conditionValue as $conditionOrPropriete => $conditionOrValeur) {
                $conditionsOr[] = $this->processQueryBuilderHaving($queryBuilder, $conditionOrPropriete, $conditionOrValeur);
            }
            return call_user_func_array(array($queryBuilder->expr(), 'orX'), $conditionsOr);
        }

        if (LyssalQueryBuilder::AND_HAVING === $conditionProperty) {
            $conditionsAnd = array();
            foreach ($conditionValue as $conditionOrPropriete => $conditionOrValeur) {
                $conditionsAnd[] = $this->processQueryBuilderHaving($queryBuilder, $conditionOrPropriete, $conditionOrValeur);
            }
            return call_user_func_array(array($queryBuilder->expr(), 'andX'), $conditionsAnd);
        }

        if (in_array($conditionProperty, array(LyssalQueryBuilder::HAVING_EQUAL, LyssalQueryBuilder::HAVING_LESS, LyssalQueryBuilder::HAVING_LESS_OR_EQUAL, LyssalQueryBuilder::HAVING_GREATER, LyssalQueryBuilder::HAVING_GREATER_OR_EQUAL), true)) {
            if (!is_array($conditionValue) || count($conditionValue) != 1) {
                throw new OrmException('The condition of an { HAVING_LESS | HAVING_LESS_OR_EQUAL | HAVING_GREATER | HAVING_GREATER_OR_EQUAL } value must be an associative array with only one value.');
            }
            foreach ($conditionValue as $property => $value) {
                $conditionValueLabel = $this->addParameterInQueryBuilder($queryBuilder, $value);
                return $this->getCompleteProperty($property).' '.$this->getDqlSymbol($conditionProperty).' :'.$conditionValueLabel;
            }
        }

        return $this->getQueryBuilderConditionString($queryBuilder, $conditionProperty, $conditionValue);
    }

    /**
     * Add a parameter in the query builder which will be well formatted.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param mixed                      $value        The value
     * @return string The parameter label
     */
    protected function addParameterInQueryBuilder(QueryBuilder $queryBuilder, $value)
    {
        $parameter = 'lyssal_'.(self::$parametersCounter++);
        $queryBuilder->setParameter($parameter, $value);

        return $parameter;
    }

    /**
     * Get the DQL symbol for the EntityRepository constant.
     *
     * @param string $constant The constant (cf. LyssalQueryBuilder::*)
     * @return string The DQL symbol
     * @throws \Lyssal\Doctrine\Orm\Exception\OrmException If the symbol is not founded
     */
    protected function getDqlSymbol($constant)
    {
        switch ($constant) {
            case LyssalQueryBuilder::WHERE_EQUAL:
            case LyssalQueryBuilder::HAVING_EQUAL:
                return '=';
            case LyssalQueryBuilder::WHERE_NOT_EQUAL:
                return '!=';
            case LyssalQueryBuilder::WHERE_LESS:
            case LyssalQueryBuilder::HAVING_LESS:
                return '<';
            case LyssalQueryBuilder::WHERE_LESS_OR_EQUAL:
            case LyssalQueryBuilder::HAVING_LESS_OR_EQUAL:
                return '<=';
            case LyssalQueryBuilder::WHERE_GREATER:
            case LyssalQueryBuilder::HAVING_GREATER:
                return '>';
            case LyssalQueryBuilder::WHERE_GREATER_OR_EQUAL:
            case LyssalQueryBuilder::HAVING_GREATER_OR_EQUAL:
                return '>=';
            default:
                throw new OrmException('The symbol has not been founded for "'.$constant.'".');
        }
    }

    /**
     * Get the condition string with the parameter name.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder      The query builder
     * @param string                     $conditionProperty The condition property
     * @param string                     $conditionValue    The condition value
     * @return string[string] The condition string with the query builder parameters
     */
    protected function getQueryBuilderConditionString(QueryBuilder &$queryBuilder, $conditionProperty, $conditionValue)
    {
        $conditionType = (is_array($conditionValue) ? 'in' : 'eq');
        $conditionValueLabel = $this->addParameterInQueryBuilder($queryBuilder, $conditionValue);

        return call_user_func_array(array($queryBuilder->expr(), $conditionType), array($this->getCompleteProperty($conditionProperty), ':'.$conditionValueLabel));
    }

    /**
     * Process the query builder' orders.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param array                      $orderBy      The OrderBys
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository This
     */
    protected function processQueryBuilderOrderBy(QueryBuilder $queryBuilder, array $orderBy = [])
    {
        foreach ($orderBy as $orderByKey => $orderByValue) {
            if (is_int($orderByKey)) {// Not an associative array
                $queryBuilder->addOrderBy($this->getCompleteProperty($orderByValue), 'ASC');
            } else {
                $queryBuilder->addOrderBy($this->getCompleteProperty($orderByKey), $orderByValue);
            }
        }

        return $this;
    }

    /**
     * Get the entity name.
     *
     * @param string $property The property
     * @return string The complete property
     */
    protected function getCompleteProperty($property)
    {
        if ($this->entityHasProperty($property)) {
            return 'entity.'.$property;
        }

        return $property;
    }

    /**
     * Get if the entity has the property.
     *
     * @param string $property The property
     * @return bool If the property exists
     */
    protected function entityHasProperty($property)
    {
        return (false === strpos($property, '.') && property_exists($this->_class->getName(), $property));
    }

    /**
     * Process the query builder's max results.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param int|null                   $limit        The results limit
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository This
     */
    protected function processQueryBuilderMaxResults(QueryBuilder $queryBuilder, $limit = null)
    {
        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $this;
    }

    /**
     * Process the query builder's first result.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder The query builder
     * @param int|null                   $offset       The first result index
     * @return \Lyssal\Doctrine\Orm\Repository\EntityRepository This
     */
    protected function processQueryBuilderFirstResult(QueryBuilder $queryBuilder, $offset = null)
    {
        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        return $this;
    }
}
