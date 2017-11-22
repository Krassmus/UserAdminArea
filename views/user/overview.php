<? if (count($users)) : ?>
    <form action="<?= PluginEngine::getLink($plugin, array(), "user/edit") ?>"
          method="post"
          data-dialog>
        <table class="default">
            <thead>
                <tr>
                    <th></th>
                    <th><?= _("Name") ?></th>
                    <th>
                        <input type="checkbox" data-proxyfor=":checkbox[name^=u]">
                    </th>
                </tr>
                <tr>
                    <th colspan="100" style="text-align: right">
                        <?= \Studip\Button::create(_("Bearbeiten")) ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <? foreach ($users as $user) : ?>
                    <tr>
                        <td>
                            <?= Avatar::getAvatar($user->getId())->getImageTag(Avatar::MEDIUM, array("style" => "max-width: 50px; max-height: 50px;")) ?>
                        </td>
                        <td>
                            <a href="<?= URLHelper::getLink("dispatch.php/admin/user/edit/".$user->getId()) ?>">
                                <?= htmlReady($user->getFullName()) ?>
                            </a>
                        </td>
                        <td>
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
        'unlocked' => _("Ungesperrte Nutzer")
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

$actions = new ActionsWidget();
$actions->addLink(
    _("Alle Nutzer bearbeiten"),
    PluginEngine::getURL($plugin, array('all' => 1), "user/edit"),
    Icon::create("edit"),
    array('data-dialog' => 1)
);
Sidebar::Get()->addWidget($actions);