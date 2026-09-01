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
                'import_warning' => 'Import Warnung',
                'source_email_subject' => 'Quelle E-Mail Betreff',
                'source_email_from' => 'Quelle E-Mail Absender',
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

        'imported_email' => [
            'navigation_label' => 'E-Mail Imports',
            'model_label' => 'E-Mail Import',
            'plural_model_label' => 'E-Mail Imports',
            'fields' => [
                'imap_account' => 'IMAP Konto',
                'subject' => 'Betreff',
                'from_name' => 'Absendername',
                'from_email' => 'Absender E-Mail',
                'message_id' => 'Message-ID',
                'uid' => 'UID',
                'mailbox_folder' => 'Mailbox Ordner',
                'attachments_count' => 'Anhaenge',
                'attachments' => 'Anhaenge',
                'warning_count' => 'Warnungen',
                'warning_summary' => 'Warnungsdetails',
                'marked_as_read' => 'Als gelesen markiert',
                'moved_to_processed' => 'In processed verschoben',
                'received_at' => 'Empfangen am',
                'processed_at' => 'Verarbeitet am',
            ],
            'filters' => [
                'moved_to_processed' => 'In processed verschoben',
                'has_warnings' => 'Hat Warnungen',
            ],
        ],

        'application_log' => [
            'navigation_label' => 'System-Logs',
            'model_label' => 'System-Log',
            'plural_model_label' => 'System-Logs',
            'fields' => [
                'event' => 'Event',
                'channel' => 'Kanal',
                'level' => 'Level',
                'message' => 'Nachricht',
                'user' => 'Benutzer',
                'occurred_at' => 'Zeitpunkt',
            ],
            'filters' => [
                'event' => 'Event',
                'level' => 'Level',
                'occurred_between' => 'Zeitraum',
                'from' => 'Von',
                'until' => 'Bis',
            ],
        ],

        // MeasurementDevice / Messgerät
        'measurement_device' => [
            'navigation_label' => 'Messgeräte',
            'model_label' => 'Messgerät',
            'plural_model_label' => 'Messgeräte',
        ],

        // MeasurementDeviceContract / Vertrag
        'measurement_device_contract' => [
            'navigation_label' => 'Verträge',
            'model_label' => 'Vertrag',
            'plural_model_label' => 'Verträge',
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
            'navigation_label' => 'Ein-/Ausgaben',
            'model_label' => 'Fixkosten',
            'plural_model_label' => 'Fixkosten',
        ],

        'fixed_cost_category' => [
            'navigation_label' => 'Kategorien',
            'model_label' => 'Kategorie',
            'plural_model_label' => 'Kategorien',
        ],

        // Insurance / Versicherung
        'insurance' => [
            'navigation_label' => 'Übersicht',
            'model_label' => 'Versicherung',
            'plural_model_label' => 'Versicherungen',
        ],

        'insurance_category' => [
            'navigation_label' => 'Kategorien',
            'model_label' => 'Versicherungskategorie',
            'plural_model_label' => 'Versicherungskategorien',
        ],

        // MatchingSuggestion / Matching-Vorschlag
        'matching_suggestion' => [
            'navigation_label' => 'Matching-Vorschläge',
            'model_label' => 'Matching-Vorschlag',
            'plural_model_label' => 'Matching-Vorschläge',
        ],

        // RecurringTransactionSuggestion / Vorschlag fuer wiederkehrende Transaktionen
        'recurring_transaction_suggestion' => [
            'navigation_label' => 'Wiederkehrende Buchungen',
            'model_label' => 'Wiederkehrender Vorschlag',
            'plural_model_label' => 'Wiederkehrende Vorschlaege',
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
