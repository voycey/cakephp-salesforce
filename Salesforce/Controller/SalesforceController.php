<?php
    App::uses('AppController', 'Controller');
    class SalesforceController extends AppController {

        public $name = 'Salesforce';
        public $helpers = array('Html', 'Form');


        function lead_test() {
            $this->loadModel('SalesforceLead');
            $data = $this->SalesforceLead->find('all');
            $this->set(compact('data','result'));
            $this->render('index');
        }
        function account_test() {
            $this->loadModel('SalesforceAccount');
            $data = $this->SalesforceAccount->find('all', array('conditions' => array('Name' => 'MON')));
            $result = $this->SalesforceAccount->save(array('Name' => 'Dan Test Account 1'));
            $this->set(compact('data','result'));
            $this->render('index');

        }
        function contacts() {
            $this->loadModel('SalesforceContact');
            $data = $this->SalesforceContact->find('all', array('conditions' => array('Email' => 'dan444@702010forum.com')));
            $this->set(compact('data'));
            $this->render('index');
        }

        function contacts_save() {
            $this->loadModel('SalesforceContact');
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
            $this->set(compact('result', 'data'));
            $this->render('index');
        }

    }
?>