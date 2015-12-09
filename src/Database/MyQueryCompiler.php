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

use Cake\Database\QueryCompiler;
/**
 * Responsible for compiling a Query object into its SQL representation
 *
 * @internal
 */
class MyQueryCompiler extends QueryCompiler
{
    /**
     * Helper function used to build the string representation of a SELECT clause,
     * it constructs the field list taking care of aliasing and
     * converting expression objects to string. This function also constructs the
     * DISTINCT clause for the query. This has been modified to allow it to support Salesforce
     *
     * @param array $parts list of fields to be transformed to string
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return string
     */
    protected function _buildSelectPart($parts, $query, $generator)
    {
        $driver = $query->connection()->driver();
        $select = 'SELECT %s%s%s';
        if ($this->_orderedUnion && $query->clause('union')) {
            $select = '(SELECT %s%s%s';
        }
        $distinct = $query->clause('distinct');
        $modifiers = $query->clause('modifier') ?: null;

        $normalized = [];
        $parts = $this->_stringifyExpressions($parts, $generator);
        foreach ($parts as $k => $p) {
            if (!is_numeric($k)) {
                $p = $p; //Leave it alone
            }
            $normalized[] = $p;
        }

        if ($distinct === true) {
            $distinct = 'DISTINCT ';
        }

        if (is_array($distinct)) {
            $distinct = $this->_stringifyExpressions($distinct, $generator);
            $distinct = sprintf('DISTINCT ON (%s) ', implode(', ', $distinct));
        }
        if ($modifiers !== null) {
            $modifiers = $this->_stringifyExpressions($modifiers, $generator);
            $modifiers = implode(' ', $modifiers) . ' ';
        }

        return sprintf($select, $distinct, $modifiers, implode(', ', $normalized));
    }

    /**
     * Returns the SQL representation of the provided query after generating
     * the placeholders for the bound values using the provided generator
     *
     * @param \Cake\Database\Query $query The query that is being compiled
     * @param \Cake\Database\ValueBinder $generator the placeholder generator to be used in expressions
     * @return \Closure
     */
    public function compile($query, $generator)
    {
        $sql = '';
        $type = $query->type();
        $query->traverse(
            $this->_sqlCompiler($sql, $query, $generator),
            $this->{'_' . $type . 'Parts'}
        );
        return $sql;
    }
}