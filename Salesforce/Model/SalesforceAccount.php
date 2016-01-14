<?php
    App::uses('Salesforce', 'Salesforce.Model');
    App::import('Utility', 'Xml');
    /**
     * Class SalesforceContact
     */

    class SalesforceAccount extends Salesforce {

        public function __construct($id = false, $table = null, $ds = null) {
            $this->name = "Account";
            parent::__construct($id, $table, $ds);

        }
    }
?>