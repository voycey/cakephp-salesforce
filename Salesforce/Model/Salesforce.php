<?php
    App::uses('Salesforce', 'Model');
    App::uses('AppModel', 'Model');
    App::import('Utility', 'Xml');
    /**
     * Class SalesforceContact
     */

    class Salesforce extends AppModel {
        public $id = null;
        public $name = "Contact";
        public $useDbConfig='sflive';
        public $primaryKey = 'Id';

        public $_schema = array();
        public $useTable = false;

        public function __construct($id = false, $table = null, $ds = null) {
            parent::__construct($id, $table, $ds);
            $datasource = ConnectionManager::getDataSource($this->useDbConfig);
            //$datasource = ConnectionManager::loadDataSource('SalesforceSource');

            if($datasource->_baseConfig['dynamic_mode']) {
                $this->_schema = $datasource->getSchema($this->name);
            } else {
                if(!empty($datasource->_baseConfig['my_wsdl'])) {
                    $wsdl = APP ."Config" .DS  . $datasource->_baseConfig['my_wsdl'];
                } else {
                    $wsdl = App::Path('Vendor')[0] . 'salesforce/soapclient/' . $datasource->_baseConfig['standard_wsdl'];
                }
                $database_fields = Xml::toArray(Xml::build($wsdl));
                $schemas = $database_fields['definitions']['types']['schema'][0]['complexType'];

                $this_wsdl_schema = array();
                foreach ($schemas as $schema) {
                    if($schema['@name'] == $this->name) {
                        $this_wsdl_schema = $schema['complexContent']['extension']['sequence']['element'];
                        break;
                    }
                }
                $names = Hash::extract($this_wsdl_schema, '{n}.@name');
                $types = Hash::extract($this_wsdl_schema, '{n}.@type');
                $new_array = array(
                    'Id' => array('type' => 'string', 'length' => 16)
                );
                $n=0;
                $type_name = "";
                foreach ($names as $name) {
                    if(substr($types[$n],0,3) != "ens") { //we dont want type of ens
                        if(substr($types[$n],4) != "QueryResult") { //Or this

                            if(substr($types[$n],4) == "int") {
                                $type_name = "integer";
                            } elseif (substr($types[$n],4) == "boolean") {
                                $type_name = "boolean";
                            } elseif (substr($types[$n],4) == "dateTime" || substr($types[$n],4) == "date") {
                                $type_name = "datetime";
                            } else {
                                $type_name = "string";
                            }

                            $new_array[$name] = array('type' => $type_name, 'length' => 255);
                        }
                    }
                    $n++;
                }
                $this->_schema = $new_array;
            }
            return true;
        }

        /**
         * This is an override that uses $model->name rather than the Alias
         * Cakephp 2.4.3 currently
         * @param null $id
         * @return bool
         */
        public function exists($id = null) {
            if ($id === null) {
                $id = $this->getID();
            }

            if ($id === false) {
                return false;
            }

            return (bool)$this->find('count', array(
                'conditions' => array(
                    $this->name . '.' . $this->primaryKey => $id
                ),
                'recursive' => -1,
                'callbacks' => false
            ));
        }
    }
?>