<style>
    .bulkedit tbody tr {
        opacity: 0.5;
    }
    .bulkedit tbody tr.active {
        opacity: 1;
    }
    .bulkedit .entsperren_hinweis {
        display: none;
    }
    .bulkedit tbody tr.active input:not(:checked) + .entsperren_hinweis {
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
        <?= \Studip\Button::create(_("Alle löschen"), "delete", array('formaction' => PluginEngine::getURL($plugin, array(), "user/delete_all"), 'onClick' => "return window.confirm('"._("Wirklich alle diese Nutzer löschen?")."');")) ?>
    </div>
</form>