<?php

require_once __DIR__."/lib/SQLQuery.class.php";

class UserAdminArea extends StudIPPlugin implements SystemPlugin
{
    public function __construct()
    {
        parent::__construct();
        $nav = new Navigation(_("Mehrere Nutzer"), PluginEngine::getURL($this, array(), "user/overview"));
        Navigation::insertItem("/admin/user/admin", $nav, "user_domains");
    }
}