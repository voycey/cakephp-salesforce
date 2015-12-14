<?php
namespace Salesforce\Model\Table;

use Salesforce\Model\Entity\Salesforce;
use Salesforce\ORM\SalesforceQuery;
use Salesforce\ORM\SalesforceTable;
use Cake\Cache\Cache;
use Cake\Utility\Xml;
use Cake\Utility\Hash;

/**
 * Questions Model
 *
 * @property \Cake\ORM\Association\HasMany $PathwaysAnswers
 * @property \Cake\ORM\Association\HasMany $QuestionParts
 * @property \Cake\ORM\Association\BelongsToMany $Pathways
 */
class SalesforcesTable extends SalesforceTable
{
    public $schema = array();
    public $updatable_fields = array();
    public $selectable_fields = array();

    public static function defaultConnectionName() {
        return 'salesforce';
    }
    /**
     * Initialize method
     *
     * @param  array $config The configuration for the Table.
     * @return void
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
            throw new \Exception("You need to provide a WSDL");
        }

        $mySforceConnection = new \SforceEnterpriseClient();
        $mySoapClient = $mySforceConnection->createConnection($wsdl);

        $sflogin = (array)Cache::read('salesforce_login', 'salesforce');
        if(!empty($sflogin['sessionId'])) {
            $mySforceConnection->setSessionHeader($sflogin['sessionId']);
            $mySforceConnection->setEndPoint($sflogin['serverUrl']);
        } else {
            try{
                $mylogin = $mySforceConnection->login($config['connection']->config()['username'],$config['connection']->config()['password']);
                $sflogin = array('sessionId' => $mylogin->sessionId, 'serverUrl' => $mylogin->serverUrl);
                Cache::write('salesforce_login', $sflogin, 'salesforce');
            } catch (Exception $e) {
                $this->log("Error logging into salesforce from Table - Salesforce down?");
            }
        }


        if (!$sObject = Cache::read($this->name.'_sObject', 'salesforce')) {
            $sObject = $mySforceConnection->describeSObject($this->name);
            Cache::write($this->name.'_sObject', $sObject, 'salesforce');
        }


        foreach ($sObject->fields as $field) {
            if(substr($field->soapType,0,3) != "ens") { //we dont want type of ens
                if(substr($field->soapType,4) == "int") {
                    $type_name = "integer";
                } elseif (substr($field->soapType,4) == "boolean") {
                    $type_name = "boolean";
                } elseif (substr($field->soapType,4) == "dateTime" || substr($field->soapType,4) == "date") {
                    $type_name = "datetime";
                } else {
                    $type_name = "string";
                }
                if($field->updateable) {
                    $this->updatable_fields[$field->name] = ['type' => $type_name, 'length' => $field->length, 'null' => $field->nillable];
                    $this->selectable_fields[$field->name] = ['type' => $type_name, 'length' => $field->length, 'null' => $field->nillable];
                } else {
                    $this->selectable_fields[$field->name] = ['type' => $type_name, 'length' => $field->length, 'null' => $field->nillable];
                }
            }
        }

        //Cache select fields right away as most likely need them immediately
        Cache::write($this->name.'_selectable_schema', $this->selectable_fields, 'salesforce');


        if (!$this->schema(Cache::read($this->name.'_updatable_schema', 'salesforce'))) {
            $this->schema($this->updatable_fields);
            Cache::write($this->name.'_updatable_schema', $this->updatable_fields, 'salesforce');
        }

    }

    public function beforeFind($event, $query, $options, $primary) {
        if (!$this->schema(Cache::read($this->name.'_selectable_schema', 'salesforce'))) {
            $this->schema($this->selectable_fields);
            Cache::write($this->name.'_selectable_schema', $this->selectable_fields, 'salesforce');
        }
    }

    public function beforeSave($event, $entity, $options) {
        if (!$this->schema(Cache::read($this->name.'_updatable_schema', 'salesforce'))) {
            $this->schema($this->updatable_fields);
            Cache::write($this->name.'_updatable_schema', $this->updatable_fields, 'salesforce');
        }
    }
}
