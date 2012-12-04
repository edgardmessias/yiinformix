<?php

/**
 * CInformixSchema class file.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @link https://github.com/edgardmessias/yiinformix
 */

/**
 * CInformixSchema is the class for retrieving metadata information from a PostgreSQL database.
 *
 * @author Edgard L. Messias <edgardmessias@gmail.com>
 * @package ext.yiinformix
 */
class CInformixSchema extends CDbSchema {

    const DEFAULT_SCHEMA = 'informix';

    /**
     * @var array the abstract column types mapped to physical column types.
     */
    public $columnTypes = array(
        'pk' => 'serial NOT NULL PRIMARY KEY',
        'string' => 'varchar(255)',
        'text' => 'text',
        'integer' => 'integer',
        'float' => 'float',
        'decimal' => 'decimal',
        'datetime' => 'datetime year to second',
        'timestamp' => 'datetime year to second',
        'time' => 'datetime hour to second',
        'date' => 'datetime year to day',
        'binary' => 'byte',
        'boolean' => 'boolean',
        'money' => 'money',
    );
    private $_sequences = array();

    /**
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return CDbTableSchema driver dependent table metadata.
     */
    protected function loadTable($name) {
        $table = new CInformixTableSchema;
        $this->resolveTableNames($table, $name);
        if (!$this->findColumns($table))
            return null;
        $this->findConstraints($table);

        if (is_string($table->primaryKey) && isset($this->_sequences[$table->rawName . '.' . $table->primaryKey]))
            $table->sequenceName = $this->_sequences[$table->rawName . '.' . $table->primaryKey];
        elseif (is_array($table->primaryKey)) {
            foreach ($table->primaryKey as $pk) {
                if (isset($this->_sequences[$table->rawName . '.' . $pk])) {
                    $table->sequenceName = $this->_sequences[$table->rawName . '.' . $pk];
                    break;
                }
            }
        }
//        if (is_string($table->primaryKey))
//            $table->sequenceName = $this->_sequences[$table->rawName . '.' . $table->primaryKey];
//        elseif (is_array($table->primaryKey)) {
//            foreach ($table->primaryKey as $pk) {
//                if (isset($this->_sequences[$table->rawName . '.' . $pk])) {
//                    $table->sequenceName = $this->_sequences[$table->rawName . '.' . $pk];
//                    break;
//                }
//            }
//        }
        return $table;
    }

    /**
     * Quotes a table name for use in a query.
     * A simple table name does not schema prefix.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name) {
        return $name;
    }

    /**
     * Quotes a column name for use in a query.
     * A simple column name does not contain prefix.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name) {
        return $name;
    }

    /**
     * Generates various kinds of table names.
     * @param CInformixTableSchema $table the table instance
     * @param string $name the unquoted table name
     */
    protected function resolveTableNames($table, $name) {
        $parts = explode('.', str_replace('"', '', $name));
        if (isset($parts[1])) {
            $schemaName = $parts[0];
            $tableName = $parts[1];
        } else {
            $schemaName = self::DEFAULT_SCHEMA;
            $tableName = $parts[0];
        }

        $table->name = $tableName;
        $table->schemaName = $schemaName;
        if ($schemaName === self::DEFAULT_SCHEMA)
            $table->rawName = $this->quoteTableName($tableName);
        else
            $table->rawName = $this->quoteTableName($schemaName) . '.' . $this->quoteTableName($tableName);
    }

