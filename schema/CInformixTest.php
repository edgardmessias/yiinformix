<?php

Yii::import('ext.yiinformix.CInformixConnection');

class CInformixTest extends CTestCase
{
	private $db;

	public function setUp()
	{
		if(!extension_loaded('pdo') || !extension_loaded('pdo_informix'))
			$this->markTestSkipped('PDO and Informix extensions are required.');

		$this->db=new CInformixConnection('informix:host=127.0.0.1;service=9088;database=yii;server=ol_informix;protocol=onsoctcp;CLIENT_LOCALE=en_US.utf8;DB_LOCALE=en_US.8859-1;EnableScrollableCursors=1;FET_BUF_SIZE=32767','test','test');
		try
		{
			$this->db->active=true;
		}
		catch(Exception $e)
		{
			$schemaFile=realpath(dirname(__FILE__).'/../data/informix.sql');
			$this->markTestSkipped("Please read $schemaFile for details on setting up the test environment for Informix test case.");
		}

		$tables=array('comments','post_category','posts','categories','profiles','user_friends', 'users','items','orders','types');
		foreach($tables as $table){
			$this->db->createCommand("DROP TABLE IF EXISTS $table CASCADE")->execute();
                }

		$sqls=file_get_contents(dirname(__FILE__).'/../data/informix.sql');
		foreach(explode(';',$sqls) as $sql)
		{
			if(trim($sql)!=='')
				$this->db->createCommand($sql)->execute();
		}
	}

	public function tearDown()
	{
		$this->db->active=false;
	}

	public function testSchema()
	{
		$schema=$this->db->schema;
		$this->assertTrue($schema instanceof CDbSchema);
		$this->assertEquals($schema->dbConnection,$this->db);
		$this->assertTrue($schema->commandBuilder instanceof CDbCommandBuilder);
		$this->assertEquals('posts',$schema->quoteTableName('posts'));
		$this->assertEquals('id',$schema->quoteColumnName('id'));
		$this->assertTrue($schema->getTable('posts') instanceof CDbTableSchema);
		$this->assertTrue($schema->getTable('foo')===null);
	}

	public function testTable()
	{
		$table=$this->db->schema->getTable('posts');
		$this->assertTrue($table instanceof CDbTableSchema);
		$this->assertEquals('posts',$table->name);
		$this->assertEquals('posts',$table->rawName);
		$this->assertEquals('id',$table->primaryKey);
		$this->assertEquals(array('author_id'=>array('users','id')),$table->foreignKeys);
		$this->assertEquals('id',$table->sequenceName);
		$this->assertEquals(5,count($table->columns));

		$this->assertTrue($table->getColumn('id') instanceof CDbColumnSchema);
		$this->assertTrue($table->getColumn('foo')===null);
		$this->assertEquals(array('id','title','create_time','author_id','content'),$table->columnNames);

		$table=$this->db->schema->getTable('orders');
		$this->assertEquals(array('key1','key2'),$table->primaryKey);

		$table=$this->db->schema->getTable('items');
		$this->assertEquals('id',$table->primaryKey);
		$this->assertEquals(array('col1'=>array('orders','key1'),'col2'=>array('orders','key2')),$table->foreignKeys);

		$table=$this->db->schema->getTable('types');
		$this->assertTrue($table instanceof CDbTableSchema);
		$this->assertEquals('types',$table->name);
		$this->assertEquals('types',$table->rawName);
		$this->assertTrue($table->primaryKey===null);
		$this->assertTrue($table->foreignKeys===array());
		$this->assertTrue($table->sequenceName===null);

		$table=$this->db->schema->getTable('invalid');
		$this->assertNull($table);
	}

