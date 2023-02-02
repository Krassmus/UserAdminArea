<? if ($users && count($users)) : ?>
    <form action="<?= PluginEngine::getLink($plugin, array(), "user/edit") ?>"
          method="post"
          data-dialog>
        <table class="default">
            <caption>
                <?= _("Benutzer") ?>
                <span class="actions"><?= sprintf("%s Personen", count($users)) ?></span>
            </caption>
            <thead>
                <tr>
                    <th style="max-width: 100px;"></th>
                    <th><?= _("Name") ?></th>
                    <th><?= _("Status") ?></th>
                    <th><?= _("Zuletzt online") ?></th>
                    <th class="actions">
                        <input type="checkbox" data-proxyfor=":checkbox[name^=u]">
                    </th>
                </tr>
                <tr>
                    <th colspan="100" class="actions">
                        <?= \Studip\Button::create(_("Bearbeiten")) ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($users as $user) : ?>
                    <tr>
                        <td>
                            <a href="<?= Avatar::getAvatar($user->getId())->getURL(Avatar::NORMAL) ?>">
                                <?= Avatar::getAvatar($user->getId())->getImageTag(Avatar::MEDIUM, array("style" => "max-width: 50px; max-height: 50px;")) ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?= URLHelper::getLink("dispatch.php/admin/user/edit/".$user->getId()) ?>">
                                <?= htmlReady($user->getFullName()) ?>
                            </a>
                        </td>
                        <td><?= htmlReady($user['perms']) ?></td>
                        <td>
                            <? if (isset($user_lastlifesign[$user->getId()])) :
                                $inactive = time() - $user_lastlifesign[$user->getId()][0];
                                if ($inactive < 3600 * 24) {
                                    $inactive = gmdate('H:i:s', $inactive);
                                } else {
                                    $inactive = floor($inactive / (3600 * 24)).' '._('Tage');
                                }
                            else :
                                $inactive = _("nie benutzt");
                            endif ?>
                            <?= $inactive ?>
                        </td>
                        <td class="actions">
                            <input type="checkbox" name="u[]" value="<?= htmlReady($user->getId()) ?>">
                        </td>
                    </tr>
                <? endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="100" style="text-align: right">
                        <?= \Studip\Button::create(_("Bearbeiten")) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </form>
<? endif ?>

<?

$search = new SearchWidget(PluginEngine::getURL($plugin, array(), "user/search_text"));
$search->addNeedle(
    _('Freie Suche'),
    'search',
    true,
    null,
    null,
    $GLOBALS['user']->cfg->ADMIN_USER_SEARCHTEXT
);
Sidebar::Get()->addWidget($search);

$institute = new SelectWidget(
    _("Einrichtung"),
    PluginEngine::getURL($plugin, array(), "user/search_institute"),
    "institut_id",
    "post"
);
$institute->setOptions(
    array('all' => "-")
);
foreach (Institute::getMyInstitutes($GLOBALS['user']->id) as $institut) {
    $institute->addElement(
        new SelectElement(
            $institut['Institut_id'],
            (!$institut['is_fak'] ? "  " : "") . $institut['Name'],
            $GLOBALS['user']->cfg->ADMIN_USER_INSTITUTE === $institut['Institut_id']
        ),
        'select-' . $institut['Institut_id']
    );
}
Sidebar::Get()->addWidget($institute);


$faecher = new SelectWidget(
    _("Fächer"),
    PluginEngine::getURL($plugin, array(), "user/search_fach"),
    "fach_id",
    "post"
);
$faecher->setOptions(
    array('' => "")
);
foreach (Fach::getAllEnriched() as $fach) {
    $faecher->addElement(
        new SelectElement(
            $fach->getId(),
            $fach['Name'],
            $GLOBALS['user']->cfg->ADMIN_USER_FACH === $fach->getId()
        ),
        'select-' . $fach->getId()
    );
}
Sidebar::Get()->addWidget($faecher);
$abschluesse = new SelectWidget(
    _("Abschlüsse"),
    PluginEngine::getURL($plugin, array(), "user/search_abschluss"),
    "abschluss_id",
    "post"
);
$abschluesse->setOptions(
    array('' => "")
);
foreach (Abschluss::getAllEnriched() as $abschluss) {
    $abschluesse->addElement(
        new SelectElement(
            $abschluss->getId(),
            $abschluss['Name'],
            $GLOBALS['user']->cfg->ADMIN_USER_ABSCHLUSS === $abschluss->getId()
        ),
        'select-' . $abschluss->getId()
    );
}
Sidebar::Get()->addWidget($abschluesse);
$fachsemester = new SelectWidget(
    _("Fachsemester"),
    PluginEngine::getURL($plugin, array(), "user/search_fachsemester"),
    "fachsemester",
    "post"
);
$fachsemester->setOptions(
    array('' => "")
);
for ($i = 1; $i < 50; $i++) {
    $fachsemester->addElement(
        new SelectElement(
            $i,
            $i,
            $GLOBALS['user']->cfg->ADMIN_USER_FACHSEMESTER == $i
        ),
        'select-' . $i
    );
}
Sidebar::Get()->addWidget($fachsemester);


