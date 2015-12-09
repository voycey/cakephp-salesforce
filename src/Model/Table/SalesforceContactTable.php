<?php
namespace Salesforce\Model\Table;

use Salesforce\Model\Entity\Salesforce;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Xml;

/**
 * Questions Model
 *
 * @property \Cake\ORM\Association\HasMany $PathwaysAnswers
 * @property \Cake\ORM\Association\HasMany $QuestionParts
 * @property \Cake\ORM\Association\BelongsToMany $Pathways
 */
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
