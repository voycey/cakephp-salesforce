<?php
    App::uses('AppController', 'Controller');
    class TestController extends AppController {

        public $name = 'Test';
        public $helpers = array('Html', 'Form');


        function lead_test() {
	        $this->autoRender = false;
            $this->loadModel('Salesforce.SalesforceLead');
            $data = $this->SalesforceLead->find('all');
            pr($data);
        }
        function account_test() {
	        $this->autoRender = false;
            $this->loadModel('Salesforce.SalesforceAccount');
            $data = $this->SalesforceAccount->find('all', array('conditions' => array('Name' => 'MON')));
            $result = $this->SalesforceAccount->save(array('Name' => 'Dan Plugin Test 2'));
	        pr($data);
	        pr($result);
        }

        function contacts() {
	        $this->autoRender = false;
            $this->loadModel('Salesforce.SalesforceContact');
            $data = $this->SalesforceContact->find('all', array('conditions' => array('Email' => 'test@test.com')));
            pr($data);
        }

        function contacts_save() {
	        $this->autoRender = false;
            $this->loadModel('Salesforce.SalesforceContact');
            $this->SalesforceContact->create();
            $data = array(
                array(
                    'FirstName' => 'DeleteMe 1',
                    'LastName' => 'DeleteMe 1',
                    'Phone' => '1234567890',
                    'BirthDate' => '1957-01-25',
                    'Email' => 'hello@danielvoyce.com'
                ),
                array (
                    'FirstName' => 'DeleteMe 2',
                    'LastName' => 'DeleteMe 2',
                    'Phone' => '1234567890',
                    'BirthDate' => '1957-01-25',
                    'Email' => 'hello@danielvoyce.com'
                ),
                array(
                    'FirstName' => 'DeleteMe 3',
                    'LastName' => 'DeleteMe 3',
                    'Phone' => '1234567890',
                    'BirthDate' => '1957-01-25',
                    'Email' => 'hello@danielvoyce.com'
                ),
                array(
                    'FirstName' => 'DeleteMe 4',
                    'LastName' => 'DeleteMe 4',
                    'Phone' => '1234567890',
                    'BirthDate' => '1957-01-25',
                    'Email' => 'hello@danielvoyce.com'
                )
            );
            $result = $this->SalesforceContact->saveAll($data);
            pr($data);
	        pr($result);
        }

    }
?>