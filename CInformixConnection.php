<?php

/**
 * CInformixSchema class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiinformix
 */

/**
 * CInformixConnection represents a connection to a informix database.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiinformix
 */
class CInformixConnection extends CDbConnection {

    public $driverMap = array(
        'informix' => 'CInformixSchema', // Informix driver
    );

}

Yii::import("ext.yiinformix.CInformixTableSchema");
Yii::import("ext.yiinformix.CInformixSchema");
Yii::import("ext.yiinformix.CInformixColumnSchema");
Yii::import("ext.yiinformix.CInformixTableSchema");
Yii::import("ext.yiinformix.CInformixCommandBuilder");
