<?php

class ExportController extends PluginController
{
    public function select_action()
    {
        PageLayout::setTitle(_('Daten zum Exportieren auswählen'));
        $dataexporter = new \UserAdmin\UserExportData();
        $this->attributes = $dataexporter->getUserAttributes();
        if (!$GLOBALS['user']->cfg->USERADMINAREA_EXPORTEDATTRIBUTES) {
            $this->selected = [
                'vorname',
                'nachname',
                'email',
                'username',
                'status'
            ];
        } else {
            $this->selected = json_decode($GLOBALS['user']->cfg->USERADMINAREA_EXPORTEDATTRIBUTES);
        }
    }

    public function download_action()
    {
        $attributes = Request::getArray('export');
        $GLOBALS['user']->cfg->store('USERADMINAREA_EXPORTEDATTRIBUTES', json_encode($attributes));

        $output = [];

        $exportdata = new \UserAdmin\UserExportData();
        $attributes_names = $exportdata->getUserAttributes();
        $names = [];
        foreach ($attributes as $index) {
            if (isset($attributes_names[$index])) {
                $names[] = $attributes_names[$index];
            }
        }
        $output[] = $names;

        $query = $exportdata->getUserQuery($attributes);
        $i = 1;
        $hull = new UserAdminAreaHull();
        $hull->metadata = [
            'attributes' => $attributes
        ];
        while ($data = $query->fetch()) {
            $hull->innerValue = $data;
            $exportdata->mapDataForUser($hull);
            $data = $hull->innerValue;
            $output_row = [];
            foreach ($attributes as $index) {
                if (isset($attributes_names[$index])) {
                    $output_row[] = $data[$index];
                }
            }
            $i++;
            $output[] = $output_row;
            if ($i > 5000) {
                //var_dump(memory_get_usage());
                //var_dump(strlen(serialize($output)));
                //die('fin');
            }
        }
        //var_dump(memory_get_usage());
        //var_dump(strlen(serialize($exportdata)));
        //var_dump($exportdata);
        //var_dump(strlen(serialize($output)));
        //die('fin 2');

        if (Request::submitted('send_as_message')) {
            do {
                $message_id = md5(uniqid());
            } while (Message::find($message_id));

            $file = tmpfile();
            $path = stream_get_meta_data($file)['uri'];
            fputs($file, "\xEF\xBB\xBF"); //BOM

            foreach ($output as $row) {
                fputcsv($file, $row, ';', '"');
            }

            $export_csv = StandardFile::create([
                'name' => 'persons.csv',
                'type' => 'text/csv',
                'size' => filesize($path),
                'tmp_name' => $path
            ]);

            $attachment_folder = MessageFolder::createTopFolder($message_id);
            $attachment_folder->addFile($export_csv);

            $messaging = new messaging();
            $messaging->insert_message(
                _('Die von Ihnen angefordertem Nutzerdaten stehen bereit.'),
                User::findCurrent()->username,
                '____%system%____',
                '',
                $message_id,
                '',
                '',
                _('Datenexport'),
                '',
                'normal',
                ['nutzerdatenexport']
            );
            fclose($file);

            $this->redirect('user/overview');
        } else {
            $this->render_csv($output, 'persons.csv');
        }
    }
}
