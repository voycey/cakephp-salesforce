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

use Cake\Database\Type;
use InvalidArgumentException;

/**
 * Encapsulates all conversion functions for values coming from database into PHP and
 * going from PHP into database.
 */
class SalesforceType extends Type
{

    /**
     * List of supported database types. A human readable
     * identifier is used as key and a complete namespaced class name as value
     * representing the class that will do actual type conversions.
     *
     * @var array
     */
    protected static $_types = [
        'biginteger' => 'Cake\Database\Type\IntegerType',
        'binary' => 'Cake\Database\Type\BinaryType',
        'boolean' => 'Cake\Database\Type\BoolType',
        'date' => 'Cake\Database\Type\DateType',
        'datetime' => 'Cake\Database\Type\DateTimeType',
        'decimal' => 'Cake\Database\Type\FloatType',
        'float' => 'Cake\Database\Type\FloatType',
        'integer' => 'Cake\Database\Type\IntegerType',
        'string' => 'Salesforce\Database\Type\SalesforceStringType',
        'text' => 'Cake\Database\Type\StringType',
        'time' => 'Cake\Database\Type\TimeType',
        'timestamp' => 'Cake\Database\Type\DateTimeType',
        'uuid' => 'Cake\Database\Type\UuidType'
    ];

    /**
     * List of basic type mappings, used to avoid having to instantiate a class
     * for doing conversion on these
     *
     * @var array
     * @deprecated 3.1 All types will now use a specific class
     */
    protected static $_basicTypes = [
        'string' => ['callback' => ['\Salesforce\Database\Type', 'strval']],
        'text' => ['callback' => ['\Cake\Database\Type', 'strval']],
        'boolean' => [
            'callback' => ['\Cake\Database\Type', 'boolval']
        ],
    ];

    /**
     * Returns a Type object capable of converting a type identified by $name
     *
     * @param string $name type identifier
     * @throws \InvalidArgumentException If type identifier is unknown
     * @return Type
     */
    public static function build($name)
    {
        //force rebuild of string type
        if ($name != "string") {
            if (isset(static::$_builtTypes[$name])) {
                return static::$_builtTypes[$name];
            }
            if (!isset(static::$_types[$name])) {
                throw new InvalidArgumentException(sprintf('Unknown type "%s"', $name));
            }
        }

        return static::$_builtTypes[$name] = new static::$_types[$name]($name);
    }
}
