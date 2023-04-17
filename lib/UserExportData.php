<?php

namespace UserAdmin;

class UserExportData
{
    protected $attributes = [];
    public $data = [];

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
            'last_activity' => _('Zuletzt Online'),
            'expires' => _("Ablaufdatum"),
            'language' => _('Sprache'),
            'gender' => _('Geschlecht'),
            'visible' => _('Sichtbarkeit'),
            'userdomains' => _('Nutzerdomänen'),
            'roles' => _('Rollen'),
            'studycourses' => _('Studiengänge'),
            'institutes' => _('Einrichtungen')
        ];
        if (!$GLOBALS['perm']->have_perm('root')) {
            unset($this->attributes['locked']);
            unset($this->attributes['lock_comment']);
            unset($this->attributes['last_activity']);
            unset($this->attributes['expires']);
            unset($this->attributes['userdomains']);
            unset($this->attributes['roles']);
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

    public function getDataForUser($user)
    {
        $data = [];
        foreach ($this->attributes as $index => $name) {
            switch ($index) {
                case 'user_id':
                    $data['user_id'] = $user['user_id'];
                    break;
                case 'title_front':
                    $data['title_front'] = $user['title_front'];
                    break;
                case 'vorname':
                    $data['vorname'] = $user['Vorname'];
                    break;
                case 'nachname':
                    $data['nachname'] = $user['Nachname'];
                    break;
                case 'title_rear':
                    $data['title_rear'] = $user['title_rear'];
                    break;
                case 'email':
                    $data['email'] = $user['Email'];
                    break;
                case 'username':
                    $data['username'] = $user['username'];
                    break;
                case 'avatar':
                    $data['avatar'] = \Avatar::getAvatar($user['user_id'])->getURL(\Avatar::NORMAL);
                    break;
                case 'status':
                    $data['status'] = $user['perms'];
                    break;
                case 'locked':
                    $data['locked'] = $user['locked'];
                    break;
                case 'lock_comment':
                    $data['lock_comment'] = $user['lock_comment'];
                    break;
                case 'last_activity':
                    $statement = \DBManager::get()->prepare("
                        SELECT last_lifesign
                        FROM user_online
                        WHERE user_id = :user_id
                    ");
                    $statement->execute(array(
                        'user_id' => $user['user_id']
                    ));
                    $data['last_activity'] = $statement->fetchAll(\PDO::FETCH_COLUMN);
                case 'expires':
                    $data['expires'] = $user->config->EXPIRATION_DATE
                        ? date('c', $user->config->EXPIRATION_DATE)
                        : '';
                    break;
                case 'language':
                    $data['language'] = $user['preferred_language'] ?: \Config::get()->DEFAULT_LANGUAGE;
                    break;
                case 'gender':
                    $map_geschlecht = [
                        0 => '',
                        1 => 'm',
                        2 => 'f',
                        3 => 'd'
                    ];
                    $data['gender'] = $map_geschlecht[$user['geschlecht']];
                    break;
                case 'visible':
                    $data['visible'] = $user['visible'];
                    break;
                case 'userdomains':
                    $statement = \DBManager::get()->prepare("
                        SELECT userdomains.name
                        FROM userdomains
                            INNER JOIN user_userdomains ON (user_userdomains.userdomain_id = userdomains.userdomain_id)
                        WHERE user_userdomains.user_id = ?
                    ");
                    $statement->execute([$user['user_id']]);
                    $userdomains = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
                    $data['userdomains'] = $userdomains
                        ? implode('|', $userdomains)
                        : '';
                    break;
                case 'roles':
                    $roles = \RolePersistence::getAssignedRoles($user['user_id']);
                    $data['roles'] = $roles
                        ? implode('|', array_map(function ($r) { return $r->getRolename(); }, $roles))
                        : '';
                    break;
                case 'studycourses':
                    $statement = \DBManager::get()->prepare("
                        SELECT CONCAT(abschluss.name, ' ', user_studiengang.semester)
                        FROM user_studiengang
                            LEFT JOIN fach ON (fach.fach_id = user_studiengang.fach_id)
                            LEFT JOIN abschluss ON (abschluss.abschluss_id = user_studiengang.abschluss_id)
                        WHERE user_studiengang.user_id = ?
                    ");
                    $statement->execute([$user['user_id']]);
                    $studiengaenge = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
                    $data['studycourses'] = $studiengaenge
                        ? implode('|', $studiengaenge)
                        : '';
                    break;
                case 'institutes':
                    $statement = \DBManager::get()->prepare("
                        SELECT Name
                        FROM Institute
                            INNER JOIN user_inst ON (user_inst.Institut_id = Institute.Institut_id)
                        WHERE user_inst.user_id = ?
                    ");
                    $statement->execute([$user['user_id']]);
                    $institutes = $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
                    $data['institutes'] = $institutes
                        ? implode('|', $institutes)
                        : '';
                    break;
                default:
                    if (preg_match('/^[a-f0-9]{32}$/', $index)) {
                        if (isset($this->attributes[$index])) {
                            $datafield_entry = \DatafieldEntryModel::findOneBySQL('datafield_id = :datafield_id AND range_id = :user_id', [
                                'datafield_id' => $index,
                                'user_id' => $user['user_id']
                            ]);
                            $data[$index] = $datafield_entry ? $datafield_entry['content'] : '';
                        }
                    }
            }
        }
        $this->data[$user['user_id']] = $data;
        \NotificationCenter::postNotification('UserAdminAreaUserExportDataReturnsDataForUser', $this, $user);
        $data = $this->data[$user['user_id']];
        unset($this->data[$user['user_id']]);
        return $data;
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
