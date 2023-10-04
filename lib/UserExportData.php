<?php

namespace UserAdmin;

class UserExportData
{
    protected $attributes = [];

    public function __construct()
    {
        $this->attributes = [
            'user_id' => _('User-ID'),
            'title_front' => _('Titel'),
            'vorname' => _('Vorname'),
            'nachname' => _('Nachname'),
            'title_rear' => _('Titel nachgestellt'),
            'email' => _('Email'),
            'username' => _('Username'),
            'avatar' => _('Bild'),
            'status' => _('Status'),
            'locked' => _('Gesperrt'),
            'lock_comment' => _('Sperrkommentar'),
            'registriert' => _('Registriert seit'),
            'last_activity' => _('Zuletzt Online'),
            'expires' => _("Ablaufdatum"),
            'language' => _('Sprache'),
            'gender' => _('Geschlecht'),
            'visible' => _('Sichtbarkeit'),
            'userdomains' => _('Nutzerdomänen'),
            'userdomain_ids' => _('Nutzerdomänen-IDs'),
            'roles' => _('Rollen'),
            'role_ids' => _('Rollen-IDs'),
            'studycourses' => _('Studiengänge'),
            'institutes' => _('Einrichtungen')
        ];
        if (!$GLOBALS['perm']->have_perm('root')) {
            unset($this->attributes['locked']);
            unset($this->attributes['lock_comment']);
            unset($this->attributes['last_activity']);
            unset($this->attributes['registriert']);
            unset($this->attributes['expires']);
            unset($this->attributes['userdomains']);
            unset($this->attributes['userdomain_ids']);
            unset($this->attributes['roles']);
            unset($this->attributes['role_ids']);
            unset($this->attributes['studycourses']);
            unset($this->attributes['institutes']);
        }
        $this->datafields = \DataField::getDataFields('user');
        foreach ($this->datafields as $datafield) {
            if ($datafield->accessAllowed() || $this->datafieldAccessAllowed($datafield)) {
                $this->attributes[$datafield['datafield_id']] = (string) $datafield['name'];
            }
        }
        \NotificationCenter::postNotification('UserAdminAreaUserExportDataReturnsAttributes', $this);
    }

    public function addUserAttribute($attribute_index, $attribute_name)
    {
        if (!isset($this->attributes[$attribute_index])) {
            $this->attributes[$attribute_index] = $attribute_name;
        }
        return $this;
    }

    public function removeUserAttribute($attribute_index)
    {
        if (isset($this->attributes[$attribute_index])) {
            unset($this->attributes[$attribute_index]);
        }
        return $this;
    }

    public function getUserAttributes()
    {
        \NotificationCenter::postNotification('UserAdminAreaUserExportDataReturnsAttributes', $this);
        return $this->attributes;
    }

