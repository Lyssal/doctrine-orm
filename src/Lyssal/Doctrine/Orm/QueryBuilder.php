<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm;

/**
 * Constants used in the entity repository.
 */
class QueryBuilder
{
    /**
     * @var string Extra for addSelect()
     */
    const SELECTS = 'selects';

    /**
     * @var string Extra for leftJoin()
     */
    const LEFT_JOINS = 'leftJoins';

    /**
     * @var string Extra for innerJoin()
     */
    const INNER_JOINS = 'innerJoins';

    /**
     * @var string Extra for andGroupBy()
     */
    const GROUP_BYS = 'groupBys';

    /**
     * @var string Used with the SELECTS extra to add joined entities into the main entity
     */
    const SELECT_JOIN = '__SELECT_JOIN__';

    /**
     * @var string For (x OR y OR ...)
     */
    const OR_WHERE = '__OR_WHERE__';

    /**
     * @var string For (x AND y AND ...)
     */
    const AND_WHERE = '__AND_WHERE__';

    /**
     * @var string For a WHERE ... LIKE ...
     */
    const WHERE_LIKE = '__LIKE__';

    /**
     * @var string For a WHERE ... IN (...)
     */
    const WHERE_IN = '__IN__';

    /**
     * @var string For a WHERE ... NOT IN (...)
     */
    const WHERE_NOT_IN = '__NOT_IN__';

    /**
     * @var string For a WHERE ... IS NULL
     */
    const WHERE_NULL = '__IS_NULL__';

    /**
     * @var string For a WHERE ... IS NOT NULL
     */
    const WHERE_NOT_NULL = '__IS_NOT_NULL__';

    /**
     * @var string For a x = y
     */
    const WHERE_EQUAL = '__WHERE_EQUAL__';

    /**
     * @var string For a x < y
     */
    const WHERE_LESS = '__WHERE_LESS__';

    /**
     * @var string For a x <= y
     */
    const WHERE_LESS_OR_EQUAL = '__WHERE_LESS_OR_EQUAL__';

    /**
     * @var string For a x > y
     */
    const WHERE_GREATER = '__WHERE_GREATER__';

    /**
     * @var string For a x >= y
     */
    const WHERE_GREATER_OR_EQUAL = '__WHERE_GREATER_OR_EQUAL__';

    /**
     * @var string For a HAVING (x OR y OR ...)
     */
    const OR_HAVING = '__OR_HAVING__';

    /**
     * @var string For a HAVING (x AND y AND ...)
     */
    const AND_HAVING = '__AND_HAVING__';

    /**
     * @var string For a HAVING x = y
     */
    const HAVING_EQUAL = '__HAVING_EQUAL__';

    /**
     * @var string For a HAVING x < y
     */
    const HAVING_LESS = '__HAVING_LESS__';

    /**
     * @var string For a HAVING x <= y
     */
    const HAVING_LESS_OR_EQUAL = '__HAVING_LESS_OR_EQUAL__';

    /**
     * @var string For a HAVING x > y
     */
    const HAVING_GREATER = '__HAVING_GREATER__';

    /**
     * @var string For a HAVING x >= y
     */
    const HAVING_GREATER_OR_EQUAL = '__HAVING_GREATER_OR_EQUAL__';
}
