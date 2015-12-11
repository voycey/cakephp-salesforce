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

use \ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Salesforce\ORM\SalesforceQuery;
use Salesforce\ORM\SalesforceMarshaller;


class SalesforceTable extends Table
{
    /**
     * {@inheritDoc}
     */
    public function query()
    {
        return new SalesforceQuery($this->connection(), $this);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($conditions)
    {
        return (bool)count(
            $this->find('all')
                ->select(['Id'])
                ->where($conditions)
                ->limit(1)
                ->hydrate(false)
                ->toArray()
        );
    }

    /**
    * {@inheritDoc}
    */
    public function save(EntityInterface $entity, $options = [])
    {
        $options = new ArrayObject($options + [
                'atomic' => true,
                'associated' => true,
                'checkRules' => true,
                'checkExisting' => true,
                '_primary' => true
            ]);

        if (is_array($entity)) {
            $entity = $this->newEntity($entity);
        }
        if ($entity->errors()) {
            return false;
        }

        if ($entity->isNew() === false && !$entity->dirty()) {
            return $entity;
        }

        $connection = $this->connection();
        $success = $this->_processSave($entity, $options);

        if ($success) {
            if ($options['atomic'] || (!$options['atomic'] && $options['_primary'])) {
                $this->dispatchEvent('Model.afterSaveCommit', compact('entity', 'options'));
            }

            if ($options['atomic'] || $options['_primary']) {
                $entity->isNew(false);
                $entity->source($this->registryAlias());
            }
        }

        return $success;
    }
    /**
    * {@inheritDoc}
    */
    public function newEntity($data = null, array $options = [])
    {
        if ($data === null) {
            $class = $this->entityClass();
            $entity = new $class([], ['source' => $this->registryAlias()]);
            return $entity;
        }
        if (!isset($options['associated'])) {
            $options['associated'] = $this->_associations->keys();
        }
        $marshaller = $this->marshaller();
        return $marshaller->one($data, $options);
    }

    /**
     * Get the object used to marshal/convert array data into objects.
     *
     * Override this method if you want a table object to use custom
     * marshalling logic.
     *
     * @return \Cake\ORM\Marshaller
     * @see \Cake\ORM\Marshaller
     */
    public function marshaller()
    {
        return new SalesforceMarshaller($this);
    }
}