	public function testColumn()
	{
		$values=array
		(
			'name'=>array('id', 'title', 'create_time', 'author_id', 'content'),
			'rawName'=>array('id', 'title', 'create_time', 'author_id', 'content'),
			'defaultValue'=>array(null, null, null, null, null),
			'size'=>array(null, 128, null, null, null),
			'precision'=>array(null, 128, null, null, null),
			'scale'=>array(null, null, null, null, null),
			'type'=>array('integer','string','string','integer','string'),
			'isPrimaryKey'=>array(true,false,false,false,false),
			'isForeignKey'=>array(false,false,false,true,false),
		);
		$this->checkColumns('posts',$values);
		$values=array
		(
			'name'=>array('int_col', 'int_col2', 'char_col', 'char_col2', 'char_col3', 'numeric_col', 'real_col', 'blob_col', 'time', 'bool_col', 'bool_col2'),
			'rawName'=>array('int_col', 'int_col2', 'char_col', 'char_col2', 'char_col3', 'numeric_col', 'real_col', 'blob_col', 'time', 'bool_col', 'bool_col2'),
			'defaultValue'=>array(null, 1, null, 'something', null, null, '1.23', null, null, null, true),
			'size'=>array(null, null, 100, 100, null, 4, null, null, null, null, null),
			'precision'=>array(null, null, 100, 100, null, 4, null, null, null, null, null),
			'scale'=>array(null, null, null, null, null, 3, null, null, null, null, null),
			'type'=>array('integer','integer','string','string','string','double','double','string','string','boolean','boolean'),
			'isPrimaryKey'=>array(false,false,false,false,false,false,false,false,false,false,false),
			'isForeignKey'=>array(false,false,false,false,false,false,false,false,false,false,false),
		);
		$this->checkColumns('types',$values);
	}

	protected function checkColumns($tableName,$values)
	{
		$table=$this->db->schema->getTable($tableName);
		foreach($values as $name=>$value)
		{
			foreach(array_values($table->columns) as $i=>$column)
			{
				$type1=gettype($column->$name);
				$type2=gettype($value[$i]);
				$this->assertTrue($column->$name===$value[$i], "$tableName.{$column->name}.$name is {$column->$name} ($type1), different from the expected {$value[$i]} ($type2).");
			}
		}
	}

