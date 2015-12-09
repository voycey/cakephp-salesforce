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

use Salesforce\Database\Driver\SalesforceDriverTrait;
use Cake\Database\Exception;
use Cake\Database\Schema\BaseSchema;
use Cake\Database\Schema\Table;

/**
 * Schema generation/reflection features for MySQL
 */
class SalesforceSchema extends BaseSchema
{

   use SalesforceDriverTrait;
    /**
     * {@inheritDoc}
     */
    public function listTablesSql($config)
    {
        return ['SHOW TABLES FROM ' . $this->_driver->quoteIdentifier($config['database']), []];
    }

    /**
     * Custom function that queries the datasource for the table list as SOQL doesn't accept
     * DESCRIBE functionality
     */
    public function listTables()
    {

        $sfschema = $this->client->describeSObject($modelname);
    }

    /**
     * Custom function that queries the datasource for the table list as SOQL doesn't accept
     * DESCRIBE functionality
     */
    public function describeColumn($tableName, $config)
    {
        $sfschema = $this->_driver->client->describeSObject($tableName);
        $newSchema = array();
        foreach ($sfschema->fields as $field) {
            switch ($field->type) {
                case "id":
                    $field->type = "integer";
                    break;
                case "integer":
                    $field->type = "integer";
                    break;
                case "boolean":
                    $field->type = "boolean";
                    break;
                case "datetime":
                    $field->type = "datetime";
                    break;

                default:
                    $field->type = "string";
            }

            if ($field->nillable == 1) {
                $field->nillable = "true";
            } else {
                $field->nillable = "false";
            }

            //Capital letters here as it is emulating return from MySQL
            if ($field->length > 0) {
                $newSchema[] = array('Field' => $field->name, 'Type' => $field->type, 'Length' => $field->length, 'Null' => $field->nillable);
            } else {
                $newSchema[] = array('Field' => $field->name, 'Type' => $field->type, 'Null' => $field->nillable);
            }
        }
        return $newSchema;

    }
    /**
     * {@inheritDoc}
     */
    public function describeColumnSql($tableName, $config)
    {
        return ['SHOW FULL COLUMNS FROM ' . $this->_driver->quoteIdentifier($tableName), []];
    }

    /**
     * {@inheritDoc}
     */
    public function describeIndexSql($tableName, $config)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function describeOptionsSql($tableName, $config)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function convertOptionsDescription(Table $table, $row)
    {
        $table->options([
            'engine' => $row['Engine'],
            'collation' => $row['Collation'],
        ]);
    }

    /**
     * Convert a MySQL column type into an abstract type.
     *
     * The returned type will be a type that Cake\Database\Type can handle.
     *
     * @param string $column The column type + length
     * @return array Array of column information.
     * @throws \Cake\Database\Exception When column type cannot be parsed.
     */
    protected function _convertColumn($column)
    {
        preg_match('/([a-z]+)(?:\(([0-9,]+)\))?\s*([a-z]+)?/i', $column, $matches);
        if (empty($matches)) {
            throw new Exception(sprintf('Unable to parse column type from "%s"', $column));
        }

        $col = strtolower($matches[1]);
        $length = $precision = null;
        if (isset($matches[2])) {
            $length = $matches[2];
            if (strpos($matches[2], ',') !== false) {
                list($length, $precision) = explode(',', $length);
            }
            $length = (int)$length;
            $precision = (int)$precision;
        }

        if (in_array($col, ['date', 'time', 'datetime', 'timestamp'])) {
            return ['type' => $col, 'length' => null];
        }
        if (($col === 'tinyint' && $length === 1) || $col === 'boolean') {
            return ['type' => 'boolean', 'length' => null];
        }

        $unsigned = (isset($matches[3]) && strtolower($matches[3]) === 'unsigned');
        if (strpos($col, 'bigint') !== false || $col === 'bigint') {
            return ['type' => 'biginteger', 'length' => $length, 'unsigned' => $unsigned];
        }
        if (in_array($col, ['int', 'integer', 'tinyint', 'smallint', 'mediumint'])) {
            return ['type' => 'integer', 'length' => $length, 'unsigned' => $unsigned];
        }
        if ($col === 'char' && $length === 36) {
            return ['type' => 'uuid', 'length' => null];
        }
        if ($col === 'char') {
            return ['type' => 'string', 'fixed' => true, 'length' => $length];
        }
        if (strpos($col, 'char') !== false) {
            return ['type' => 'string', 'length' => $length];
        }
        if (strpos($col, 'text') !== false) {
            return ['type' => 'text', 'length' => $length];
        }
        if (strpos($col, 'blob') !== false || $col === 'binary') {
            return ['type' => 'binary', 'length' => $length];
        }
        if (strpos($col, 'float') !== false || strpos($col, 'double') !== false) {
            return [
                'type' => 'float',
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned
            ];
        }
        if (strpos($col, 'decimal') !== false) {
            return [
                'type' => 'decimal',
                'length' => $length,
                'precision' => $precision,
                'unsigned' => $unsigned
            ];
        }
        return ['type' => 'text', 'length' => null];
    }

