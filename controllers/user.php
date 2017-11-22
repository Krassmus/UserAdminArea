<?php

class UserController extends PluginController
{
    public function overview_action()
    {
        Navigation::activateItem("/admin/user/admin");
        $query = \UserAdmin\SQLQuery::table("auth_user_md5")->groupBy("auth_user_md5.user_id");
        if ($GLOBALS['user']->cfg->ADMIN_USER_SEARCHTEXT) {
            $query->where("searchtext","CONCAT_WS(auth_user_md5.Vorname, auth_user_md5.Nachname, auth_user_md5.Email) LIKE :searchtext", array(
                'searchtext' => "%".$GLOBALS['user']->cfg->ADMIN_USER_SEARCHTEXT."%"
            ));
        }
        if ($GLOBALS['user']->cfg->ADMIN_USER_LOCKED) {
            $query->where("locked","auth_user_md5.locked = :locked", array(
                'locked' => $GLOBALS['user']->cfg->ADMIN_USER_LOCKED === "locked" ? 1 : 0
            ));
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
        if ($query->count() <= 500) {
            $this->users = $query->fetchAll("User");
        } else {
            PageLayout::postInfo(_("Geben Sie mehr Filter ein."));
        }
    }

    public function edit_action()
    {
        if (Request::get("all")) {
            $query = \UserAdmin\SQLQuery::table("auth_user_md5");
            $count = $query->count();
            $this->users = $query->fetchAll("User");
        } else {
            $count = count(Request::getArray("u"));
            $this->users = User::findMany(Request::getArray("u"));
        }
        $this->datafields = DataField::findBySQL("object_type = 'user' ORDER BY priority");

        if (Request::isPost() && Request::submitted("save")) {
            $changes = Request::getArray("change");
            foreach ($this->users as $user) {
                foreach ($changes as $change) {
                    if ($change === "locked") {
                        $user['locked'] = Request::int("locked", 0);
                        $user['locked_by'] = $GLOBALS['user']->id;
                    }
                    if ($change === "lock_comment") {
                        $user['lock_comment'] = Request::get("lock_comment");
                    }
                    if ($change === "lock_rule" && Request::option("lock_rule") && $GLOBALS['perm']->have_perm("admin")) {
                        if (Request::option("lock_rule") === "none") {
                            $user['lock_rule'] = null;
                        } else {
                            $user['lock_rule'] = Request::get("lock_rule");
                        }
                    }
                    if (strpos($change, "datafield_") === 0) {
                        $datafield_id = substr($change, strlen("datafield_"));
                        $course_value = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ?", array($datafield_id, $user->getId()));
                        if (!$course_value) {
                            $course_value = new DatafieldEntryModel();
                            $course_value['range_id'] = $user->getId();
                            $course_value['datafield_id'] = $datafield_id;
                            $course_value['sec_range_id'] = '';
                        }
                        $course_value->content = Request::get("datafield_" . $datafield_id, '');
                        $course_value->store();
                    }
                }
                $user->store();
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%s Personen erfolgreich gespeichert"), count($this->users))));
        }

        PageLayout::setTitle(sprintf(_("%s Nutzer bearbeiten"), $count));
    }

    public function delete_all_action() {
        if (Request::get("all")) {
            $query = \UserAdmin\SQLQuery::table("auth_user_md5");
            $this->users = $query->fetchAll("User");
        } else {
            $this->users = User::findMany(Request::getArray("u"));
        }
        if (Request::isPost()) {
            $success = 0;
            foreach ($this->users as $user) {
                $umanager = new UserManagement($user->getId());
                $success += $umanager->deleteUser() ? 1 : 0;
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%s Nutzer erfolgreich gelöscht."), $success)));
            if ($success < count($this->users)) {
                PageLayout::postMessage(MessageBox::success(sprintf(_("Konnte %s Nutzer nicht löschen."), count($this->users) - $success)));
            }
        }
        $this->redirect("user/overview");
    }

    public function search_text_action()
    {
        if (Request::submitted("search") || Request::get("reset-search")) {
            $GLOBALS['user']->cfg->store('ADMIN_USER_SEARCHTEXT', Request::get("search"));
        }
        $this->redirect("user/overview");
    }

    public function search_locked_action()
    {
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('ADMIN_USER_LOCKED', Request::get("locked"));
        }
        $this->redirect("user/overview");
    }

    public function search_status_action()
    {
        if (Request::get("remove") === "all") {
            $GLOBALS['user']->cfg->store('ADMIN_USER_STATUS', "");
        } else {
            $config = $GLOBALS['user']->cfg->ADMIN_USER_STATUS ? unserialize($GLOBALS['user']->cfg->ADMIN_USER_STATUS) : array();
            if (in_array(Request::get("toggle"), $config)) {
                unset($config[array_search(Request::get("toggle"), $config)]);
                $config = array_values($config);
            } else {
                $config[] = Request::get("toggle");
            }
            $GLOBALS['user']->cfg->store('ADMIN_USER_STATUS', serialize($config));
        }
        $this->redirect("user/overview");
    }

    public function search_inactivity_action()
    {
        if (Request::submitted("inactivity") || Request::get("reset-search")) {
            $GLOBALS['user']->cfg->store('ADMIN_USER_INACTIVITY', Request::get("inactivity"));
        }
        $this->redirect("user/overview");
    }

    public function getAverageValue($users, $attribute)
    {
        $value = null;
        if (strpos($attribute, "datafield_") === 0) {
            $datafield_id = substr($attribute, strlen("datafield_"));
            foreach ($users as $user) {
                $course_value = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ?", array($datafield_id, $user->getId()));
                if ($value === null && $course_value->content !== '') {
                    $value = $course_value->content;
                } elseif ($value != $course_value->content) {
                    $value = false;
                }
            }
        } else {
            foreach ($users as $user) {
                if ($value === null && $user[$attribute] !== '') {
                    $value = $user[$attribute];
                } elseif ($value != $user[$attribute]) {
                    $value = false;
                }
            }
        }
        return $value;
    }
}