$locked = new SelectWidget(
    _("Sperr-Filter"),
    PluginEngine::getURL($plugin, array(), "user/search_locked"),
    "locked",
    "post"
);
$locked->setOptions(
    array(
        '' => "",
        'locked' => _("Gesperrte Nutzer"),
        'unlocked' => _("Ungesperrte Nutzer"),
        'locked_and_expired' => _("Gesperrte und abgelaufene Nutzer"),
        'expired' => _('Abglaufene Nutzer')
    ),
    $GLOBALS['user']->cfg->ADMIN_USER_LOCKED
);
Sidebar::Get()->addWidget($locked);

$status_select = new OptionsWidget();
$status_select->setTitle(_('Rollen-Filter'));
$status_config = $config = $GLOBALS['user']->cfg->ADMIN_USER_STATUS ? unserialize($GLOBALS['user']->cfg->ADMIN_USER_STATUS) : array();
$status_select->addCheckbox(
    _("egal"),
    count($status_config) === 0,
    PluginEngine::getURL($plugin, array('remove' => "all"), "user/search_status")
);
foreach (array("user", "autor", "tutor", "dozent", "admin", "root") as $status) {
    $status_select->addCheckbox(
        ucfirst($status),
        in_array($status, $status_config),
        PluginEngine::getURL($plugin, array('toggle' => $status), "user/search_status")
    );
}
foreach (RolePersistence::getAllRoles() as $role) {
    if (!$role->getSystemType()) {
        $status_select->addCheckbox(
            $role->getRolename(),
            in_array($role->getRoleid(), $status_config),
            PluginEngine::getURL($plugin, array('toggle' => $role->getRoleid()), "user/search_status")
        );
    }
}
Sidebar::get()->addWidget($status_select);

$locked = new SelectWidget(
    _("Domänen-Filter"),
    PluginEngine::getURL($plugin, array(), "user/search_userdomain"),
    "domain_id",
    "post"
);
$domains = array(
    '' => "",
    'USER_ADMIN_AREA_NULLDOMAIN' => "Null-Domäne"
);
foreach (UserDomain::getUserDomains() as $domain) {
    $domains[$domain->getID()] = $domain['name'];
}
$locked->setOptions(
    $domains,
    $GLOBALS['user']->cfg->ADMIN_USER_DOMAIN
);
Sidebar::Get()->addWidget($locked);

$inactivity = new SearchWidget(PluginEngine::getURL($plugin, array(), "user/search_inactivity"));
$inactivity->setTitle(_("Inaktiv seit Tagen"));
$inactivity->addNeedle(
    "",
    'inactivity',
    true,
    null,
    null,
    $GLOBALS['user']->cfg->ADMIN_USER_INACTIVITY
);
Sidebar::Get()->addWidget($inactivity);

$status_select = new OptionsWidget();
$status_select->setTitle(_('Eingeloggt-Filter'));
$status_select->addCheckbox(
    _("Nutzer, die noch nie eingeloggt waren"),
    $GLOBALS['user']->cfg->ADMIN_USER_NEVER_ONLINE,
    PluginEngine::getURL($plugin, array(), "user/toggle_never_online")
);
Sidebar::get()->addWidget($status_select);

foreach (DataField::getDataFields("user") as $datafield) {
    if ($datafield['type'] === "bool") {
        $datafield_widget = new SelectWidget(
            sprintf(_("%s-Filter"), $datafield['name']),
            PluginEngine::getURL($plugin, array(), "user/search_datafield"),
            "datafield_".$datafield->getId(),
            "post"
        );
        $options = array(
            '' => "",
            'yes' => _("Ja"),
            'no' =>  _("Nein")
        );
        $datafield_widget->setOptions(
            $options,
            $GLOBALS['user']->cfg->getValue("ADMIN_USER_DATAFIELD_".$datafield->getId()) ? "yes" : ($GLOBALS['user']->cfg->getValue("ADMIN_USER_DATAFIELD_".$datafield->getId()) === "0" ? "no" : false)
        );
        Sidebar::Get()->addWidget($datafield_widget);
    } else {
        $search = new SearchWidget(PluginEngine::getURL($plugin, array('df' => $datafield->getId()), "user/search_datafield"));
        $search->setTitle(sprintf(_("%s-Filter"), $datafield['name']));
        $search->addNeedle(
            $datafield['name'],
            "datafield_".$datafield->getId(),
            true,
            null,
            null,
            $GLOBALS['user']->cfg->getValue("ADMIN_USER_DATAFIELD_".$datafield->getId())
        );
        Sidebar::Get()->addWidget($search);
    }
}


$actions = new ActionsWidget();
$actions->addLink(
    _("ALLE Nutzer bearbeiten"),
    PluginEngine::getURL($plugin, array('all' => 1), "user/edit"),
    Icon::create("edit"),
    array('data-dialog' => 1, 'data-confirm' => _("Wirklich ALLE Nutzer des Systems bearbeiten?"))
);
Sidebar::Get()->addWidget($actions);
