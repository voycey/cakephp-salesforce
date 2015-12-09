<?php
namespace Salesforce\Model\Table;

use Salesforce\Model\Entity\Salesforce;
use Salesforce\ORM\SalesforceQuery;
use Salesforce\ORM\SalesforceTable;
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
        $this->schema($new_array);
    }
}
