<?php

/**
 * CInformixCommandBuilder class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiinformix
 */

/**
 * CInformixCommandBuilder provides basic methods to create query commands for tables for Informix Servers.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiinformix
 */
class CInformixCommandBuilder extends CDbCommandBuilder {

    /**
     * 
     * @param string $sql SQL query string.
     * @param integer $limit maximum number of rows, 0 to ignore limit.
     * @param integer $offset row offset, 0 to ignore offset.
     * @return string SQL with limit and offset.
     */
    public function applyLimit($sql, $limit, $offset) {
        $limit = $limit !== null ? (int) $limit : 0;
        $offset = $offset !== null ? (int) $offset : 0;

        if ($offset > 0) { //just limit
            $sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(\s+SKIP\s+\d+\s*)?/i', "\\1SELECT\\2 SKIP $offset", $sql);
        } else {
            $sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(\s+SKIP\s+\d+\s*)?/i', "\\1SELECT\\2 ", $sql);
        }
        if ($limit > 0) { //just offset
            $sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(\s+SKIP\s+\d+\s*)?(\s*LIMIT\s+\d+\s*)?/i', "\\1SELECT\\2\\3 LIMIT $limit ", $sql);
        } else {
            $sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(\s+SKIP\s+\d+\s*)?(\s*LIMIT\s+\d+\s*)?/i', "\\1SELECT\\2\\3 ", $sql);
        }
        return $sql;
    }

    /**
     * Creates an UPDATE command.
     * @param CDbTableSchema $table the table schema ({@link CDbTableSchema}) or the table name (string).
     * @param array $data list of columns to be updated (name=>value)
     * @param CDbCriteria $criteria the query criteria
     * @return CDbCommand update command.
     */
    public function createUpdateCommand($table, $data, $criteria) {
        foreach ($data as $name => $value) {
            if (($column = $table->getColumn($name)) !== null) {
                if (is_string($table->primaryKey) && $table->primaryKey == $column->name) {
                    unset($data[$name]);
                    continue;
                }
            }
        }
        return parent::createUpdateCommand($table, $data, $criteria);
    }

}
