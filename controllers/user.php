<?php

class UserController extends PluginController
{
    function before_filter(&$action, &$args)
    {
        $this->utf8decode_xhr = true;
        parent::before_filter($action, $args);
    }

    public function overview_action()
    {
        if ($GLOBALS['perm']->have_perm('root')) {
            Navigation::activateItem("/admin/user/admin");
        } else {
            Navigation::activateItem("/contents/useradminarea");
        }
        PageLayout::setTitle(_("Mehrere Nutzer bearbeiten"));
        $query = UserAdminArea::getUsersQuery();
        $countUsers = $query->count();

        if ($countUsers <= 500 || Request::get("show_all")) {
            $this->users = $query->fetchAll("User");
            if (count($this->users) === 0) {
                PageLayout::postMessage(MessageBox::info(_("Keine Personen zu den angegebenen Filterkriterien gefunden.")));
            }
        } else {
            PageLayout::postInfo(sprintf(_("%s Nutzer gefunden. Geben Sie mehr Filter ein, oder %salle anzeigen%s."), $countUsers, '<a href="'.PluginEngine::getLink($this->plugin, array('show_all' => 1), "user/overview").'">', '</a>'));
        }


        $statement = DBManager::get()->prepare("
            SELECT user_id, last_lifesign
            FROM user_online
            WHERE user_id IN (:user_ids)
        ");
        $statement->execute(array(
            'user_ids' => array_map(function ($u) { return $u->getId(); }, (array) $this->users)
        ));
        $this->user_lastlifesign = $statement->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP);
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
            $edited = 0;
            $changes = Request::getArray("change");
            foreach ($this->users as $user) {
                if (!UserAdminArea::userMayEditUser($user->getId())) {
                    continue;
                }
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
                    if ($change === "add_institut_id" && Request::option("add_institut_id") && $GLOBALS['perm']->have_perm("admin")) {
                        $member = InstituteMember::findOneBySQL("user_id = ? AND institut_id = ?", array($user->getId(), Request::option("add_institut_id")));
                        if (!$member) {
                            $member = new InstituteMember();
                            $member['user_id'] = $user->getId();
                            $member['institut_id'] = Request::option("add_institut_id");
                        }
                        switch ($user['perms']) {
                            case "autor":
                                $member['inst_perms'] = "user";
                                break;
                            case "tutor":
                                $member['inst_perms'] = "autor";
                                break;
                            case "dozent":
                                $member['inst_perms'] = "dozent";
                                break;
                            case "admin":
                                $member['inst_perms'] = "admin";
                                break;
                            default:
                                $member['inst_perms'] = null;
                        }
                        if ($member['inst_perms']) {
                            $member->store();
                        }
                    }
                    if ($change === "remove_institut_id" && Request::option("remove_institut_id") && $GLOBALS['perm']->have_perm("admin")) {
                        $member = InstituteMember::findOneBySQL("user_id = ? AND institut_id = ?", array($user->getId(), Request::option("remove_institut_id")));
                        if ($member) {
                            $member->delete();
                        }
                    }
                    if ($change === "add_seminar_id" && Request::option("add_seminar_id") && $GLOBALS['perm']->have_perm("admin")) {
                        $member = CourseMember::findOneBySQL("user_id = ? AND Seminar_id = ?", array($user->getId(), Request::option("add_seminar_id")));
                        if (!$member) {
                            $member = new CourseMember();
                            $member['user_id'] = $user->getId();
                            $member['seminar_id'] = Request::option("add_seminar_id");
                            $member['status'] = "autor";
                            $member->store();
                        }
                    }
                    if ($change === "remove_seminar_id" && Request::option("remove_seminar_id") && $GLOBALS['perm']->have_perm("admin")) {
                        $member = CourseMember::findOneBySQL("user_id = ? AND Seminar_id = ?", array($user->getId(), Request::option("remove_seminar_id")));
                        if ($member) {
                            $member->delete();
                        }
                    }
                    if ($change === "visible" && Request::get("visible")) {
                        $user['visible'] = Request::get("visible");
                    }
                    if ($change === "password" && Request::get("password")) {
                        if (Request::get("password") === "generate") {
                            $manager = new UserManagement($user->getId());
                            $manager->setPassword();
                            $password = false;
                        } else {
                            $password = Request::get("new_password");
                        }
                        if ($password) {
                            $hash = UserManagement::getPwdHasher()->HashPassword($password);
                            $user['password'] = $hash;
                            if (!Request::get("changed_password_mail")) {
                                $user_language = getUserLanguagePath($user->getId());
                                $Zeit = date("H:i:s, d.m.Y", time());
                                $this->user_data = new UserDataAdapter($user);
                                include("locale/$user_language/LC_MAILS/password_mail.inc.php");
                            } else {
                                $subject = Request::get("changed_password_subject");
                                $mailbody = Request::get("changed_password_mailbody");
                                $parameters = $user->toArray();
                                $parameters['password'] = $password;
                                $parameters['link'] = $GLOBALS['ABSOLUTE_URI_STUDIP'];
                                foreach ($parameters as $parameter => $value) {
                                    $subject = str_replace("{{".$parameter."}}", $value, $subject);
                                    $mailbody = str_replace("{{".$parameter."}}", $value, $mailbody);
                                }
                            }
                            if ($subject && $mailbody) {
                                StudipMail::sendMessage($user['email'], $subject, $mailbody);
                                StudipLog::log("USER_NEWPWD", $user->getId());
                            }
                        }
                    }
                    if ($change === "add_userdomain" && Request::option("add_userdomain") && $GLOBALS['perm']->have_perm("admin")) {
                        $domain = new UserDomain(Request::option("add_userdomain"));
                        $domain->addUser($user->getId());
                    }
                    if ($change === "remove_userdomain" && Request::option("remove_userdomain") && $GLOBALS['perm']->have_perm("admin")) {
                        $domain = new UserDomain(Request::option("remove_userdomain"));
                        $domain->removeUser($user->getId());
                    }
                    if ($change === "studiengang" && Request::option("studiengang_studiengang_id") && Request::option("studiengang_abschluss_id") && $GLOBALS['perm']->have_perm("admin")) {
                        $eintrag = UserStudyCourse::findOneBySQL("user_id = :user_id AND studiengang_id = :studiengang_id AND abschluss_id = :abschluss_id", array(
                            'user_id' => $user->getId(),
                            'studiengang_id' => Request::option("studiengang_studiengang_id"),
                            'abschluss_id' => Request::option("studiengang_abschluss_id")
                        ));
                        if (!$eintrag) {
                            $eintrag = new UserStudyCourse();
                            $eintrag['user_id'] = $user->getId();
                            $eintrag['studiengang_id'] = Request::option("studiengang_studiengang_id");
                            $eintrag['abschluss_id'] = Request::option("studiengang_abschluss_id");
                        }
                        if (Request::int("studiengang_semester") > 0) {
                            $eintrag['semester'] = Request::int("studiengang_semester");
                            $eintrag->store();
                        } else {
                            $eintrag->delete();
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
                            if (StudipVersion::newerThan("4.1")) {
                                $course_value['lang'] = '';
                            }
                        }
                        $course_value->content = Request::get("datafield_" . $datafield_id, '');
                        $course_value->store();
                    }
                }
                $edited++;
                $user->store();
            }
            PageLayout::postMessage(MessageBox::success(sprintf(_("%s Personen erfolgreich gespeichert"), $edited)));
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
                if (!UserAdminArea::userMayEditUser($user->getId())) {
                    continue;
                }
                $umanager = new UserManagement($user->getId());
                $success += $umanager->deleteUser() ? 1 : 0;
                $user->delete();
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