    /**
     * {@inheritDoc}
     */
    public function convertColumnDescription(Table $table, $row)
    {
        $field = $this->_convertColumn($row['Type']);
        $field += [
            'null' => $row['Null'] === 'YES' ? true : false
        ];
        if (isset($row['Extra']) && $row['Extra'] === 'auto_increment') {
            $field['autoIncrement'] = true;
        }
        $table->addColumn($row['Field'], $field);
    }

    /**
     * {@inheritDoc}
     */
    public function convertIndexDescription(Table $table, $row)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function describeForeignKeySql($tableName, $config)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function convertForeignKeyDescription(Table $table, $row)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function truncateTableSql(Table $table)
    {
       return false;
    }

    /**
     * {@inheritDoc}
     */
    public function createTableSql(Table $table, $columns, $constraints, $indexes)
    {
       return false;
    }

    /**
     * {@inheritDoc}
     */
    public function columnSql(Table $table, $name)
    {
        $data = $table->column($name);
        $out = $this->_driver->quoteIdentifier($name);
        $typeMap = [
            'integer' => ' INTEGER',
            'biginteger' => ' BIGINT',
            'boolean' => ' BOOLEAN',
            'binary' => ' LONGBLOB',
            'float' => ' FLOAT',
            'decimal' => ' DECIMAL',
            'text' => ' TEXT',
            'date' => ' DATE',
            'time' => ' TIME',
            'datetime' => ' DATETIME',
            'timestamp' => ' TIMESTAMP',
            'uuid' => ' CHAR(36)'
        ];
        $specialMap = [
            'string' => true,
        ];
        if (isset($typeMap[$data['type']])) {
            $out .= $typeMap[$data['type']];
        }
        if (isset($specialMap[$data['type']])) {
            switch ($data['type']) {
                case 'string':
                    $out .= !empty($data['fixed']) ? ' CHAR' : ' VARCHAR';
                    if (!isset($data['length'])) {
                        $data['length'] = 255;
                    }
                    break;
            }
        }
        $hasLength = ['integer', 'string'];
        if (in_array($data['type'], $hasLength, true) && isset($data['length'])) {
            $out .= '(' . (int)$data['length'] . ')';
        }

        $hasPrecision = ['float', 'decimal'];
        if (in_array($data['type'], $hasPrecision, true) &&
            (isset($data['length']) || isset($data['precision']))
        ) {
            $out .= '(' . (int)$data['length'] . ',' . (int)$data['precision'] . ')';
        }

        $hasUnsigned = ['float', 'decimal', 'integer', 'biginteger'];
        if (in_array($data['type'], $hasUnsigned, true) &&
            isset($data['unsigned']) && $data['unsigned'] === true
        ) {
            $out .= ' UNSIGNED';
        }

        if (isset($data['null']) && $data['null'] === false) {
            $out .= ' NOT NULL';
        }
        $addAutoIncrement = (
            [$name] == (array)$table->primaryKey() &&
            !$table->hasAutoIncrement()
        );
        if (in_array($data['type'], ['integer', 'biginteger']) &&
            ($data['autoIncrement'] === true || $addAutoIncrement)
        ) {
            $out .= ' AUTO_INCREMENT';
        }
        if (isset($data['null']) && $data['null'] === true) {
            $out .= $data['type'] === 'timestamp' ? ' NULL' : ' DEFAULT NULL';
            unset($data['default']);
        }
        if (isset($data['default']) && !in_array($data['type'], ['timestamp', 'datetime'])) {
            $out .= ' DEFAULT ' . $this->_driver->schemaValue($data['default']);
            unset($data['default']);
        }
        if (isset($data['default']) &&
            in_array($data['type'], ['timestamp', 'datetime']) &&
            strtolower($data['default']) === 'current_timestamp'
        ) {
            $out .= ' DEFAULT CURRENT_TIMESTAMP';
            unset($data['default']);
        }
        if (isset($data['comment']) && $data['comment'] !== '') {
            $out .= ' COMMENT ' . $this->_driver->schemaValue($data['comment']);
        }
        return $out;
    }

    /**
     * {@inheritDoc}
     */
    public function constraintSql(Table $table, $name)
    {
       return false;
    }

    /**
     * {@inheritDoc}
     */
    public function addConstraintSql(Table $table)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function dropConstraintSql(Table $table)
    {
       return false;
    }

    /**
     * {@inheritDoc}
     */
    public function indexSql(Table $table, $name)
    {
        return false;
    }

    /**
     * Helper method for generating key SQL snippets.
     *
     * @param string $prefix The key prefix
     * @param array $data Key data.
     * @return string
     */
    protected function _keySql($prefix, $data)
    {
        return false;
    }
}