	public function testCommandBuilder()
	{
		$schema=$this->db->schema;
		$builder=$schema->commandBuilder;
		$this->assertTrue($builder instanceof CDbCommandBuilder);
		$table=$schema->getTable('posts');

		$c=$builder->createInsertCommand($table,array('title'=>'test post','create_time'=>'2004-10-19 10:23:54','author_id'=>1,'content'=>'test content'));
		$this->assertEquals('INSERT INTO posts (title, create_time, author_id, content) VALUES (:yp0, :yp1, :yp2, :yp3)',$c->text);
		$c->execute();
		$this->assertEquals(6,$builder->getLastInsertId($table));

		$c=$builder->createCountCommand($table,new CDbCriteria);
		$this->assertEquals('SELECT COUNT(*) FROM posts t',$c->text);
		$this->assertEquals(6,$c->queryScalar());

		$c=$builder->createDeleteCommand($table,new CDbCriteria(array(
			'condition'=>'id=:id',
			'params'=>array('id'=>6))));
		$this->assertEquals('DELETE FROM posts WHERE id=:id',$c->text);
		$c->execute();
		$c=$builder->createCountCommand($table,new CDbCriteria);
		$this->assertEquals(5,$c->queryScalar());

		$c=$builder->createFindCommand($table,new CDbCriteria(array(
			'select'=>'id, title',
			'condition'=>'id=:id',
			'params'=>array('id'=>5),
			'order'=>'title',
			'limit'=>2,
			'offset'=>0)));
		$this->assertEquals('SELECT LIMIT 2 id, title FROM posts t WHERE id=:id ORDER BY title',$c->text);
		$rows=$c->query()->readAll();
		$this->assertEquals(1,count($rows));
		$this->assertEquals('post 5',$rows[0]['title']);

		$c=$builder->createUpdateCommand($table,array('title'=>'new post 5'),new CDbCriteria(array(
			'condition'=>'id=:id',
			'params'=>array('id'=>5))));
		$c->execute();
		$c=$builder->createFindCommand($table,new CDbCriteria(array(
			'select'=>'title',
			'condition'=>'id=:id',
			'params'=>array('id'=>5))));
		$this->assertEquals('new post 5',$c->queryScalar());

		$c=$builder->createSqlCommand('SELECT title FROM posts WHERE id=:id',array(':id'=>3));
		$this->assertEquals('post 3',$c->queryScalar());

		$c=$builder->createUpdateCounterCommand($table,array('author_id'=>-2),new CDbCriteria(array('condition'=>'id=5')));
		$this->assertEquals('UPDATE posts SET author_id=author_id-2 WHERE id=5',$c->text);
		$c->execute();
		$c=$builder->createSqlCommand('SELECT author_id FROM posts WHERE id=5');
		$this->assertEquals(1,$c->queryScalar());

		// test bind by position
		$c=$builder->createFindCommand($table,new CDbCriteria(array(
			'select'=>'title',
			'condition'=>'id=?',
			'params'=>array(4))));
		$this->assertEquals('SELECT title FROM posts t WHERE id=?',$c->text);
		$this->assertEquals('post 4',$c->queryScalar());

		// another bind by position
		$c=$builder->createUpdateCommand($table,array('title'=>'new post 4'),new CDbCriteria(array(
			'condition'=>'id=?',
			'params'=>array(4))));
		$c->execute();
		$c=$builder->createSqlCommand('SELECT title FROM posts WHERE id=4');
		$this->assertEquals('new post 4',$c->queryScalar());

		// testCreateCriteria
		$c=$builder->createCriteria('column=:value',array(':value'=>'value'));
		$this->assertEquals('column=:value',$c->condition);
		$this->assertEquals(array(':value'=>'value'),$c->params);

		$c=$builder->createCriteria(array('condition'=>'column=:value','params'=>array(':value'=>'value')));
		$this->assertEquals('column=:value',$c->condition);
		$this->assertEquals(array(':value'=>'value'),$c->params);

		$c2=$builder->createCriteria($c);
		$this->assertTrue($c2!==$c);
		$this->assertEquals('column=:value',$c2->condition);
		$this->assertEquals(array(':value'=>'value'),$c2->params);

		// testCreatePkCriteria
		$c=$builder->createPkCriteria($table,1,'author_id>1');
		$this->assertEquals('posts.id=1 AND (author_id>1)',$c->condition);

		$c=$builder->createPkCriteria($table,array(1,2));
		$this->assertEquals('posts.id IN (1, 2)',$c->condition);

		$table2=$schema->getTable('orders');
		$c=$builder->createPkCriteria($table2,array('key1'=>1,'key2'=>2),'name=\'\'');
		$this->assertEquals('orders.key1=1 AND orders.key2=2 AND (name=\'\')',$c->condition);

		$c=$builder->createPkCriteria($table2,array(array('key1'=>1,'key2'=>2),array('key1'=>3,'key2'=>4)));
		$this->assertEquals('(orders.key1, orders.key2) IN ((1, 2), (3, 4))',$c->condition);

		// createColumnCriteria
		$c=$builder->createColumnCriteria($table,array('id'=>1,'author_id'=>2),'title=\'\'');
		$this->assertEquals('posts.id=:yp0 AND posts.author_id=:yp1 AND (title=\'\')',$c->condition);
	}

	public function testResetSequence()
	{
		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->db->createCommand("DELETE FROM users")->execute();
		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
		$max2=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
		$this->assertEquals($max+1,$max2);

		$userTable=$this->db->schema->getTable('users');

                /**
                 * @todo resetSequence deleting relationships
                 */
//		$this->db->createCommand("DELETE FROM users")->execute();
//		$this->db->schema->resetSequence($userTable);
//		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
//		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
//		$this->assertEquals(1,$max);
//		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
//		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
//		$this->assertEquals(2,$max);

//		$this->db->createCommand("DELETE FROM users")->execute();
//		$this->db->schema->resetSequence($userTable,10);
//		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
//		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
//		$this->assertEquals(10,$max);
//		$this->db->createCommand("INSERT INTO users (username, password, email) VALUES ('user4','pass4','email4')")->execute();
//		$max=$this->db->createCommand("SELECT MAX(id) FROM users")->queryScalar();
//		$this->assertEquals(11,$max);
	}
}