<form action="<?= PluginEngine::getLink($plugin, [], 'export/download') ?>"
      method="get"
      class="default">

    <label>
        <input type="checkbox" name="send_as_message" value="1">
        <?= _('Datei als Stud.IP-Nachricht an mich verschicken') ?>
    </label>

    <? foreach ($attributes as $index => $name) : ?>
    <label>
        <input type="checkbox" name="export[]" value="<?= htmlReady($index) ?>"<?= in_array($index, $selected) ? ' checked ' : '' ?>>
        <?= htmlReady($name) ?>
    </label>
    <? endforeach ?>

    <div data-dialog-button>
        <?= \Studip\Button::create(_('Export starten'))?>
    </div>
</form>
