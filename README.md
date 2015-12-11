# Salesforce Datasource plugin for CakePHP 3.x
[![Latest Stable Version](https://poser.pugx.org/voycey/salesforce-datasource-cakephp-3.x/v/stable)](https://packagist.org/packages/voycey/salesforce-datasource-cakephp-3.x) [![Total Downloads](https://poser.pugx.org/voycey/salesforce-datasource-cakephp-3.x/downloads)](https://packagist.org/packages/voycey/salesforce-datasource-cakephp-3.x) [![Latest Unstable Version](https://poser.pugx.org/voycey/salesforce-datasource-cakephp-3.x/v/unstable)](https://packagist.org/packages/voycey/salesforce-datasource-cakephp-3.x) [![License](https://poser.pugx.org/voycey/salesforce-datasource-cakephp-3.x/license)](https://packagist.org/packages/voycey/salesforce-datasource-cakephp-3.x)

## Installation

**Please note - this plugin is still under active development and is very much in a pre-alpha state - Reading SF records seems to work fine and Saving works in a limited fashion but it is not API compatible with the CakePHP 3.x ORM just yet - I am working on it!**
    
You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require voycey/salesforce-datasource-cakephp-3.x
```

## Information

This plugin is in EARLY Alpha release, there are currently no tests, and the amount of testing I have done is limited
I am creating this github repo to allow me to import it in to my project via composer to test it's integration that way.

This has been somewhat of a learning curve for me, due to the nature of how Datasources are created in 3.x this is quite 
possibly very "hacky", however I have tried to follow the patterns of the other SQL-like datasources.

Also there is NO schema caching so this definitely isn't ready for production use (I am getting onto this next)

## Notes

1. This uses the PHP-Force.com toolkit as a dependency
2. This uses SOAP and NOT REST (Because of Reasons™)
3. I repeat - this is like version 0.0.1a - I havent even tested saving an entity yet
4. It will likely stay this way on Github for a while as I am developing on a private repo but ill update regularly once I have some good features
5. Feel free to submit pull requests - here are a few examples of things I'd like to implement / test
    1. Associations between native Cake Tables & API Tables
    2. Tests (Most can probably be ripped from the core tests I assume)
    3. Testing with all SObjects (currently I have only tested with Contact but from my experience with my version 2.x datasource this is usually enoguh to work with all SObjects
    
    
    
## Usage

If you are feeling brave then here are some basic instructions to getting it working

1. Do composer reqire as above
2. Add ```Plugin::load('Salesforce', ['bootstrap' => false, 'routes' => true]);``` to your bootstrap.php
3. Create the connection in app.php like this:

    ```php
              'salesforce' => [
                    'className' => 'Salesforce\Database\MyConnection',
                    'driver' => 'Salesforce\Database\Driver\Salesforce',
                    'persistent' => false,
                    'username' => getenv("SF_USER"),
                    'password' => getenv("SF_PASSWORD"),
                    'quoteIdentifiers' => false,
                    'my_wsdl' => 'enterprise.wsdl.xml'
                ],
    ```

        **Your SF_PASSWORD should be your password + security token**
 
4. Get your Enterprise WSDL and place it in the ```config``` directory
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


Then browse to /salesforces and you should have a couple of the standard Salesforce records. If not then go back and repeat these steps. If you get an interesting error message then.... well sorry, I'm sure it will get fixed as I use it more
