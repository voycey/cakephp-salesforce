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

use ArrayObject;
use Cake\ORM\Marshaller;
use Salesforce\Database\SalesforceType;

/**
 * Contains logic to convert array data into entities.
 *
 * Useful when converting request data into entities.
 *
 * @see \Cake\ORM\Table::newEntity()
 * @see \Cake\ORM\Table::newEntities()
 * @see \Cake\ORM\Table::patchEntity()
 * @see \Cake\ORM\Table::patchEntities()
 */
class SalesforceMarshaller extends Marshaller
{
    /**
     * Hydrate one entity and its associated data.
     *
     * ### Options:
     *
     * * associated: Associations listed here will be marshalled as well.
     * * fieldList: A whitelist of fields to be assigned to the entity. If not present,
     *   the accessible fields list in the entity will be used.
     * * accessibleFields: A list of fields to allow or deny in entity accessible fields.
     *
     * The above options can be used in each nested `associated` array. In addition to the above
     * options you can also use the `onlyIds` option for HasMany and BelongsToMany associations.
     * When true this option restricts the request data to only be read from `_ids`.
     *
     * ```
     * $result = $marshaller->one($data, [
     *   'associated' => ['Tags' => ['onlyIds' => true]]
     * ]);
     * ```
     *
     * @param array $data The data to hydrate.
     * @param array $options List of options
     * @return \Cake\ORM\Entity
     * @see \Cake\ORM\Table::newEntity()
     */
    public function one(array $data, array $options = [])
    {
        list($data, $options) = $this->_prepareDataAndOptions($data, $options);

        $propertyMap = $this->_buildPropertyMap($options);

        $schema = $this->_table->schema();
        $primaryKey = $schema->primaryKey();
        $entityClass = $this->_table->entityClass();
        $entity = new $entityClass();
        $entity->source($this->_table->registryAlias());

        if (isset($options['accessibleFields'])) {
            foreach ((array)$options['accessibleFields'] as $key => $value) {
                $entity->accessible($key, $value);
            }
        }

        $errors = $this->_validate($data, $options, true);
        $properties = [];
        foreach ($data as $key => $value) {
            if (!empty($errors[$key])) {
                continue;
            }
            $columnType = $schema->columnType($key);
            if (isset($propertyMap[$key])) {
                $assoc = $propertyMap[$key]['association'];
                $value = $this->_marshalAssociation($assoc, $value, $propertyMap[$key]);
            } elseif ($value === '' && in_array($key, $primaryKey, true)) {
                // Skip marshalling '' for pk fields.
                continue;
            } elseif ($columnType) {
                $converter = SalesforceType::build($columnType);
                $value = $converter->marshal($value);
            }
            $properties[$key] = $value;
        }

        if (!isset($options['fieldList'])) {
            $entity->set($properties);
            $entity->errors($errors);
            return $entity;
        }

        foreach ((array)$options['fieldList'] as $field) {
            if (array_key_exists($field, $properties)) {
                $entity->set($field, $properties[$field]);
            }
        }

        $entity->errors($errors);
        return $entity;
    }
}
