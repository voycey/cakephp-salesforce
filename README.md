# Salesforce Datasource plugin for CakePHP 3.x
[![Latest Stable Version](https://poser.pugx.org/voycey/cakephp-salesforce/v/stable)](https://packagist.org/packages/voycey/cakephp-salesforce) [![Total Downloads](https://poser.pugx.org/voycey/cakephp-salesforce/downloads)](https://packagist.org/packages/voycey/cakephp-salesforce) [![Latest Unstable Version](https://poser.pugx.org/voycey/cakephp-salesforce/v/unstable)](https://packagist.org/packages/voycey/cakephp-salesforce) [![License](https://poser.pugx.org/voycey/cakephp-salesforce/license)](https://packagist.org/packages/voycey/cakephp-salesforce)

## For earlier versions of CakePHP (2.x)
Please use the 2.x branch and follow the readme instructions under that for usage instructions

## Installation

**This plugin is now in Beta**

* API Compatible Saving & Reading is working
* Schema & Connection Caching is working
* Bear in mind that any API interaction is expensive, you should be using this with a deferred execution method.
 
 
    
You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require voycey/cakephp-salesforce
```

## Information

This has been somewhat of a learning curve for me, due to the nature of how Datasources are created in 3.x.
I have tried to follow the patterns of the other SQL-like datasources, Eager loading is used (as in SQL sources) and there is a limited dialect.

I would have liked to do some processing on the WSDL as I did in my 2.x datasource in order to create the schema however this wasn't possible as
I now needed to see the status of certain fields (readable / updateable) which has caused a need for a further call to the API.

Any API connections are cached for 1 hour (Salesforce timeout is 2 hours), after this time the connection will be refreshed 
(but this shouldn't matter as you are using this with a deferred execution method right?)

## Notes

1. This uses the PHP-Force.com toolkit as a dependency
2. This uses SOAP and NOT REST (Because of Reasons™)
3. I have only tested this with the Contact and Account SObjects, the Contact example is included in the plugin, to create more models to interact with other SObjects copy SalesforceContactTable as a template (see instructions below).
4. Feel free to submit pull requests - here are a few examples of things I'd like to implement / test
    1. Associations between native Cake Tables & API Tables (I'm not sure this is actually possible though!)
    2. Tests (Most can probably be ripped from the core tests I assume) - I don't plan to do many as to test properly would require access to the API.
    3. Testing with all SObjects (currently I have only tested with a few but from experience with my version 2.x datasource this is usually enough to work with all SObjects)
    4. Efficiency increases.
    
    
    
    
## Usage

1. Do composer reqire as above
2. Add ```Plugin::load('Salesforce', ['bootstrap' => true, 'routes' => true]);``` to your bootstrap.php
3. Create the connection in app.php like this:

```php
  'salesforce' => [
     'className' => 'Salesforce\Database\SalesforceConnection',
     'driver' => 'Salesforce\Database\Driver\Salesforce',
     'persistent' => false,
     'username' => getenv("SF_USER"),
     'password' => getenv("SF_PASSWORD"),
     'quoteIdentifiers' => false,
     'my_wsdl' => 'enterprise.wsdl.xml'
  ],
```

        **Your SF_PASSWORD should be your password + security token**
 
4. Get your Enterprise WSDL and place it in the app ```config``` directory
5. Create a test controller that looks something like this

 ```php
  <?php
     namespace App\Controller;
        
     use App\Controller\AppController;
     use Cake\Event\Event;
        
     class SalesforcesController extends AppController 
     {
        
        public function beforeFilter(Event $event)
        {
            parent::beforeFilter($event);
        }
        
        public function index()
        {
            $this->autoRender = false;
            $this->loadModel('Salesforce.SalesforceContact');
            $query = $this->SalesforceContact->find('all')
                        ->select(['Id','Email','LastName'])
                        ->where(['Email' => "info@salesforce.com"]
                    );
    
            foreach ($query as $row) {
              echo "<pre>";
              print_r($row);
              echo "</pre>";
            }
    
        }
     }
 ```        


Then browse to /salesforces and you should have a couple of the standard Salesforce records. If not then go back and repeat these steps. If you get an interesting error message then.... well sorry, I'm sure it will get fixed as I use it more.

## Interfacing with other Salesforce Items

This should simply be a case of extending "SalesforcesTable" rather than Table with your chosen Item (e.g. Account)

```php
 <?php
  namespace App\Model\Table;
  
  use Salesforce\Model\Entity\Salesforce;
  use Salesforce\Model\Table\SalesforcesTable;
  
  class SalesforceAccountTable extends SalesforcesTable
  {
      public $name = "Account";
  
      /**
       * Initialize method
       *
       * @param  array $config The configuration for the Table.
       * @return void
       */
      public function initialize(array $config)
      {
          parent::initialize($config);
  
          $this->table('Account');
          $this->displayField('Name');
          $this->primaryKey('Id');
      }
  }

```
