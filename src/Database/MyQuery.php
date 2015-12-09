<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Salesforce\Database;

use Cake\Database\Query;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\OrderClauseExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Expression\ValuesExpression;
use Cake\Database\Statement\CallbackStatement;
use Cake\Database\ValueBinder;
use IteratorAggregate;
use RuntimeException;

/**
 * This class represents a Relational database SQL Query. A query can be of
 * different types like select, update, insert and delete. Exposes the methods
 * for dynamically constructing each query part, execute it and transform it
 * to a specific SQL dialect.
 */
class MyQuery extends Query
{
    /**
     * Returns the SQL representation of this object.
     *
     * This function will compile this query to make it compatible
     * with the SQL dialect that is used by the connection, This process might
     * add, remove or alter any query part or internal expression to make it
     * executable in the target platform.
     *
     * The resulting query may have placeholders that will be replaced with the actual
     * values when the query is executed, hence it is most suitable to use with
     * prepared statements.
     *
     * @param ValueBinder $generator A placeholder object that will hold
     * associated values for expressions
     * @return string
     */
    public function sql(ValueBinder $generator = null, $incoming = null)
    {
        if (!$generator) {
            $generator = $this->valueBinder();
            $generator->resetCount();
        }

        return $this->connection()->compileQuery($incoming, $generator);
    }

    /**
     * Will iterate over every specified part. Traversing functions can aggregate
     * results using variables in the closure or instance variables. This function
     * is commonly used as a way for traversing all query parts that
     * are going to be used for constructing a query.
     *
     * The callback will receive 2 parameters, the first one is the value of the query
     * part that is being iterated and the second the name of such part.
     *
     * ### Example:
     * ```
     *  $query->select(['title'])->from('articles')->traverse(function ($value, $clause) {
     *      if ($clause === 'select') {
     *          var_dump($value);
     *      }
     *  }, ['select', 'from']);
     * ```
     *
     * @param callable $visitor a function or callable to be executed for each part
     * @param array $parts the query clauses to traverse
     * @return $this
     */
    public function traverse(callable $visitor, array $parts = [])
    {
        $parts = $parts ?: array_keys($this->_parts);
        foreach ($parts as $name) {
            $visitor($this->_parts[$name], $name);
        }
        return $this;
    }

    /**
     * Returns string representation of this query (complete SQL statement).
     *
     * @return string
     */
    public function __toString()
    {
        return $this->sql();
    }
}