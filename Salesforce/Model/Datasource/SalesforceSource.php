<?php

/*  Cakephp 2.x Salesforce Datasource
    Website - http://www.danielvoyce.com
    Author - Daniel Voyce
*/

    App::uses('HttpSocket', 'Network/Http');
    App::uses('Model', 'Datasource');

    class SalesforceSource extends DataSource {

        /**
         * Description
         * @var string
         */
        public $description = 'Salesforce Enterprise Datasource';

        /**
         * The SoapClient instance
         *
         * @var object
         */
        public $client = null;

        /**
         * The current connection status
         * @var boolean
         */
        public $connected = false;

        /**
         * Value to ensure we dont retrieve the schema multiple times
         * @var bool
         */
        public $_schemaReceived = false;

        /**
         * baseConfig - given in database.php
         * @var array
         */
        public $_baseConfig = array(
            'wsdl'     => '',
            'username' => '',
            'password' => '',
            'my_wsdl'  => ''
        );

        /**
         * Empty Schema
         * @var array
         */
        public $_schema = array();

        /**
         * Constructor
         * @param array $config An array defining the configuration settings
         */
        public function __construct($config) {
            //Construct API version in this to go to SalesforceBaseClass!
            parent::__construct($config);
            $this->_baseConfig = $config;
            $this->connect();
        }

        /**
         * Gets the schema of the model from Salesforce, transforms it a bit for cake
         * and writes it to $this->_schema
         * @param $modelname
         * @return mixed
         */
        public function getSchema($modelname) {
            ////$this->connect();
            if(!$cached_schema = Cache::read($modelname . "_schema", 'short')) {
                $sfschema = $this->client->describeSObject($modelname);
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

                    if ($field->length > 0) {
                        $newSchema[$field->name] = array('type' => $field->type, 'length' => $field->length, 'null' => $field->nillable);
                    } else {
                        $newSchema[$field->name] = array('type' => $field->type, 'null' => $field->nillable);
                    }
                }
                $this->_schemaReceived = true;
                $this->_schema = $newSchema;
                Cache::write($modelname."_schema", $newSchema, "short");
                return $newSchema;
            } else {
                $this->_schema = $cached_schema;
                return $cached_schema;
            }
        }

        /**
         * Use WSDL for basic fields first of all
         * passes the salesforce credentals for login
         * @return boolean True on success, false on failure
         */
        public function connect() {
                //Vendor loading seems to be picky using App::import
                require_once App::Path('Vendor')[0] . 'salesforce/soapclient/SforceEnterpriseClient.php';

                if(empty($this->config['my_wsdl'])) {
                    $wsdl = App::Path('Vendor')[0] . 'salesforce/soapclient/' . $this->config['standard_wsdl'];
                } else {
                    $wsdl = APP ."Config" .DS  . $this->config['my_wsdl'];
                }
                $mySforceConnection = new SforceEnterpriseClient(); //Dont forget to Change your API version in here!
                $mySoapClient = $mySforceConnection->createConnection($wsdl);
                $sflogin = (array)Cache::read('salesforce_login', 'short');
                if(!empty($sflogin['sessionId'])) {
                    $mySforceConnection->setSessionHeader($sflogin['sessionId']);
                    $mySforceConnection->setEndPoint($sflogin['serverUrl']);
                } else {
                    $mylogin = $mySforceConnection->login($this->config['username'], $this->config['password']);
                    $sflogin = array('sessionId' => $mylogin->sessionId, 'serverUrl' => $mylogin->serverUrl);
                    Cache::write('salesforce_login', $sflogin, 'short');
                }

                $this->client = $mySforceConnection;
                $this->connected = true;
            return $this->connected;
        }

        /**
         * Clears the connections and logs the user out
         * @return boolean True
         */
        public function close() {
            $this->client = null;
            $this->connected = false;
            if ($this->client instanceof SforceEnterpriseClient) {
                return $this->client->logout();
            } else {
                return true;
            }

        }

        /**
         * Checks if connected
         * @return bool
         */
        public function isConnected() {
            return $this->connected;
        }

        /**
         * Available SOAP Methods (Not used)
         * @return array List of SOAP methods
         */
        public function listSources($data = null) {
            return $this->client->getFunctions();
        }

        /**
         * Standard describe function
         * @param Model|string $model
         * @return array
         */
        public function describe($model) {
            return $model->_schema;
            //return $this->_schema;
        }

        /**
         * API compatible create method to allow saving
         * @param Model $model
         * @param null $fields
         * @param null $values
         * @return bool
         */
        public function create(Model $model, $fields = null, $values = null) {
            $data = array_combine($fields, $values);
            if (array_key_exists('Id', $data)) {
                $response = $this->sfUpdate($data, $model->name);
                return ($response);
            }
            $data = (object)$data;
            try {
                $response = $this->client->create(array($data), $model->name);
                $this->log($data);
            } catch (Exception $e) {
                $response->errorMessages = array($e->faultstring, $this->client->getLastRequest());
                $this->log("Error in Create: ". $response->errorMessages);
                return false;
            }

            return ($response); //This should return the ID of the created record?
        }

        /**
         * API compatible read function
         * @param Model $model
         * @param array $queryData
         * @param null $recursive
         * @return array|mixed
         */
        public function read(Model $model, $queryData = array(), $recursive = null) {
            $limit = "";
            $conditions = array();
            if (!empty($queryData['limit']) && is_numeric($queryData['limit'])) {
                $limit = "LIMIT " . $queryData['limit'];
            }
            if (count($queryData['conditions']) > 0) {
                foreach ($queryData['conditions'] as $key=>$value) {
                    $conditions[] = $key . " = '" . $value. "'";
                }
            }
            if($queryData['fields'] === 'COUNT') {
                $sfq = "SELECT COUNT (Id) FROM {$model->name} WHERE " . implode(" AND ", $conditions);
                $results = $this->query($sfq);
                return array(array(array('count' => $results[0]['any']['expr0'])));
            } else {
                if(is_array($conditions) && !empty($conditions)) {
                    if (is_array($queryData['fields']) && count($queryData['fields']) > 0) {
                        $sfq = "SELECT " . implode(",", $queryData['fields']) . " FROM {$model->name} WHERE " . implode(" AND ", $conditions) . " " . $limit;
                    } else {
                        $sfq = "SELECT " . implode(",", array_keys($model->_schema)) . " FROM {$model->name } WHERE " . implode(" AND ", $conditions) . " " . $limit;
                    }
                } else {
                    if (is_array($queryData['fields']) && count($queryData['fields']) > 0) {
                        $sfq = "SELECT " . implode(",", $queryData['fields']) . " FROM {$model->name} " . $limit;
                    } else {
                        $sfq = "SELECT " . implode(",", array_keys($model->_schema)) . " FROM {$model->name } " . $limit;
                    }
                }
            }
            $results = $this->query($sfq);
            return $this->cakeify_results($model, $results);

        }

        /**
         * API compatible update function
         * @param Model $model
         * @param null $fields
         * @param null $values
         * @param null $conditions
         * @return bool
         */
        public function update(Model $model, $fields = null, $values = null, $conditions = null) {
            $data = (object)array_combine($fields, $values);
            return $this->sfUpdate($data, $model->name);
        }

        /**
         * Non-API compatible update for updating a record containing an Id field
         * pass the sObject query as the only pram
         * @return mixed Returns the soql result object array result on success, false on failure
         */
        public function sfUpdate($sOBject = null, $type = 'Contact') {
            $response = false;
            $this->error = false;
            try {
                ////$this->connect();
                $response = $this->client->update(array($sOBject), $type);

            } catch (Exception $e) {
                print_r($mySforceConnection->getLastRequest());
                echo $e->faultstring;
            }

            return ($response);
        }

        /**
         * Executes SOQL against the API
         * @return mixed
         */
        public function query($Query = null) {
            $response = false;
            $this->error = false;
            try {
                $response = $this->client->query($Query);

            } catch (Exception $e) {
                echo $e->faultstring;
            }

            return ($this->__getRecordData($response));
        }

        /**
         * Convert the stdClass Object into a cake style array
         * @param $recordObject
         * @return array
         *
         */
        function __getRecordData($salesforceObject) {
            $recordArray = array();
            $salesforceArray = (array)$salesforceObject;
            foreach ((array)$salesforceArray['records'] as $recordObject) {
                $recordArray[] = (array)$recordObject;
            }

            return $recordArray;
        }

        /**
         * Takes a standard flat array as produced by Salesforce and translates it to a cake-style results array
         * @param Model $model
         * @return array
         */
        public function cakeify_results(Model $model, $results) {
            $newResults = array();
            foreach ($results as $result) {
                $newResults[] = array($model->alias => $result);
            }
            return $newResults;
        }

        public function calculate(Model $model, $func, $params = array()) {
            return 'COUNT';
        }

        /**
         * Deletes a salesforce Record
         * @return mixed
         */
        public function delete(Model $model, $Id = null) {
            $IdArray = array();
            foreach ($Id as $key=>$value) {
                //get the ids and translate them to Salesforce compatible ones
                $IdArray[] = $value;
            }

            $response = false;
            $this->error = false;
            try {
                $response = $this->client->delete($IdArray);

            } catch (Exception $e) {
                echo $e->faultstring;
            }

            return ($response);
        }

        /**
         * Shows an error message and outputs the SOAP result if passed
         *
         * @param string $result A SOAP result
         * @return string The last SOAP response
         */
        public function showError($result = null) {
            if (Configure::read() > 0) {
                if ($this->error) {
                    trigger_error('<span style = "color:Red;text-align:left"><b>SOAP Error:</b> <pre>' . print_r($this->error) . '</pre></span>', E_USER_WARNING);
                }
                if ($result) {
                    e(sprintf("<p><b>Result:</b> %s </p>", $result));
                }
            }
        }

    }

?>
