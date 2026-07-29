<?php

return [
    // Resources
    'resource' => [
        // Document / Dokument
        'document' => [
            'navigation_label' => 'Dokumente',
            'model_label' => 'Dokument',
            'plural_model_label' => 'Dokumente',

            'fields' => [
                'advertising' => 'Werbeanzeige',
                'advertising_id' => 'Werbeanzeige',
                'type' => 'Typ',
                'filename' => 'Dateiname',
                'path' => 'Datei',
                'description' => 'Beschreibung',
                'file_size' => 'Dateigröße',
                'mime_type' => 'MIME‑Typ',
                'sort' => 'Sortierung',
                'created_at' => 'Erstellt',
                'updated_at' => 'Aktualisiert',
            ],

            'filters' => [
                'advertising' => 'Werbeanzeige',
                'advertising_status' => 'Werbung Status',
                'type' => 'Typ',
                'mime_category' => 'MIME Kategorie',
                'mime_type' => 'MIME Typ',
                'extension' => 'Dateiendung',
                'path_type' => 'Pfad‑Typ',
                'filename' => 'Dateiname',
                'description' => 'Beschreibung',
                'file_size' => 'Dateigröße',
                'file_size_range' => 'Dateigröße',
                'sort' => 'Sortierung',
                'created' => 'Erstellt',
                'updated' => 'Aktualisiert',
                'missing_on_disk' => 'Auf Disk fehlt',

                // small placeholders
                'placeholder_all' => 'Alle',
            ],

            'placeholders' => [
                'empty' => '-',
            ],

            'mime_categories' => [
                'image' => 'Bilder',
                'pdf' => 'PDF',
                'text' => 'Text',
                'other' => 'Andere / Unbekannt',
            ],

            'helpers' => [
                'filename_auto' => 'Wird automatisch befüllt beim Upload, kann aber manuell geändert werden.',
            ],
        ],
    ],
];