    public function getUserQuery(array $attributes)
    {
        $query = \UserAdminArea::getUsersQuery();
        foreach ($attributes as $attribute) {
            switch ($attribute) {
                case 'vorname':
                    $query->select('`auth_user_md5`.`Vorname` AS `vorname`');
                    break;
                case 'nachname':
                    $query->select('`auth_user_md5`.`Nachname` AS `nachname`');
                    break;
                case 'email':
                    $query->select('`auth_user_md5`.`Email` AS `email`');
                    break;
                case 'status':
                    $query->select('`auth_user_md5`.`perms` AS `status`');
                    break;
                case 'gender':
                    $query->select('`user_info`.`geschlecht` AS `gender`');
                    break;
                case 'registriert':
                    $query->select("FROM_UNIXTIME(`user_info`.`mkdate`, '%Y-%m-%d %H:%i:%s') AS `registriert`");
                    break;
                case 'last_activity':
                    $query->join(
                        'user_online',
                        'user_online',
                        "`user_online`.`user_id` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->select("FROM_UNIXTIME(`user_online`.`last_lifesign`, '%Y-%m-%d %H:%i:%s') AS `last_activity`");
                    break;
                case 'expires':
                    $query->join(
                        'user_config_expires',
                        'config_values',
                        "`user_config_expires`.`field` = 'EXPIRATION_DATE' AND `user_config_expires`.`range_id` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->select("FROM_UNIXTIME(`user_config_expires`.`value`, '%Y-%m-%d %H:%i:%s') AS `expires`");
                    break;
                case 'language':
                    $query->select('`user_info`.`preferred_language` AS `language`');
                    break;
                case 'userdomains':
                    $query->join(
                        'user_userdomains',
                        'user_userdomains',
                        "`user_userdomains`.`user_id` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->join(
                        'userdomains',
                        'userdomains',
                        "`user_userdomains`.`userdomain_id` = `userdomains`.`userdomain_id`",
                        'LEFT JOIN'
                    );
                    $query->select("GROUP_CONCAT(DISTINCT `userdomains`.`name` ORDER BY `userdomains`.`name` ASC SEPARATOR '|') AS `userdomains`");
                    break;
                case 'userdomain_ids':
                    $query->join(
                        'user_userdomains',
                        'user_userdomains',
                        "`user_userdomains`.`user_id` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->select("GROUP_CONCAT(DISTINCT `user_userdomains`.`userdomain_id` SEPARATOR '|') AS `userdomain_ids`");
                    break;
                case 'roles':
                    $query->join(
                        'roles_user',
                        'roles_user',
                        "`roles_user`.`userid` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->join(
                        'roles',
                        'roles',
                        "`roles`.`roleid` = `roles_user`.`roleid`",
                        'LEFT JOIN'
                    );
                    $query->select("GROUP_CONCAT(DISTINCT `roles`.`rolename` ORDER BY `roles`.`rolename` ASC SEPARATOR '|') AS `roles`");
                    break;
                case 'role_ids':
                    $query->join(
                        'roles_user',
                        'roles_user',
                        "`roles_user`.`userid` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->select("GROUP_CONCAT(DISTINCT `roles_user`.`roleid` SEPARATOR '|') AS `role_ids`");
                    break;
                case 'studycourses':
                    $query->join(
                        'user_studiengang',
                        'user_studiengang',
                        "`user_studiengang`.`user_id` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->join(
                        'fach',
                        'fach',
                        "`fach`.`fach_id` = `user_studiengang`.`fach_id`",
                        'LEFT JOIN'
                    );
                    $query->join(
                        'abschluss',
                        'abschluss',
                        "`abschluss`.`abschluss_id` = `user_studiengang`.`abschluss_id`",
                        'LEFT JOIN'
                    );
                    $query->select("GROUP_CONCAT(DISTINCT CONCAT(`fach`.`name`, ': ', `abschluss`.`name`, ': ', `user_studiengang`.`semester`) SEPARATOR '|') AS `studycourses`");
                case 'institutes':
                    $query->join(
                        'user_inst',
                        'user_inst',
                        "`user_inst`.`user_id` = `auth_user_md5`.`user_id`",
                        'LEFT JOIN'
                    );
                    $query->join(
                        'Institute',
                        'Institute',
                        "`Institute`.`Institut_id` = `user_inst`.`Institut_id`",
                        'LEFT JOIN'
                    );
                    $query->select("GROUP_CONCAT(DISTINCT `Institute`.`Name` ORDER BY `Institute`.`Name` ASC SEPARATOR '|') AS `institutes`");
                    break;
                default:
                    if (preg_match('/^[a-f0-9]{32}$/', $attribute)) {
                        if (isset($this->attributes[$attribute])) {
                            $query->join(
                                'df_'.$attribute,
                                'datafields_entries',
                                "`df_".$attribute."`.`datafield_id` = '".$attribute."' AND `df_".$attribute."`.`range_id` = `auth_user_md5`.`user_id`",
                                'LEFT JOIN'
                            );
                            $query->select('`df_'.$attribute.'`.`content` AS `'.$attribute.'`');
                        }
                    }
            }
        }
        return $query;
    }

    public function mapDataForUser(\UserAdminAreaHull $hull)
    {
        foreach ($this->attributes as $index => $name) {
            switch ($index) {
                case 'avatar':
                    $hull->innerValue['avatar'] = \Avatar::getAvatar($hull->innerValue['user_id'])->getURL(\Avatar::NORMAL);
                    break;
                case 'language':
                    $hull->innerValue['language'] = $hull->innerValue['language'] ?: \Config::get()->DEFAULT_LANGUAGE;
                    break;
            }
        }
        \NotificationCenter::postNotification('UserAdminAreaUserExportData', $hull);
        return $hull;
    }

    protected function datafieldAccessAllowed(\DataField $datafield, $user = null)
    {
        $user || $user = \User::findCurrent();
        $access = \SuperDatafieldAccess::findOneBySQL('datafield_id = :datafield_id AND user_id = :user_id', [
            'datafield_id' => $datafield->id,
            'user_id' => $user->id
        ]);
        return (bool) $access;
    }
}
