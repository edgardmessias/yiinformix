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

    protected function initConnection($pdo) {
        parent::initConnection($pdo);
        $this->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
    }

    public $driverMap = array(
        'informix' => 'CInformixSchema', // Informix driver
    );

    public function getPdoType($type) {

        if ($type == 'NULL') {
            return PDO::PARAM_STR;
        } else {
            return parent::getPdoType($type);
        }
    }

}

$dir = dirname(__FILE__);
$alias = md5($dir);
Yii::setPathOfAlias($alias, $dir);
Yii::import($alias . '.*');