    /**
     * Collects the table column metadata.
     * @param CInformixTableSchema $table the table metadata
     * @return boolean whether the table exists in the database
     */
    protected function findColumns($table) {
//        return true;
        $sql = <<<EOD
SELECT syscolumns.colname,
       syscolumns.colmin,
       syscolumns.colmax,
    NOT (coltype>255) AS allownull,
    CASE MOD(coltype, 256)
        WHEN  0 THEN 'char'
        WHEN  1 THEN 'smallint'
        WHEN  2 THEN 'integer'
        WHEN  3 THEN 'float'
        WHEN  4 THEN 'smallfloat'
        WHEN  5 THEN 'decimal'
        WHEN  6 THEN 'serial'
        WHEN  7 THEN 'date'
        WHEN  8 THEN 'money'
        WHEN  9 THEN 'null'
        WHEN 10 THEN 'datetime'
        WHEN 11 THEN 'byte'
        WHEN 12 THEN 'text'
        WHEN 13 THEN 'varchar'
        WHEN 14 THEN 'interval'
        WHEN 15 THEN 'nchar'
        WHEN 16 THEN 'nvarchar'
        WHEN 17 THEN 'int8'
        WHEN 18 THEN 'serial8'
        WHEN 19 THEN 'set'
        WHEN 20 THEN 'multiset'
        WHEN 21 THEN 'list'
        WHEN 22 THEN 'Unnamed ROW'
        WHEN 40 THEN sysxtdtypes.name
        WHEN 41 THEN sysxtdtypes.name
        WHEN 52 THEN 'bigint'
        WHEN 53 THEN 'bigserial'
        WHEN 4118 THEN 'Named ROW'
        ELSE '???'
    END AS type,
    CASE
        WHEN mod(coltype,256) in (5,8) THEN trunc(collength/256)||","||mod(collength,256)                
        WHEN mod(coltype,256) in (10,14) THEN                   
            CASE trunc(mod(collength,256)/16)                        
                WHEN  0 THEN "YEAR"                        
                WHEN  2 THEN "MONTH"                        
                WHEN  4 THEN "DAY"                        
                WHEN  6 THEN "HOUR"                        
                WHEN  8 THEN "MINUTE"                        
                WHEN 10 THEN "SECOND"                        
                WHEN 11 THEN "FRACTION(1)"                        
                WHEN 12 THEN "FRACTION(2)"                        
                WHEN 13 THEN "FRACTION(3)"                        
                WHEN 14 THEN "FRACTION(4)"                        
                WHEN 15 THEN "FRACTION(5)"                     
            END ||"("||trunc(collength/256)+trunc(mod(collength,256)/16)-mod(collength,16)||") : "||                       
            CASE mod(collength,16)                        
                WHEN  0 THEN "YEAR"                        
                WHEN  2 THEN "MONTH"                        
                WHEN  4 THEN "DAY"                        
                WHEN  6 THEN "HOUR"                        
                WHEN  8 THEN "MINUTE"                        
                WHEN 10 THEN "SECOND"                        
                WHEN 11 THEN "FRACTION(1)"                        
                WHEN 12 THEN "FRACTION(2)"                        
                WHEN 13 THEN "FRACTION(3)"                        
                WHEN 14 THEN "FRACTION(4)"                        
                WHEN 15 THEN "FRACTION(5)"                     
            END                 
        ELSE ""||collength          
    END collength
FROM systables 
  INNER JOIN syscolumns ON syscolumns.tabid = systables.tabid
  LEFT JOIN sysxtdtypes on sysxtdtypes.extended_id = syscolumns.extended_id
WHERE systables.tabid >= 100
AND   systables.tabname = :table
AND   systables.owner = :schema
ORDER BY syscolumns.colno
EOD;

        $command = $this->getDbConnection()->createCommand($sql);
        $command->bindValue(':table', $table->name);
        $command->bindValue(':schema', $table->schemaName);

        if (($columns = $command->queryAll()) === array())
            return false;

        foreach ($columns as $column) {
            $c = $this->createColumn($column);
            $table->columns[$c->name] = $c;
        }
        return true;
    }

    /**
     * Creates a table column.
     * @param array $column column metadata
     * @return CDbColumnSchema normalized column metadata
     */
    protected function createColumn($column) {
        $c = new CInformixColumnSchema;
        $c->name = $column['COLNAME'];
        $c->rawName = $this->quoteColumnName($c->name);
        $c->allowNull = $column['ALLOWNULL'];
        $c->isPrimaryKey = false;
        $c->isForeignKey = false;

        if (strpos($column['TYPE'], 'char') !== false || strpos($column['TYPE'], 'text') !== false) {
            $c->size = $column['COLLENGTH'];
        } elseif (preg_match('/(real|float|double|decimal)/', $column['TYPE'])) {
            $length = explode(",", $column['COLLENGTH']);
            $c->size = $length[0];
            $c->precision = $length[0];
            $c->scale = $length[1];
        }


        if (stripos($column['TYPE'], 'serial') !== false) {
            $c->autoIncrement = true;
        } else {
            $c->autoIncrement = false;
        }

        $c->init($column['TYPE'], null);
        return $c;
    }

    /**
     * Collects the primary and foreign key column details for the given table.
     * @param CInformixTableSchema $table the table metadata
     */
    protected function findConstraints($table) {
        $sql = <<<EOD
SELECT sysconstraints.constrtype, sysconstraints.idxname
FROM systables 
  INNER JOIN sysconstraints ON sysconstraints.tabid = systables.tabid
WHERE systables.tabname = :table
AND   systables.owner = :schema;
   
EOD;
        $command = $this->getDbConnection()->createCommand($sql);
        $command->bindValue(':table', $table->name);
        $command->bindValue(':schema', $table->schemaName);
        foreach ($command->queryAll() as $row) {
            if ($row['CONSTRTYPE'] === 'P') { // primary key
                $this->findPrimaryKey($table, $row['IDXNAME']);
            } elseif ($row['CONSTRTYPE'] === 'R') { // foreign key
                $this->findForeignKey($table, $row['IDXNAME']);
            }
        }
    }

