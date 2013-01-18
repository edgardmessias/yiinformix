yiinformix
==========

Support the informix database in Yii Framework

## Requirements
* PHP module pdo_informix;
* Informix Client SDK installed
 
## Installation
* Install yiinformix extension
* Extract the release file under `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
  'components' => array(
  ...
    'db' => array(
      'connectionString' => 'informix:host=host;service=port;database=database;server=server;protocol=onsoctcp;CLIENT_LOCALE=en_US.utf8;DB_LOCALE=en_US.8859-1;EnableScrollableCursors=1',
      'username' => 'username',
      'password' => 'password',
      'class' => 'ext.yiinformix.CInformixConnection',
    ),
    ...
  ),
...
```

