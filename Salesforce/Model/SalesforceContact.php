<?php
    App::uses('Salesforce', 'Salesforce.Model');
    App::import('Utility', 'Xml');
    /**
     * Class SalesforceContact
     */

    class SalesforceContact extends Salesforce {

        public function __construct($id = false, $table = null, $ds = null) {
            $this->name = "Contact";
            parent::__construct($id, $table, $ds);

        }
    }
?>