<?php
    App::uses('Salesforce', 'Salesforce.Model');
    App::import('Utility', 'Xml');
    /**
     * Class SalesforceContact
     */

    class SalesforceLead extends Salesforce {

        public function __construct($id = false, $table = null, $ds = null) {
            $this->name = "Lead";
            parent::__construct($id, $table, $ds);

        }
    }
?>