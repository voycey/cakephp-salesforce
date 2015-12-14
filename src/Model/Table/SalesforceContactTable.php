<?php
namespace Salesforce\Model\Table;

use Salesforce\Model\Entity\Salesforce;

class SalesforceContactTable extends SalesforcesTable
{
    public $name = "Contact";

    /**
     * Initialize method
     *
     * @param  array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('Contact');
        $this->displayField('Name');
        $this->primaryKey('Id');
    }
}