    public function search_institute_action()
    {
        if (Request::get("institut_id") && Request::get("institut_id") !== 'all') {
            $GLOBALS['user']->cfg->store('ADMIN_USER_INSTITUTE', Request::get("institut_id"));
        } else {
            $GLOBALS['user']->cfg->delete('ADMIN_USER_INSTITUTE');
        }
        $this->redirect("user/overview");
    }

    public function search_fach_action()
    {
        $fach_id = Request::get("fach_id") !== 'all' ? Request::get("fach_id") : null;
        $GLOBALS['user']->cfg->store('ADMIN_USER_FACH', $fach_id);
        $this->redirect("user/overview");
    }

    public function search_abschluss_action()
    {
        $abschluss_id = Request::get("abschluss_id") !== 'all' ? Request::get("abschluss_id") : null;
        $GLOBALS['user']->cfg->store('ADMIN_USER_ABSCHLUSS', $abschluss_id);
        $this->redirect("user/overview");
    }

    public function search_fachsemester_action()
    {
        $fachsemester = Request::get("fachsemester") !== 'all' ? Request::get("fachsemester") : null;
        $GLOBALS['user']->cfg->store('ADMIN_USER_FACHSEMESTER', Request::get("fachsemester"));
        $this->redirect("user/overview");
    }

    public function search_locked_action()
    {
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('ADMIN_USER_LOCKED', Request::get("locked"));
        }
        $this->redirect("user/overview");
    }

    public function search_auth_action()
    {
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('ADMIN_USER_AUTH_PLUGIN', Request::get("auth_plugin"));
        }
        $this->redirect("user/overview");
    }

    public function search_userdomain_action()
    {
        if (Request::isPost()) {
            $GLOBALS['user']->cfg->store('ADMIN_USER_DOMAIN', Request::get("domain_id"));
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

    public function search_datafield_action()
    {
        foreach (DataField::getDataFields("user") as $datafield) {
            if (in_array("datafield_".$datafield->getId(), array_keys($_REQUEST))) {
                if (Request::get("datafield_".$datafield->getId()) === "") {
                    $GLOBALS['user']->cfg->delete('ADMIN_USER_DATAFIELD_'.$datafield->getId());
                } else {
                    $value = $datafield['type'] === "bool"
                        ? (Request::get("datafield_" . $datafield->getId()) === "yes" ? '1' : '0')
                        : Request::get("datafield_" . $datafield->getId());
                    $GLOBALS['user']->cfg->store('ADMIN_USER_DATAFIELD_'.$datafield->getId(), $value);
                }
            }
        }
        if (Request::submitted("reset-search")) {
            $GLOBALS['user']->cfg->delete('ADMIN_USER_DATAFIELD_'.Request::get("df"));
        }
        $this->redirect("user/overview");
    }

    public function toggle_never_online_action()
    {
        $GLOBALS['user']->cfg->store('ADMIN_USER_NEVER_ONLINE', !$GLOBALS['user']->cfg->ADMIN_USER_NEVER_ONLINE);
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
