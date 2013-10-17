<?php

/**
 * CInformixColumnSchema class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiinformix
 */

/**
 * CInformixColumnSchema class describes the column meta data of a Informix table.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiinformix
 */
class CInformixColumnSchema extends CDbColumnSchema {

    /**
     * Extracts the PHP type from DB type.
     * @param string $dbType DB type
     */
    protected function extractType($dbType) {
        $dbType = strtolower(trim($dbType));
        if (strpos($dbType, 'char') !== false || strpos($dbType, 'text') !== false) {
            $this->type = 'string';
        } elseif (strpos($dbType, 'bool') !== false) {
            $this->type = 'boolean';
        } elseif (preg_match('/(real|float|double|decimal|money)/', $dbType)) {
            $this->type = 'double';
        } elseif (preg_match('/(integer|serial|smallint|int8|bigint)/', $dbType)) {
            $this->type = 'integer';
        } else {
            $this->type = 'string';
        }
    }

    /**
     * Extracts size, precision and scale information from column's DB type.
     * @param string $dbType the column's DB type
     */
    protected function extractLimit($dbType) {
        if (!preg_match('/(datetime|interval)/i', $dbType)) {
            parent::extractLimit($dbType);
        }
    }

    /**
     * Extracts the default value for the column.
     * The value is typecasted to correct PHP type.
     * @param mixed $defaultValue the default value obtained from metadata
     */
    protected function extractDefault($defaultValue) {
        if (strtolower($defaultValue) === 't')
            $this->defaultValue = true;
        elseif (strtolower($defaultValue) === 'f')
            $this->defaultValue = false;
        elseif (preg_match('/(CURRENT|DBSERVERNAME|TODAY|USER|NULL)/i', $defaultValue)) {
            $this->defaultValue = null;
        } else {
            parent::extractDefault($defaultValue);
        }
    }

    /**
     * Converts the input value to the type that this column is of.
     * @param mixed $value input value
     * @return mixed converted value
     */
    public function typecast($value) {
        if ($value === null || $value instanceof CDbExpression)
            return $value;
        if ($value === '' && $this->allowNull)
            return $this->type === 'string' ? '' : null;
        switch ($this->type) {
            case 'string': return (string) $value;
            case 'integer': return (integer) $value;
            case 'boolean': return $value ? 't' : 'f';
            case 'double':
            default: return $value;
        }
    }

}
