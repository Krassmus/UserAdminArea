<?php

class SuperDatafieldAccess extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'useradminarea_superdatafieldaccess';
        parent::configure($config);
    }
}
