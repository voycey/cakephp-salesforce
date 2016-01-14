CakePHP Salesforce Enterprise Plugin
=========================
A plugin and datasource that allows access to salesforce via CakePHP CRUD methods for Salesforce Enterprise Entities.

I have decided to release this as Open Source as we are moving away from this method, I can offer basic support for this, if you have any alterations to make please submit a pull request and ill merge it in when I can!

**For Cake 3.x I have started on a new style data source which can be found at https://github.com/voycey/salesforce-datasource-cakephp-3.x** (Currently in early alpha)

***
@Requires Force.com / Developer Force PHP Toolkit

@Requires CakePHP 2.x (Tested on Cake 2.7)

@Requires Enterprise Salesforce

@Requires APC Enabled (Can be changed in code)

@Recommended:

* Your Enterprise WSDL
* Knowledge of Salesforce API Versions

***

Background
----------

This plugin was created to allow easy interaction with a Salesforce Enterprise
instance either by using a WSDL or by automagically fetching the fields for a
specific entity. It provides a unified method of querying, editing, saving and
deleting on several (tested) entities (Currently **Contacts**, **Leads** and
**Accounts**), and it **SHOULD** work on all other entities!

###What does it provide?
Basically it allows you to use standard CakePHP find(), save() and delete()
methods on models which map to Salesforce Entities. This means that if you
want to find all users who work for X company you could simply issue this:

```php
$this->SalesforceContact->find('all', array('conditions' => array('Company' =>
'Acme inc')));
```

And this will return you a lovely standard CakePHP data array which you can
manipulate and use however you want.

###Why Is it needed?
Salesforce has a weird way of allowing you to query it's entities, everything
would be really simple if with SOQL (with salesforce's own flavour of SQL) you
could simply issue a "SELECT * FROM Contacts" - however you cant do this - you
need to specify which fields you want to select. This is fine if you only use
the standard fields but probably 95% of all Enterprise instances of Salesforce
have custom fields implemented.

###Why is it unique?
Surely it is just a SOAP client I hear you asking? Well yes and no! It uses
the Developer force toolkit as transport to fetch the items but it does a fair
few clever things underneath the hood aswell, **Maybe most importantly it
prevents the need for having to pass in a HUGE list of fields to each request
you want to make to Salesforce or having to create custom queries - it can all
be done with cakes "find" method** the prime of which is the ability to
function without a WSDL file. The WSDL file describes your companies data
structures, it is useful for querying a web service but again the Salesforce
one has a few quirks, namely with how it labels specific fields and handles
ID's, and because of this getting a CakePHP schema to function properly is a
little like smashing your head against a brick wall. 

<!--###Why Am I selling this?
Well the version I used in the project I did was pretty specific to my project
so I have left that one alone but I have created this Plugin to allow it to
help other people out, With all of the research and coding of the Datasource
first time around I think that this probably took me close on 2 weeks to get
right, I have drawn on that Knowledge and created this simpler version and
packaged it up into a Plugin to hopefully help anyone else that needs a "drop
in" Salesforce integration. -->


#Installation

1. Clone the Developerforce PHP toolkit into app/Vendor/salesforce
   you should have a path that looks like app/Vendor/salesforce/soapclient
2. Install this Plugin into app/Plugins/Salesforce
3. Place the following code into database.php

```php
 var $sflive = array(
            'datasource' => 'SalesforceSource',
            'standard_wsdl' => 'enterprise.wsdl.xml',
            'dynamic_mode' => false,
            'my_wsdl' => 'enterprise.wsdl.xml',//optional but recommended
            'username' => 'YourSalesForceUsername',
            'password' => 'YourSFPassword+SecurityToken'
        );
```
4. Download your enterprise.wsdl.xml and place it in app/Config (if you dont plan on using this see "Development Mode" below
5. Enable the plugin in your bootstrap.php:

```php
CakePlugin::load(
        array(
            'Salesforce' => array('bootstrap' => true)
  )
);
```

Ok so that is the basic setup now it needs a little configuration in order to
match your Salesforce setup.

One of the other quirks of Salesforce is that it has different API versions
that have different functionality (AND different default fields). This is
where you might fall down with it:

1. First open up your enterprise.wsdl.xml and look in the comments at the top
   for the following lines:
```
Salesforce.com Enterprise Web Services API Version 29.0
Generated on 2013-11-29 01:39:56 +0000.
```
That is your API Version!

2. Now you need to tell the soapclient to use this XML Version:
in the file app/Vendor/salesforce/soapclient/SForceBaseClient.php find the
following lines and ensure they are set to the same API version as the version
above:

```php
class SforceBaseClient {
    protected $sforce;
  protected $sessionId;
  protected $location;
  protected $version = '29.0';
```

***
##Usage:
So the usage is fairly simple, There are some examples in
app/Plugin/Salesforce/Controllers/TestController.php but essentially it
is:

```php
$this->loadModel('Salesforce.SalesforceContact');
$this->SalesforceContact->find('all', array('conditions' => array('Company' =>
'Acme inc')));
```

or if you want to interact with Accounts

```php
$this->loadModel('Salesforce.SalesforceAccount');
$this->SalesforceAccount->find('all', array('conditions' => array('Name' =>
'Acme inc')));
```

##WAIT! I want to interact with something like Contracts
Ok so as stated this is untested (Mostly because I dont have any contracts to
test with) **BUT** there is no reason why this shouldn't work, the plugin is
agnostic of the entity that it is using as it simply issues commands via SOQL
and then processes the return data. I would say that if you can fetch fields
using the Salesforce Developer Workbench (Google it!) then you can use this to
do it.

###How?
The easiest way would be to just create some new models for the entities you
want to access in app/Plugin/Salesforce/Model - for example:
######SalesforceContract.php
```php
    App::uses('Salesforce', 'Salesforce.Model');
    App::import('Utility', 'Xml');
    /**
     * Class SalesforceContract
     */

    class SalesforceContract extends Salesforce {

        public function __construct($id = false, $table = null, $ds = null) {
            $this->name = "Contract";
            parent::__construct($id, $table, $ds);

        }
    }
?>
```

This would then allow you to query the Contract table in Salesforce using

```php 
$this->loadModel('Salesforce.SalesforceContract');
$this->SalesforceContract->find('all', array('conditions' => array('Name' =>
'Acme inc')));
```


##Advanced Configuration
So I have tried to keep this as simple as possible with not much configuration
other than absolutely necessary but the plugin does have a few options:

###Development Mode
If you set **dynamic_mode = true** in database.php this will then not use your
WSDL, it will try and create a schema from your Salesforce instance for the
model you are using, this isnt 100% foolproof but if you are developing a lot
and people are making changes to your Saleforce instance regularly this will
be a godsend, mostly because Salesforce gets really pissy when you try and
include fields that arent there (Or Omit fields that it thinks you should
have). 

It should cache the schema it creates but obviously fetching this schema from
Salesforce takes time so using this on a production server really isnt
recommended.

This is used instead of the "Standard WSDL" that is provided by Salesforce -
mostly because that Standard WSDL wont include all of your custom fields!


Troubleshooting
---------------

:I have created new fields and they show in the result array from Salesforce but the value hasn't been updated.

You probably haven't downloaded the updated wsdl from Salesforce - if you are using development mode try clearing your cache as it caches the schema.

