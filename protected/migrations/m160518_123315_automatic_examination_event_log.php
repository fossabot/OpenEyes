<?php

class m160518_123315_automatic_examination_event_log extends OEMigration
{
	public function up()
	{
            $this->createOETable('automatic_examination_event_log', array(
                'id' => 'pk',
                'unique_code' => 'varchar(6) NOT NULL',
                'examination_date' => "datetime DEFAULT '1900-01-01 00:00:00'",
            ),true);
	}

	public function down()
	{
		$this->dropTable('automatic_examination_event_log');
	}
}