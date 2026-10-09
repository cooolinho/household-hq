# CSV-Import von Transaktionen

Kontoauszüge werden über den Assistenten **Finanzen → CSV-Import** (`/app/transactions/csv-import`) importiert.
Er ist auch über die Aktion „Transaktionen importieren (CSV)“ im Transaktions-Tab eines Bankkontos
(`?bankAccount=<id>`, Profil des Kontos wird vorausgewählt) und über „Importieren“ in der Liste der
Import-Profile (`?profile=<id>`) erreichbar.

## Ablauf

1. **Datei & Format** – Bankkonto, optional ein Import-Profil und die CSV-Datei wählen. Das Format (Trennzeichen,
   Kodierung, Betrags-/Datumsformat, Kopfzeile, zu überspringende Zeilen) kommt aus dem Profil und kann angepasst
   werden. Ohne Profil wird das Trennzeichen automatisch erkannt.
2. **Vorschau** – erkannte Spalten, Anzahl Spalten/Datensätze, Beispielzeilen und Zeilen mit abweichender
   Spaltenanzahl.
3. **Zuordnung** – je Transaktionsfeld eine CSV-Spalte oder „Nicht importieren“. Mit Profil wird die Zuordnung
   automatisch über die gespeicherten Spaltennamen wiederhergestellt; fehlende, neue und mehrdeutige Spalten werden
   angezeigt. Ohne Profil werden Spalten anhand typischer Spaltennamen (Buchungstag, Betrag, Verwendungszweck, …)
   vorgeschlagen. Die Zuordnung kann als neues Profil gespeichert oder in das gewählte Profil übernommen und dem
   Konto zugewiesen werden.
4. **Prüfung** – Trockenlauf über die ganze Datei: wie viele Transaktionen importiert werden, Duplikate und
   fehlerhafte Datensätze mit CSV-Zeilennummer und Grund. Bei Fehlern ist der Import gesperrt, außer
   „Fehlerhafte Datensätze überspringen“ ist aktiv.
5. **Ergebnis** – importiert, übersprungen, Fehler, Duplikate sowie die betroffenen Zeilen.

Nach dem Import wird wie bisher der Kontostand aktualisiert und Kategorisierung, Fixkosten-Matching,
Erkennung wiederkehrender Zahlungen und Statistiken angestoßen.

## Architektur

Die Logik liegt unabhängig von Filament in `laravel/app/Services/TransactionImport`; die Seite
`App\Filament\App\Pages\TransactionCsvImportPage` sammelt nur Eingaben und zeigt Ergebnisse.

| Klasse | Aufgabe |
|---|---|
| `CsvFormat`, `CsvEncoding`, `AmountFormat` | Format-Konfiguration (aus Profil oder Formular) |
| `CsvReader` | streamendes Lesen (fgetcsv), BOM, Präambel, Zeilennummern, Kodierung → UTF-8, Steuerzeichen entfernen |
| `CsvAnalyzer` / `CsvAnalysis` | Vorschau, Spaltenanzahl, Datensätze, Trennzeichen-Erkennung |
| `TransactionImportField` | explizite Liste der Mapping-Ziele; Typ wird aus den Casts von `Transaction` abgeleitet (`ValueType`) |
| `MappingResolver` / `ResolvedMapping` | Profil ↔ CSV-Spalten, Vorschläge, Profil-Attribute speichern |
| `ValueTransformer` | Datum, Beträge (de/en/auto), Text, Boolean, Enum |
| `TransactionRowValidator` | Mapping + Umwandlung + Pflichtfelder je Datensatz (`RowResult`) |
| `DuplicateDetector` | Abgleich über `Transaction::hash` |
| `TransactionImportService` | `review()` (Trockenlauf) und `import()` (in Chunks, in einer DB-Transaktion) → `ImportReport` |

Neue importierbare Spalten werden in `TransactionImportField` ergänzt; Enum-/Boolean-Casts am Model werden
automatisch berücksichtigt.

## Import-Profile