    /**
     * Collects primary key information.
     * @param CInformixTableSchema $table the table metadata
     * @param string $indice pgsql primary key index list
     */
    protected function findPrimaryKey($table, $indice) {
        $sqlArray = array();
        for ($i = 1; $i <= 16; $i++) {
            $sqlTmp = <<<EOD
SELECT syscolumns.colname
FROM sysindexes 
  INNER JOIN syscolumns ON syscolumns.tabid = sysindexes.tabid AND syscolumns.colno = sysindexes.part$i
WHERE idxname = :indice$i
EOD;
            $sqlArray[] = $sqlTmp;
        }
        $sql = implode(" UNION ALL ", $sqlArray);
        $command = $this->getDbConnection()->createCommand($sql);
//        $command->bindValue(':table', $table->name);
//        $command->bindValue(':schema', $table->schemaName);
        for ($i = 1; $i <= 16; $i++) {
            $command->bindValue(":indice$i", $indice);
        }
        foreach ($command->queryAll() as $row) {
            $name = $row['COLNAME'];
            if (isset($table->columns[$name])) {
                $table->columns[$name]->isPrimaryKey = true;
                if ($table->primaryKey === null)
                    $table->primaryKey = $name;
                elseif (is_string($table->primaryKey))
                    $table->primaryKey = array($table->primaryKey, $name);
                else
                    $table->primaryKey[] = $name;
            }
        }
    }

    /**
     * Collects foreign key information.
     * @param CInformixTableSchema $table the table metadata
     * @param string $indice pgsql foreign key definition
     */
    protected function findForeignKey($table, $indice) {
        $sqlArray = array();
        for ($i = 1; $i <= 16; $i++) {
            $sqlTmp = <<<EOD
SELECT syscolumns.colname as columnbase,
       stf.tabname as tablereference,
       sclf.colname as columnreference
FROM sysindexes 
  INNER JOIN sysconstraints ON sysconstraints.idxname = sysindexes.idxname 
  INNER JOIN sysreferences ON sysreferences.constrid = sysconstraints.constrid 
  INNER JOIN syscolumns ON syscolumns.colno = sysindexes.part$i AND syscolumns.tabid = sysindexes.tabid 
  INNER JOIN systables AS stf ON stf.tabid = sysreferences.ptabid 
  INNER JOIN sysconstraints AS scf ON scf.constrid = sysreferences. 'primary' 
  INNER JOIN sysindexes AS sif ON sif.idxname = scf.idxname 
  INNER JOIN syscolumns AS sclf ON sclf.colno = sif.part$i AND sclf.tabid = sysreferences.ptabid
WHERE sysindexes.idxname = :indice$i
EOD;
            $sqlArray[] = $sqlTmp;
        }
        $sql = implode(" UNION ALL ", $sqlArray);
        $command = $this->getDbConnection()->createCommand($sql);
//        $command->bindValue(':table', $table->name);
//        $command->bindValue(':schema', $table->schemaName);
        for ($i = 1; $i <= 16; $i++) {
            $command->bindValue(":indice$i", $indice);
        }
        foreach ($command->queryAll() as $row) {
            $name = $row['COLUMNBASE'];
            if (isset($table->columns[$name])) {
                $table->columns[$name]->isForeignKey = true;
            }
            $table->foreignKeys[$name] = array($row['TABLEREFERENCE'], $row['COLUMNREFERENCE']);
        }
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * If not empty, the returned table names will be prefixed with the schema name.
     * @return array all table names in the database.
     */
    protected function findTableNames($schema = '') {
        if ($schema === '')
            $schema = self::DEFAULT_SCHEMA;

        set_time_limit(600);

        $sql = <<<EOD
SELECT tabname, owner FROM systables
WHERE owner=:schema 
AND   tabtype='T' 
AND   tabid >= 100
AND   tabname LIKE 'frp_%'
EOD;
        $command = $this->getDbConnection()->createCommand($sql);
        $command->bindParam(':schema', $schema);
        $rows = $command->queryAll();
        $names = array();
        foreach ($rows as $row) {
            if ($schema === self::DEFAULT_SCHEMA)
                $names[] = $row['TABNAME'];
            else
                $names[] = $row['OWNER'] . '.' . $row['TABNAME'];
        }
        return $names;
    }

    /**
     * Creates a command builder for the database.
     * This method overrides parent implementation in order to create a Informix specific command builder
     * @return CDbCommandBuilder command builder instance
     */
    protected function createCommandBuilder() {
        return new CInformixCommandBuilder($this);
    }

}
