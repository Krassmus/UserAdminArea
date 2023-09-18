<?php

require_once __DIR__."/lib/SQLQuery.class.php";
require_once __DIR__."/lib/UserExportData.php";
require_once __DIR__.'/lib/SuperDatafieldAccess.php';

class UserAdminArea extends StudIPPlugin implements SystemPlugin
{
    public function __construct()
    {
        parent::__construct();
        if ($GLOBALS['perm']->have_perm("root")) {
            $nav = new Navigation(_("Mehrere Nutzer"), PluginEngine::getURL($this, array(), "user/overview"));
            Navigation::insertItem("/admin/user/admin", $nav, "user_domains");
        } elseif($GLOBALS['perm']->have_perm("admin") || RolePersistence::isAssignedRole(User::findCurrent()->id, 'UserAdminArea_Readonly')) {
            $nav = new Navigation(_("Nutzerverwaltung"), PluginEngine::getURL($this, array(), "user/overview"));
            $nav->setImage(Icon::create('person', Icon::ROLE_CLICKABLE));
            $nav->setDescription(_('Bearbeiten und Export von Nutzern'));
            Navigation::addItem("/contents/useradminarea", $nav);
        }
    }

    static public function getUsersQuery()
    {
        $query = \UserAdmin\SQLQuery::table("auth_user_md5")
            ->join('user_info', "user_info.user_id = auth_user_md5.user_id", "LEFT JOIN")
            ->select([
                'title_front' => 'user_info.title_front',
                'title_rear' => 'user_info.title_rear',
                'preferred_language' => 'user_info.preferred_language',
                'geschlecht' => 'user_info.geschlecht'
            ])
            ->groupBy("auth_user_md5.user_id")
            ->orderBy('auth_user_md5.Nachname , auth_user_md5.Vorname ASC');
        if ($GLOBALS['user']->cfg->ADMIN_USER_SEARCHTEXT) {
            $query->where("searchtext","CONCAT_WS(auth_user_md5.Vorname, auth_user_md5.Nachname, auth_user_md5.Email) LIKE :searchtext", array(
                'searchtext' => "%".$GLOBALS['user']->cfg->ADMIN_USER_SEARCHTEXT."%"
            ));
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_INSTITUTE) {
            $query->join("user_inst", "user_inst.user_id = auth_user_md5.user_id", "INNER JOIN");
            $query->where("institute_id","user_inst.Institut_id = :institut_id", array(
                'institut_id' => $GLOBALS['user']->cfg->ADMIN_USER_INSTITUTE
            ));
        }
        if($GLOBALS['user']->cfg->ADMIN_USER_FACH) {
            $query->join("user_studiengang", "user_studiengang.user_id = auth_user_md5.user_id", "INNER JOIN");
            $query->where("fach_id","user_studiengang.fach_id = :fach_id", array(
                'fach_id' => $GLOBALS['user']->cfg->ADMIN_USER_FACH
            ));
        }
        if($GLOBALS['user']->cfg->ADMIN_USER_ABSCHLUSS) {
            $query->join("user_studiengang", "user_studiengang.user_id = auth_user_md5.user_id", "INNER JOIN");
            $query->where("abschluss_id","user_studiengang.abschluss_id = :abschluss_id", array(
                'abschluss_id' => $GLOBALS['user']->cfg->ADMIN_USER_ABSCHLUSS
            ));
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_FACHSEMESTER && $GLOBALS['user']->cfg->ADMIN_USER_FACHSEMESTER !== 'all') {
            $query->join("user_studiengang", "user_studiengang.user_id = auth_user_md5.user_id", "INNER JOIN");
            $query->where("fachsemester","user_studiengang.semester = :fachsemester", array(
                'fachsemester' => $GLOBALS['user']->cfg->ADMIN_USER_FACHSEMESTER
            ));
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_LOCKED) {
            if (in_array($GLOBALS['user']->cfg->ADMIN_USER_LOCKED, ['locked', 'unlocked'])) {
                $query->where("locked", "auth_user_md5.locked = :locked", array(
                    'locked' => $GLOBALS['user']->cfg->ADMIN_USER_LOCKED === "locked" ? 1 : 0
                ));
            }
            if (in_array($GLOBALS['user']->cfg->ADMIN_USER_LOCKED, ['expired', 'locked_and_expired'])) {
                $query->join(
                    'expire_date',
                    'config_values', "`expire_date`.`range_id` = `auth_user_md5`.`user_id` AND `expire_date`.`field` = 'EXPIRATION_DATE'",
                    'LEFT JOIN'
                );
                if ($GLOBALS['user']->cfg->ADMIN_USER_LOCKED === 'expired') {
                    $query->where("expired", "`expire_date`.`value` < UNIX_TIMESTAMP()");
                } else {
                    $query->where("expired", "(`expire_date`.`value` < UNIX_TIMESTAMP()) OR (auth_user_md5.locked = :locked)", array(
                        'locked' => 1
                    ));
                }
            }
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_AUTH_PLUGIN) {
            $query->where("auth_plugin", "`auth_user_md5`.`auth_plugin` = :auth_plugin", [
                'auth_plugin' => $GLOBALS['user']->cfg->ADMIN_USER_AUTH_PLUGIN
            ]);
        }
        $status_config = $config = $GLOBALS['user']->cfg->ADMIN_USER_STATUS ? unserialize($GLOBALS['user']->cfg->ADMIN_USER_STATUS) : array();
        if (count($status_config)) {
            $system_status = array("user", "autor", "tutor", "dozent", "admin", "root");
            $plugin_roles = array_diff($status_config, $system_status);
            if (!count($plugin_roles)) {
                $query->where("system_role","auth_user_md5.perms IN (:status)", array(
                    'status' => $status_config
                ));
            } else {
                $query->join("roles_user", "roles_user.userid = auth_user_md5.user_id", "LEFT JOIN");
                $query->where("system_role", "(auth_user_md5.perms IN (:status) OR roles_user.roleid IN (:status))", array(
                    'status' => $status_config
                ));
            }
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_INACTIVITY > 0) {
            $query->join("user_online", "user_online.user_id = auth_user_md5.user_id", "LEFT JOIN");
            $query->where("inactive_days", "user_online.last_lifesign <= UNIX_TIMESTAMP() - :inactive_days * 86400", array(
                'inactive_days' => $GLOBALS['user']->cfg->ADMIN_USER_INACTIVITY
            ));
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_NEVER_ONLINE) {
            $query->where("never_online", "auth_user_md5.user_id NOT IN (SELECT user_id FROM user_online)");
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_DOMAIN) {
            if ($GLOBALS['user']->cfg->ADMIN_USER_DOMAIN === "USER_ADMIN_AREA_NULLDOMAIN") {
                $query->join("user_userdomains", "user_userdomains.user_id = auth_user_md5.user_id", "LEFT JOIN");
                $query->where("user_userdomains", "user_userdomains.userdomain_id IS NULL ");
            } else {
                $query->join("user_userdomains", "user_userdomains.user_id = auth_user_md5.user_id", "INNER JOIN");
                $query->where("user_userdomains", "user_userdomains.userdomain_id = :domain ", array('domain' => $GLOBALS['user']->cfg->ADMIN_USER_DOMAIN));
            }
        }
        foreach (DataField::getDataFields("user") as $datafield) {
            if (($GLOBALS['user']->cfg->getValue("ADMIN_USER_DATAFIELD_".$datafield->getId()) !== null) && ($GLOBALS['user']->cfg->getValue("ADMIN_USER_DATAFIELD_".$datafield->getId()) !== '')) {
                $value = $GLOBALS['user']->cfg->getValue("ADMIN_USER_DATAFIELD_".$datafield->getId());
                $query->join("de", "datafields_entries", "de.range_id = auth_user_md5.user_id", "LEFT JOIN");
                $query->where(
                    "datafields_entries_".$datafield->getId(),
                    "(de.`content` = :datafield_".$datafield->getId()."_content AND de.datafield_id = :datafield_".$datafield->getId()."_id)",
                    array(
                        'datafield_'.$datafield->getId().'_content' => $value,
                        'datafield_'.$datafield->getId().'_id'      => $datafield->getId()
                    )
                );
            }
        }
        return $query;
    }

    static public function userMayEditUser($user_id)
    {
        if ($GLOBALS['perm']->have_perm('root')) {
            return true;
        } elseif ($GLOBALS['perm']->have_perm('admin')) {
            $statement = DBManager::get()->prepare("
                SELECT 1
                FROM user_inst AS a
                    INNER JOIN user_inst AS b ON (a.Institut_id = b.Institut_id AND b.inst_perms = 'admin')
                WHERE b.user_id = :me
                    AND a.user_id = :user_id
            ");
            $statement->execute([
                'me' => User::findCurrent()->id,
                'user_id' => $user_id
            ]);
            return (bool) $statement->fetch();
        }
        return false;
    }
}
