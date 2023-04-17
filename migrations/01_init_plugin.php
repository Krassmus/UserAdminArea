<?php

class InitPlugin extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `useradminarea_superdatafieldaccess` (
                `id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `user_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL,
                `datafield_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL,
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_id_2` (`user_id`,`datafield_id`),
                KEY `user_id` (`user_id`),
                KEY `datafield_id` (`datafield_id`)
            ) ENGINE=InnoDB
        ");
    }

    public function down()
    {
        DBManager::get()->exec("
            DROP TABLE IF EXISTS `useradminarea_superdatafieldaccess`
        ");
    }
}
