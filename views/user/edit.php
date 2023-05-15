<style>
    .bulkedit table.default > tbody > tr {
        opacity: 0.5;
    }
    .bulkedit table.default > tbody tr.active {
        opacity: 1;
    }
    .bulkedit .entsperren_hinweis {
        display: none;
    }
    .bulkedit table.default > tbody > tr.active input:not(:checked) + .entsperren_hinweis {
        display: block;
    }
</style>
<form action="<?= PluginEngine::getLink($plugin, array(), "user/edit") ?>"
      method="post"
      class="default bulkedit"
      data-dialog>

    <? if (Request::get("all")) : ?>
        <input type="hidden" name="all" value="1">
    <? else : ?>
        <? foreach (Request::getArray("u") as $user_id) : ?>
            <input type="hidden" name="u[]" value="<?= htmlReady($user_id) ?>">
        <? endforeach ?>
    <? endif ?>


    <table class="default nohover">
        <thead>
            <tr>
                <th width="50%">
                    <?= _("Zu verändernde Eigenschaft") ?>
                </th>
                <th width="50%"><?= _("Neuen Wert festlegen") ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="locked" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Gesperrt") ?>
                    </label>
                </td>
                <td>
                    <? $value = $controller->getAverageValue($users, "locked") ?>
                    <input type="checkbox"
                           name="locked"
                           value="1"
                        <?= $value == 1 ? " checked" : ""?>
                           onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                    <? if ($value === false) : ?>
                        <div><?= _("Unterschiedliche Werte") ?></div>
                    <? endif ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="lock_comment" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Sperrkommentar") ?>
                    </label>
                </td>
                <td>
                    <? $value = $controller->getAverageValue($users, "lock_comment") ?>
                    <input type="text"
                           name="lock_comment"
                           value="<?= htmlReady($value)?>"
                           placeholder="<?= htmlReady($value || $value === '0' ? $value : ($value === false ? _("Unterschiedliche Werte") : _("Wert eingeben")))?>"
                           onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="lock_rule" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Sperrregel") ?>
                    </label>
                </td>
                <td>
                    <? $value = $controller->getAverageValue($users, "lock_rule") ?>
                    <select name="lock_rule"
                            onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                        <? if (!$value) : ?>
                            <option value=""><?= ($value === false ? " - " ._("Unterschiedliche Werte")." - " : _(" - ")) ?></option>
                        <? endif ?>
                        <option value="none"><?= _("Sperrebenen entfernen") ?></option>
                        <? foreach (LockRule::findAllByType("user") as $lockrule) : ?>
                            <option value="<?= htmlReady($lockrule->getId()) ?>"<?= $lockrule->getId() == $value ? " selected" : "" ?> title="<?= htmlReady($lockrule['description']) ?>">
                                <?= htmlReady($lockrule['name']) ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="add_seminar_id" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Veranstaltung hinzufügen") ?>
                    </label>
                </td>
                <td>
                    <?= QuickSearch::get("add_seminar_id", new SeminarSearch())->setAttributes(array('onChange' => "jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');"))->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="remove_seminar_id" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Veranstaltung entfernen") ?>
                    </label>
                </td>
                <td>
                    <?= QuickSearch::get("remove_seminar_id", new SeminarSearch())->setAttributes(array('onChange' => "jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');"))->render() ?>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="add_institut_id" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Einrichtung hinzufügen") ?>
                    </label>
                </td>
                <td>
                    <select name="add_institut_id"
                            onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                        <option value=""></option>
                        <? foreach (Institute::getInstitutes() as $institute) : ?>
                            <option value="<?= htmlReady($institute['Institut_id']) ?>">
                                <?= !$institute['is_fak'] ? "&nbsp;&nbsp;" : "" ?>
                                <?= htmlReady($institute['Name']) ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="remove_institut_id" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Einrichtung entfernen") ?>
                    </label>
                </td>
                <td>
                    <select name="remove_institut_id"
                            onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                        <option value=""></option>
                        <? foreach (Institute::getInstitutes() as $institute) : ?>
                            <option value="<?= htmlReady($institute['Institut_id']) ?>">
                                <?= !$institute['is_fak'] ? "&nbsp;&nbsp;" : "" ?>
                                <?= htmlReady($institute['Name']) ?>
                            </option>
                        <? endforeach ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="visible" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Sichtbarkeit") ?>
                    </label>
                </td>
                <td>
                    <? $value = $controller->getAverageValue($users, "visible") ?>
                    <select type="text"
                            name="visible"
                            onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                        <? if ($value === false) : ?>
                            <option value=""><?= _("Unterschiedliche Werte") ?></option>
                        <? endif ?>
                        <option value="always"<?= $value === "always" ? " selected" : "" ?>><?= _("Immer") ?></option>
                        <option value="yes"<?= $value === "yes" ? " selected" : "" ?>><?= _("Ja") ?></option>
                        <option value="unknown"<?= $value === "unknown" ? " selected" : "" ?>><?= _("Undefiniert") ?></option>
                        <option value="no"<?= $value === "no" ? " selected" : "" ?>><?= _("Nein") ?></option>
                        <option value="never"<?= $value === "never" ? " selected" : "" ?>><?= _("Niemals") ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="password" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Passwort") ?>
                    </label>
                </td>
                <td>
                    <ul class="clean">
                        <li>
                            <label>
                                <input type="radio" name="password" value="generate" onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked'); jQuery(this).closest('ul').find('label.option').hide();">
                                <?= _("Neu generieren") ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="password" value="set" onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked'); jQuery(this).closest('li').find('label.option').show();">
                                <?= _("Besonderes Passwort setzen") ?>
                            </label>
                            <label class="option" style="display: none;">
                                <?= _("Neues Passwort") ?>
                                <input type="text" name="new_password">
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="checkbox" name="changed_password_mail" value="1" value="generate" onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked'); jQuery(this).closest('li').find('label.option').toggle(jQuery(this).is(':checked'));">
                                <?= _("Eigene Nachricht schreiben") ?>
                            </label>
                            <label class="option" style="display: none;">
                                <?= _("Email-Betreff") ?>
                                <input name="changed_password_subject" type="text">
                            </label>
                            <label class="option" style="display: none;">
                                <?= _("Email-Nachricht") ?>
                                <textarea name="changed_password_mailbody"></textarea>
                            </label>
                        </li>
                    </ul>
                </td>
            </tr>
            <? $userdomains = UserDomain::getUserDomains() ?>
            <? if (count($userdomains)) : ?>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" name="change[]" value="add_userdomain" onChange="jQuery(this).closest('tr').toggleClass('active');">
                            <?= _("Nutzerdomäne hinzufügen") ?>
                        </label>
                    </td>
                    <td>
                        <select name="add_userdomain"
                                onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                            <option value=""></option>
                            <? foreach (UserDomain::getUserDomains() as $domain) : ?>
                                <option value="<?= htmlReady($domain->getID()) ?>">
                                    <?= htmlReady($domain['name']) ?>
                                </option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" name="change[]" value="remove_userdomain" onChange="jQuery(this).closest('tr').toggleClass('active');">
                            <?= _("Nutzerdomäne entfernen") ?>
                        </label>
                    </td>
                    <td>
                        <select name="remove_userdomain"
                                onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                            <option value=""></option>
                            <? foreach (UserDomain::getUserDomains() as $domain) : ?>
                                <option value="<?= htmlReady($domain->getID()) ?>">
                                    <?= htmlReady($domain['name']) ?>
                                </option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
            <? endif ?>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="studiengang" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= _("Studiengang bearbeiten") ?>
                    </label>
                </td>
                <td>
                    <table>
                        <tbody>
                            <tr>
                                <td><?= _("Studiengang") ?></td>
                                <td>
                                    <select name="studiengang_studiengang_id"
                                            onChange="jQuery(this).closest('table').closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                                        <option value=""></option>
                                        <? foreach (StudyCourse::findBySQL("1 = 1 ORDER BY name ASC") as $studiengang) : ?>
                                            <option value="<?= htmlReady($studiengang->getId()) ?>">
                                                <?= htmlReady($studiengang['name']) ?>
                                            </option>
                                        <? endforeach ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><?= _("Abschluss") ?></td>
                                <td>
                                    <select name="studiengang_abschluss_id"
                                            onChange="jQuery(this).closest('table').closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                                        <option value=""></option>
                                        <? foreach (Degree::findBySQL("1 = 1 ORDER BY name ASC") as $abschluss) : ?>
                                            <option value="<?= htmlReady($abschluss->getId()) ?>">
                                                <?= htmlReady($abschluss['name']) ?>
                                            </option>
                                        <? endforeach ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><?= _("Semester") ?></td>
                                <td>
                                    <input type="number"
                                           name="studiengang_semester"
                                           value="1"
                                           min="0"
                                           onChange="jQuery(this).closest('table').closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <? foreach ($datafields as $datafield) : ?>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" name="change[]" value="datafield_<?= $datafield->getId() ?>" onChange="jQuery(this).closest('tr').toggleClass('active');">
                            <?= htmlReady($datafield['name']) ?>
                        </label>
                    </td>
                    <td>
                        <? $value = $controller->getAverageValue($users, "datafield_".$datafield->getId()) ?>
                        <? switch ($datafield->type) {
                            case "bool" : ?>
                                <input type="checkbox"
                                       name="datafield_<?= $datafield->getId() ?>"
                                       value="1"
                                       title="<?= htmlReady($value || $value === '0' ? $value : ($value === false ? _("Unterschiedliche Werte") : _("Wert eingeben")))?>"
                                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');"
                                    <?= $value > 0 ? ' checked' : "" ?>>
                                <? break; case "selectbox" : ?>
                                <select
                                        name="datafield_<?= $datafield->getId() ?>"
                                        value="<?= htmlReady($value)?>"
                                        title="<?= htmlReady($value || $value === '0' ? $value : ($value === false ? _("Unterschiedliche Werte") : _("Wert eingeben")))?>"
                                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                                    <? foreach (explode("\n", $datafield['typeparam']) as $param) : ?>
                                        <option value="<?= htmlReady($param) ?>"<?= $param == $value ? " selected" : "" ?>><?= htmlReady($param) ?></option>
                                    <? endforeach ?>
                                </select>
                                <? break; case "textarea" : ?>
                                <textarea
                                        name="datafield_<?= $datafield->getId() ?>"
                                        placeholder="<?= htmlReady($value || $value === '0' ? $value : ($value === false ? _("Unterschiedliche Werte") : _("Wert eingeben")))?>"
                                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');"
                                ><?= htmlReady($value) ?></textarea>
                                <? break; case "textline" : default : ?>
                                <input type="text"
                                       name="datafield_<?= $datafield->getId() ?>"
                                       value="<?= htmlReady($value)?>"
                                       placeholder="<?= htmlReady($value || $value === '0' ? $value : ($value === false ? _("Unterschiedliche Werte") : _("Wert eingeben")))?>"
                                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">

                            <? } ?>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
    </table>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern"), "save") ?>
        <?= \Studip\Button::create(sprintf(_("Alle %s löschen"), count($users)), "delete", array('formaction' => PluginEngine::getURL($plugin, array(), "user/delete_all"), 'onClick' => "return window.confirm('"._("Wirklich alle diese Nutzer löschen?")."');")) ?>
    </div>
</form>
