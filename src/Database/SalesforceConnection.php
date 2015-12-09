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

use Salesforce\Database\Schema\SalesforceCollection as SchemaCollection;
use Cake\Database\TypeConverterTrait;
use Cake\Database\Connection;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\Exception\MissingExtensionException;
use Cake\Database\Log\LoggedQuery;
use Cake\Database\Log\LoggingStatement;
use Cake\Database\Log\QueryLogger;
use Cake\Database\Schema\CachedCollection;
use Cake\Datasource\ConnectionInterface;
use Exception;

/**
 * Represents a connection with a database server.
 */
class SalesforceConnection extends Connection
{
    /**
     * Compiles a Query object into a SQL string according to the dialect for this
     * connection's driver
     *
     * @param \App\Database\Query $query The query to be compiled
     * @param \Cake\Database\ValueBinder $generator The placeholder generator to use
     * @return string
     */
    public function compileQuery($query, $generator)
    {
        return $this->driver()->compileQuery($query, $generator)[1];
    }

    /**
     * Create a new Query instance for this connection.
     *
     * @return \App\Database\Query
     */
    public function newQuery()
    {
        return new SalesforceQuery($this);
    }

    public function run($query)
    {
        $statement = $this->prepare($query);
        $query->valueBinder()->attachTo($statement);
        $statement->execute();

        return $statement;
    }

    /**
     * Checks if the driver supports quoting.
     *
     * @return bool
     */
    public function supportsQuoting()
    {
        return false;
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words.
     *
     * @param string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        return $identifier;
    }
}