Profile (`financial_csv_import_profiles`) sind wie bisher haushaltsweit und werden Bankkonten über
`csv_profile_id` zugewiesen. Neu sind `encoding`, `has_header`, `date_format`, `header_mapping`
(Feld → Spaltenname) und `header_columns` (alle Spalten beim Speichern, zur Erkennung neuer Spalten).
Das bestehende `mapping` (Feld → Spaltenindex) bleibt erhalten und dient als Fallback für ältere Profile,
Dateien ohne Kopfzeile und doppelte Spaltennamen (z. B. zweimal „Währung“ bei ING).

## Formate

- Datum: `31.12.2026`, `31.12.26`, `1.2.2026`, `2026-12-31` (auch mit Uhrzeit), `31/12/2026`, `31-12-2026`,
  `2026/12/31`, `20261231` oder ein festes PHP-Format im Profil (z. B. `m/d/Y`). Ungültige Tage wie `31.02.` sind Fehler.
- Beträge: `12,34`, `1.234,56`, `-42,50`, `42,50-`, `1.234,56 €` (deutsch), `1,234.56` (englisch) oder automatische
  Erkennung. Ein einzelnes Trennzeichen mit 1–2 Nachkommastellen gilt immer als Dezimaltrenner (`12.34` auch im
  deutschen Format).
- Leere Werte werden als `null` gespeichert; Pflichtfelder sind Buchungsdatum und Betrag.

## Duplikate

`financial_transactions.hash` ist eindeutig und wird wie bisher über `Transaction::createHash()` gebildet
(Buchungsdatum, Wertstellung, Betrag, Saldo, Auftraggeber, Buchungstext, Verwendungszweck, Benutzer).
Der Importer bildet den Hash identisch zum früheren Import, daher werden auch bereits importierte Umsätze erkannt.
Identische Zeilen innerhalb einer Datei werden nur einmal importiert; `insertOrIgnore` schützt zusätzlich bei
parallelen Importen.

Grenzen des bestehenden Datenmodells:

- Zwei echte, vollständig identische Umsätze (gleicher Tag, Betrag, Text) ohne Saldo-Spalte sind nicht
  unterscheidbar und werden nur einmal importiert. Exporte mit Saldo-Spalte sind davon nicht betroffen.
- Der Hash enthält den Benutzer: importieren zwei Haushaltsmitglieder dieselbe Datei, entstehen zwei Kopien.
  Minimale Erweiterung, falls nötig: `bank_account_id` statt `user_id` in den Hash aufnehmen (erfordert eine
  Migration, die bestehende Hashes neu berechnet) oder eine von der Bank gelieferte Buchungs-ID speichern.

## Sicherheit

- Upload: nur `.csv`/`.txt`, maximal 10 MB, Speicherung unter einem zufälligen Namen im Benutzerverzeichnis des
  privaten Disks `transaction_import`; Dateipfad und Analyse sind gesperrte Livewire-Properties, die Datei wird nach
  dem Import gelöscht.
- Nur eigene Bankkonten sind auswählbar; Konto und Mapping werden bei jeder Aktion serverseitig erneut geprüft.
- Kein Mass Assignment: übernommen werden nur Felder aus `TransactionImportField`, Systemspalten setzt der Importer.
- CSV-Inhalte werden nur als Text gelesen, nie ausgewertet, und in Blade escaped ausgegeben. Werte wie `=SUMME(…)`
  werden unverändert gespeichert (Bankdaten und Duplikat-Hash bleiben unverfälscht). **Ein künftiger CSV-Export muss
  Werte, die mit `=`, `+`, `-`, `@`, Tab oder CR beginnen, escapen** (z. B. mit vorangestelltem `'`), um Formula
  Injection in Tabellenkalkulationen zu verhindern.
- Große Dateien werden zeilenweise gestreamt und in Chunks à 500 Datensätzen geprüft und geschrieben;
  Fehlerlisten im Bericht sind begrenzt, die Zähler vollständig.
