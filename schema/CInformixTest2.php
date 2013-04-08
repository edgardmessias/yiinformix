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

	public function testCreateTable()
	{
		$sql=$this->db->schema->createTable('test',array(
			'id'=>'pk',
			'name'=>'string not null',
			'desc'=>'text',
			'primary key (id, name)',
		));
		$expect="CREATE TABLE test (\n"
			. "\tid serial NOT NULL PRIMARY KEY,\n"
			. "\tname varchar(255) not null,\n"
			. "\tdesc text,\n"
			. "\tprimary key (id, name)\n"
			. ")";
		$this->assertEquals($expect, $sql);
	}

	public function testRenameTable()
	{
		$sql=$this->db->schema->renameTable('test', 'test2');
		$expect='RENAME TABLE test TO test2';
		$this->assertEquals($expect, $sql);
	}

	public function testDropTable()
	{
		$sql=$this->db->schema->dropTable('test');
		$expect='DROP TABLE test';
		$this->assertEquals($expect, $sql);
	}

	public function testAddColumn()
	{
		$sql=$this->db->schema->addColumn('test', 'id', 'integer');
		$expect='ALTER TABLE test ADD id integer';
		$this->assertEquals($expect, $sql);
	}

	public function testAlterColumn()
	{
		$sql=$this->db->schema->alterColumn('test', 'id', 'boolean');
		$expect='ALTER TABLE test MODIFY (id boolean)';
		$this->assertEquals($expect, $sql);
	}

	public function testRenameColumn()
	{
		$sql=$this->db->schema->renameColumn('users', 'username', 'name');
		$expect='RENAME COLUMN users.username TO name';
		$this->assertEquals($expect, $sql);
	}

	public function testDropColumn()
	{
		$sql=$this->db->schema->dropColumn('test', 'id');
		$expect='ALTER TABLE test DROP COLUMN id';
		$this->assertEquals($expect, $sql);
	}

	public function testAddForeignKey()
	{
		$sql=$this->db->schema->addForeignKey('fk_test', 'profile', 'user_id', 'users', 'id');
		$expect='ALTER TABLE profile ADD CONSTRAINT fk_test FOREIGN KEY (user_id) REFERENCES users (id)';
		$this->assertEquals($expect, $sql);

		$sql=$this->db->schema->addForeignKey('fk_test', 'profile', 'user_id', 'users', 'id','CASCADE','RESTRICTED');
		$expect='ALTER TABLE profile ADD CONSTRAINT fk_test FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE RESTRICTED';
		$this->assertEquals($expect, $sql);
	}

	public function testDropForeignKey()
	{
		$sql=$this->db->schema->dropForeignKey('fk_test', 'profile');
		$expect='ALTER TABLE profile DROP CONSTRAINT fk_test';
		$this->assertEquals($expect, $sql);
	}

	public function testCreateIndex()
	{
		$sql=$this->db->schema->createIndex('id_pk','test','id');
		$expect='CREATE INDEX id_pk ON test (id)';
		$this->assertEquals($expect, $sql);

		$sql=$this->db->schema->createIndex('id_pk','test','id1,id2',true);
		$expect='CREATE UNIQUE INDEX id_pk ON test (id1, id2)';
		$this->assertEquals($expect, $sql);
	}

	public function testDropIndex()
	{
		$sql=$this->db->schema->dropIndex('id_pk','test');
		$expect='DROP INDEX id_pk';
		$this->assertEquals($expect, $sql);
	}
}