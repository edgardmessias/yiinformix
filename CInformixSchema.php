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

            if ($c->autoIncrement) {
                $this->_sequences[$table->rawName . '.' . $c->name] = $table->schemaName . '.' . $table->rawName . '.' . $c->name;
            }

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
        $c->name = $column['colname'];
        $c->rawName = $this->quoteColumnName($c->name);
        $c->allowNull = $column['allownull'];
        $c->isPrimaryKey = false;
        $c->isForeignKey = false;

        if (strpos($column['type'], 'char') !== false || strpos($column['type'], 'text') !== false) {
            $c->size = $column['collength'];
        } elseif (preg_match('/(real|float|double|decimal)/', $column['type'])) {
            $length = explode(",", $column['collength']);
            $c->size = $length[0];
            $c->precision = $length[0];
            $c->scale = $length[1];
        }


        if (stripos($column['type'], 'serial') !== false) {
            $c->autoIncrement = true;
        } else {
            $c->autoIncrement = false;
        }

        $c->init($column['type'], null);
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
            if ($row['constrtype'] === 'P') { // primary key
                $this->findPrimaryKey($table, $row['idxname']);
            } elseif ($row['constrtype'] === 'R') { // foreign key
                $this->findForeignKey($table, $row['idxname']);
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
            $name = $row['colname'];
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
            $name = $row['columnbase'];
            if (isset($table->columns[$name])) {
                $table->columns[$name]->isForeignKey = true;
            }
            $table->foreignKeys[$name] = array($row['tablereference'], $row['columnreference']);
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
                $names[] = $row['tabname'];
            else
                $names[] = $row['owner'] . '.' . $row['tabname'];
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

    /**
     * Builds a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $name the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn($table, $name, $newName) {
        return "RENAME COLUMN " . $this->quoteTableName($table) . "." . $this->quoteColumnName($name)
                . " TO " . $this->quoteColumnName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The {@link getColumnType} method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn($table, $column, $type) {
        return 'ALTER TABLE ' . $this->quoteTableName($table)
                . ' MODIFY (' . $this->quoteColumnName($column) . ' ' . $this->getColumnType($type) . ')';
    }

    /**
     * Resets the sequence value of a table's primary key.
     * The sequence will be reset such that the primary key of the next new row inserted
     * will have the specified value or 1.
     * @param CDbTableSchema $table the table schema whose primary key sequence will be reset
     * @param mixed $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     */
    public function resetSequence($table, $value = null) {
        if ($table->sequenceName !== null && is_string($table->primaryKey)) {
            if ($value === null)
                $value = $this->getDbConnection()->createCommand("SELECT MAX({$table->primaryKey}) FROM {$table->rawName}")->queryScalar() + 1;
            else
                $value = (int) $value;

            $serialType = $table->getColumn($table->primaryKey)->dbType;

            $this->getDbConnection()->createCommand("ALTER TABLE {$table->rawName} MODIFY ({$table->primaryKey} $serialType ($value)")->execute();
        }
    }

}
