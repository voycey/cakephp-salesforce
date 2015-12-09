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
namespace Salesforce\ORM;

use Cake\ORM\ResultSet;
use Cake\Collection\Collection;
use Cake\Collection\CollectionTrait;
use Cake\Database\Exception;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use SplFixedArray;

/**
 * Represents the results obtained after executing a query for a specific table
 * This object is responsible for correctly nesting result keys reported from
 * the query, casting each field to the correct type and executing the extra
 * queries required for eager loading external associations.
 *
 */
class MyResultSet extends ResultSet
{
    /**
     * Creates a map of row keys out of the query select clause that can be
     * used to hydrate nested result sets more quickly.
     *
     * @return void
     */
    protected function _calculateColumnMap()
    {
        $map = []; //My one
        foreach ($this->_query->clause('select') as $key => $field) {
            $key = trim($key, '"`[]');
            if (strpos($key, '__') > 0) {
                $parts = explode('__', $key, 2);
                $map[$parts[0]][$parts[1]] = $parts[1];
            } else {
                $map[$this->_defaultAlias][$key] = $key;
            }
        }

        foreach ($this->_matchingMap as $alias => $assoc) {
            if (!isset($map[$alias])) {
                continue;
            }
            $this->_matchingMapColumns[$alias] = $map[$alias];
            unset($map[$alias]);
        }

        $this->_map = $map;
    }

    /**
     * Correctly nests results keys including those coming from associations
     *
     * @param mixed $row Array containing columns and values or false if there is no results
     * @return array Results
     */
    protected function _groupResult($row)
    {

        return $row;
    }

    /**
     * Helper function to fetch the next result from the statement or
     * seeded results.
     *
     * @return mixed
     */
    protected function _fetchResult()
    {
        if (!$this->_statement) {
            return false;
        }

        $row = $this->_statement->fetch('assoc');
        if ($row === false) {
            return $row;
        }
        return $this->_groupResult($row);
    }
}
