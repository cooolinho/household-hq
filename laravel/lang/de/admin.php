<?php

return [
    // Resources
    'resource' => [

        // ContactPerson / Ansprechpartner
        'contact_person' => [
            'navigation_label' => 'Ansprechpartner',
            'model_label' => 'Ansprechpartner',
            'plural_model_label' => 'Ansprechpartner',
        ],

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

        // MeasurementDevice / Messgerät
        'measurement_device' => [
            'navigation_label' => 'Messgeräte',
            'model_label' => 'Messgerät',
            'plural_model_label' => 'Messgeräte',
        ],

        // ReadingEntry / Ablesung
        'reading_entry' => [
            'navigation_label' => 'Ablesungen',
            'model_label' => 'Ablesung',
            'plural_model_label' => 'Ablesungen',
        ],

        // BankAccount / Bank-Konto
        'bank_account' => [
            'navigation_label' => 'Bank-Konten',
            'model_label' => 'Bank-Konto',
            'plural_model_label' => 'Bank-Konten',
        ],

        // CSVImportProfile / CSV-Importprofil
        'csv_import_profile' => [
            'navigation_label' => 'CSV-Importprofile',
            'model_label' => 'CSV-Importprofil',
            'plural_model_label' => 'CSV-Importprofile',
        ],

        // FixedCost / Fixkosten
        'fixed_cost' => [
            'navigation_label' => 'Fixkosten',
            'model_label' => 'Fixkosten',
            'plural_model_label' => 'Fixkosten',
        ],

        // Insurance / Versicherung
        'insurance' => [
            'navigation_label' => 'Versicherungen',
            'model_label' => 'Versicherung',
            'plural_model_label' => 'Versicherungen',
        ],

        // MatchingSuggestion / Matching-Vorschlag
        'matching_suggestion' => [
            'navigation_label' => 'Matching-Vorschläge',
            'model_label' => 'Matching-Vorschlag',
            'plural_model_label' => 'Matching-Vorschläge',
        ],

        // Transaction / Transaktion
        'transaction' => [
            'navigation_label' => 'Transaktionen',
            'model_label' => 'Transaktion',
            'plural_model_label' => 'Transaktionen',
        ],

        // Article / Artikel
        'article' => [
            'navigation_label' => 'Artikel',
            'model_label' => 'Artikel',
            'plural_model_label' => 'Artikel',
        ],

        // Collection / Sammlung
        'collection' => [
            'navigation_label' => 'Sammlungen',
            'model_label' => 'Sammlung',
            'plural_model_label' => 'Sammlungen',
        ],

        // Location / Ort
        'location' => [
            'navigation_label' => 'Orte',
            'model_label' => 'Ort',
            'plural_model_label' => 'Orte',
        ],

        // Tag / Schlagwort
        'tag' => [
            'navigation_label' => 'Tags',
            'model_label' => 'Tag',
            'plural_model_label' => 'Tags',
        ],
    ],
];
