yiinformix
==========

Support the informix database in Yii Framework

## Installation
* Install yiinformix extension
* Extract the release file under `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
  'import'=>array(
    ...
    'ext.yiinformix.*',
    ...
	),
...
  'components' => array(
  ...
    'db' => array(
      'connectionString' => 'informix:host=host;service=port;database=database;server=server;protocol=onsoctcp',
      'username' => 'username',
      'password' => 'password',
      'class' => 'ext.yiinformix.CInformixConnection',
    ),
    ...
  ),
...
```

