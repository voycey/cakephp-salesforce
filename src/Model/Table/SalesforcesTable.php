<?php
namespace Salesforce\Model\Table;

use Salesforce\Model\Entity\Salesforce;
use Salesforce\ORM\SalesforceQuery;
use Salesforce\ORM\SalesforceTable;
use Cake\Cache\Cache;
use Cake\Log\LogTrait;

/**
 * Questions Model
 *
 * @property \Cake\ORM\Association\HasMany $PathwaysAnswers
 * @property \Cake\ORM\Association\HasMany $QuestionParts
 * @property \Cake\ORM\Association\BelongsToMany $Pathways
 */
class SalesforcesTable extends SalesforceTable
{
    use LogTrait;

    private $_fields = [];

    public static function defaultConnectionName() {
        return 'salesforce';
    }

    /**
     * Initialize method
     *
     * @param  array $config The configuration for the Table.
     * @return void
     * @throws \Exception
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table(false);
        $this->displayField('Id');
        $this->primaryKey('Id');

        if(!empty($config['connection']->config()['my_wsdl'])) {
            $wsdl = CONFIG . DS  . $config['connection']->config()['my_wsdl'];
        } else {
            throw new \Exception('You need to provide a WSDL');
        }

        $mySforceConnection = new \SforceEnterpriseClient();
        $mySforceConnection->createConnection($wsdl);

        $sflogin = (array)Cache::read('salesforce_login', 'salesforce');
        if(!empty($sflogin['sessionId'])) {
            $mySforceConnection->setSessionHeader($sflogin['sessionId']);
            $mySforceConnection->setEndpoint($sflogin['serverUrl']);
        } else {
            try{
                $mylogin = $mySforceConnection->login($config['connection']->config()['username'],$config['connection']->config()['password']);
                $sflogin = ['sessionId' => $mylogin->sessionId, 'serverUrl' => $mylogin->serverUrl];
                Cache::write('salesforce_login', $sflogin, 'salesforce');
            } catch (\Exception $e) {
                $this->log('Error logging into salesforce from Table - Salesforce down?');
            }
        }

        if (!$sObject = Cache::read($this->name.'_sObject', 'salesforce')) {
            $sObject = $mySforceConnection->describeSObject($this->name);
            Cache::write($this->name.'_sObject', $sObject, 'salesforce');
        }

        $this->_fields = Cache::remember($this->name.'_schema', function () use ($sObject) {
            $fields = [];

            foreach ($sObject->fields as $field) {
                if(substr($field->soapType,0,3) != 'ens') { //we dont want type of ens
                    if(substr($field->soapType,4) == 'int') {
                        $type_name = 'integer';
                    } elseif (substr($field->soapType,4) == 'double') {
                        $type_name = 'float';
                    } elseif (substr($field->soapType,4) == 'boolean') {
                        $type_name = 'boolean';
                    } elseif (substr($field->soapType,4) == 'dateTime') {
                        $type_name = 'datetime';
                    } elseif (substr($field->soapType,4) == 'date') {
                        $type_name = 'date';
                    } else {
                        $type_name = 'string';
                    }
                    if($field->updateable) {
                        $fields['updatable'][$field->name] = ['type' => $type_name, 'length' => $field->length, 'null' => $field->nillable];
                    }
                    $fields['selectable'][$field->name] = ['type' => $type_name, 'length' => $field->length, 'null' => $field->nillable];
                }
            }

            return $fields;
        }, 'salesforce');

        //Cache select fields right away as most likely need them immediately
        $this->schema($this->_fields['selectable']);
    }

    public function beforeFind($event, $query, $options, $primary) {
        $this->schema($this->_fields['selectable']);
    }

    public function beforeSave($event, $entity, $options) {
        if($options['atomic']) {
            throw new \Exception('Salesforce API does not support atomic transactions; set atomic to false.');
        }
        $this->schema($this->_fields['updatable']);
    }
}
