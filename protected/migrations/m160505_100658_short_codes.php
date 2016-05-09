<?php

class m160505_100658_short_codes extends OEMigration
{
	public function up()
	{
            $this->createOETable('short_codes', array(
                'id' => 'pk',
                'code' => 'varchar(6) NOT NULL',
                'active' => 'int(1) unsigned NOT NULL default 1',
            ),true);
	}

	public function down()
	{
            $this->dropTable('short_codes');
	}
}