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

        //intercept Update here
        if ($this->_statement->type() == "update") {
            $result = $this->_driver->client->update([$this->_interpolate($sql, $bindings, true)], $this->_statement->repository()->name);
            if (empty($result->size)) {
                $result = (object)json_decode(json_encode($result));
                $result->size = 1;
            }
        } else {
            $result = $this->_driver->client->query($this->_interpolate($sql, $bindings));
        }

        $this->last_rows_affected = $result->size;
        $this->last_result = $result;
        return $result;
    }

    /**
     * Helper function used to replace query placeholders by the real
     * params used to execute the query
     *
     * @param LoggedQuery $query The query to log
     * @return mixed
     */
    protected function _interpolate($sql, $bindings, $sObject = false)
    {
        foreach ($bindings as $binding) {
            $binding['placeholder'] = ":".$binding['placeholder'];
            switch ($binding['type']) {
                case "integer":
                   $sql = preg_replace('/'.$binding['placeholder'].'\b/i', "'".(int)$binding['value']."'", $sql);
                    break;
                case "boolean":
                    $sql = preg_replace('/'.$binding['placeholder'].'\b/i', "'".(int)$binding['value']."'", $sql);
                    break;
                case "datetime":
                    $sql = preg_replace('/'.$binding['placeholder'].'\b/i', "'". $binding['value']."'", $sql);
                    break;
                default:
                    $sql = preg_replace('/'.$binding['placeholder'].'\b/i', "'". addslashes(trim($binding['value']))."'", $sql);
                    break;
            }
        }

        if ($sObject) {
            //slice and dice this into an SObject for Salesforce
            $cleanedSQL = explode("' ", trim(substr($sql, strpos($sql, "SET ") +4 )));
            $newSQL = [];
            foreach ($cleanedSQL as $row) {

                //verbose for clarity
                $string = explode("=", str_replace("'", "", str_replace(", ", " ", $row)));
                if (empty($string[1])) {
                    $string[1] = NULL;
                }
                //This needs to not be hardcoded "Id"
                if($string[0] == "WHERE Id ") {
                    $newSQL['Id'] = trim($string[1]);
                } else {
                    $newSQL[trim($string[0])] = trim($string[1]);
                }
            }

            //remove empty / null values
            $newSQL = array_filter($newSQL, 'strlen');

            //return as object
            return (object)$newSQL;
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

    /**
     * Returns the error code for the last error that occurred when executing this statement.
     *
     * @return int|string
     */
    public function errorCode()
    {
        return '00000';
    }

    /**
     * Returns the error information for the last error that occurred when executing
     * this statement.
     *
     * @return array
     */
    public function errorInfo()
    {
        return "Salesforce Datasource doesnt produce PDO error codes - exceptions are usually thrown";
    }

    /**
     * Closes a cursor in the database, freeing up any resources and memory
     * allocated to it. In most cases you don't need to call this method, as it is
     * automatically called after fetching all results from the result set.
     *
     * @return void
     */
    public function closeCursor()
    {
        return true;
    }
}
