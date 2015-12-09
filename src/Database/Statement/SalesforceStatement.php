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
namespace Salesforce\Database\Statement;

use Cake\Database\Statement\StatementDecorator;
use Cake\Database\ValueBinder;
/**
 * Statement class meant to be used by a Mysql PDO driver
 *
 * @internal
 */
class SalesforceStatement extends StatementDecorator
{

    protected $last_rows_affected = 0;
    protected $last_result; //pretty sure this is awful!
    protected $last_row_returned = 0;

    /**
     * {@inheritDoc}
     *
     */
    public function execute($params = null)
    {
        $sql = $this->_statement->sql();
        $bindings = $this->_statement->valueBinder()->bindings();

        $newSQL = $this->_interpolate($sql, $bindings);

        $result = $this->_driver->client->query($newSQL);
        $this->last_rows_affected = $result->size;
        $this->last_result = $result;
        return $result;
    }

    /**
     * Helper function used to replace query placeholders by the real
     * params used to execute the query
     *
     * @param LoggedQuery $query The query to log
     * @return string
     */
    protected function _interpolate($sql, $bindings)
    {
        foreach ($bindings as $binding) {
            switch ($binding['type']) {
                case "integer":
                   $sql = str_replace(":".$binding['placeholder'], $binding['value'], $sql);
                    break;
                case "boolean":
                    $sql = str_replace(":".$binding['placeholder'], "'".(bool) $binding['value']."'", $sql);
                    break;
                default:
                    $sql = str_replace(":".$binding['placeholder'], "'". $binding['value']."'", $sql);
                    break;
            }
        }
        return $sql;
    }

    public function rowCount()
    {
       return $this->last_rows_affected;
    }

    /**
     * Returns the next row for the result set after executing this statement.
     * Rows can be fetched to contain columns as names or positions. If no
     * rows are left in result set, this method will return false
     *
     * ### Example:
     *
     * ```
     *  $statement = $connection->prepare('SELECT id, title from articles');
     *  $statement->execute();
     *  print_r($statement->fetch('assoc')); // will show ['id' => 1, 'title' => 'a title']
     * ```
     *
     * @param string $type 'num' for positional columns, assoc for named columns
     * @return mixed Result array containing columns and values or false if no results
     * are left
     */
    public function fetch($type = 'num')
    {

        if ($type === 'num') {
            $result = (array)$this->last_result->records[$this->last_row_returned];
        }
        if ($type === 'assoc') {
            $result = (array)$this->last_result->records[$this->last_row_returned];
        }

        $this->last_row_returned++;
        return $result;
    }
}
