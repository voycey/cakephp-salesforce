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
namespace Salesforce\Database\Schema;

use Cake\Database\Exception;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\Table;
use Cake\Datasource\ConnectionInterface;
use PDOException;

/**
 * Represents a database schema collection
 *
 * Used to access information about the tables,
 * and other data in a database.
 */
class SalesforceCollection extends Collection
{

    /**
     * Get the list of tables available in the current connection.
     *
     * @return array The list of tables in the connected database/schema.
     */
    public function listTables()
    {
        list($sql, $params) = $this->_dialect->listTables($this->_connection->config());
        $result = [];
        $statement = $this->_connection->execute($sql, $params);
        while ($row = $statement->fetch()) {
            $result[] = $row[0];
        }
        $statement->closeCursor();
        return $result;
    }

    /**
     * Get the column metadata for a table.
     *
     * Caching will be applied if `cacheMetadata` key is present in the Connection
     * configuration options. Defaults to _cake_model_ when true.
     *
     * ### Options
     *
     * - `forceRefresh` - Set to true to force rebuilding the cached metadata.
     *   Defaults to false.
     *
     * @param string $name The name of the table to describe.
     * @param array $options The options to use, see above.
     * @return \Cake\Database\Schema\Table Object with column metadata.
     * @throws \Cake\Database\Exception when table cannot be described.
     */
    public function describe($name, array $options = [])
    {
        $config = $this->_connection->config();
        if (strpos($name, '.')) {
            list($config['schema'], $name) = explode('.', $name);
        }
        $table = new Table($name);

        $schema = $this->_dialect->describeColumn($name, $config);

        foreach ($schema as $row) {
            $this->_dialect->convertColumnDescription($table, $row);
        }

        if (count($table->columns()) === 0) {
            throw new Exception(sprintf('Cannot describe %s. It has 0 columns.', $name));
        }

        return $table;
    }
}