# Dynamisches Admin-Dashboard

Das Admin-Dashboard ist modular aufgebaut und kann pro Nutzer individuell angepasst werden. Der Fokus liegt auf einer
klaren, informativen Übersicht mit der monatlichen Kostenbilanz als Hauptkennzahl.

## Ziel

- eine strukturierte Dashboard-Übersicht ohne Überladung
- zentrale Darstellung der monatlichen Kostenbilanz
- einfache Ein-/Ausblendung einzelner Widgets pro Nutzer
- eigene Widgets aus sicheren Vorlagen pro Navigationsbereich

## Umsetzung

Das Dashboard wird über die Filament-Admin-Page `Dashboard` aufgebaut und lädt Widgets dynamisch je nach gespeicherten
Nutzerpräferenzen.

### Kernfunktionen

- `MonthlyBalanceStatsWidget`:zeigt die aktuelle Bilanz in Stats-Form
- `MonthlyBalanceChartWidget`: zeigt den Trend über mehrere Monate
- `UpcomingTransactionsTableWidget`: listet anstehende oder relevante Transaktionen
- `PortfolioOverviewWidget`: stellt weitere Kennzahlen als Custom-Widget dar

### Nutzerpräferenzen

Die Sichtbarkeit der Widgets sowie der Bilanzmodus werden pro Nutzer gespeichert. Dazu wird eine eigene Preference-Logik
verwendet:

- Widgets ein-/ausblenden
- Bilanzmodus: `both`, `forecast` oder `actual`
- bevorzugte Währung, standardmäßig `EUR`

## Datenbasis

Die Monatsbilanz wird zentral im Service `DashboardMetricsService` berechnet:

- Prognose aus `FixedCost`-Intervallen
- Istwerte aus `Transaction`-Datensätzen, aggregiert über `Transaction::date`
- Währungsfilter mit `EUR` als Default

## Wichtige Dateien

- `laravel/app/Filament/Admin/Pages/Dashboard.php`
- `laravel/app/Services/DashboardMetricsService.php`
- `laravel/app/Filament/Admin/Clusters/Settings/Pages/DashboardSettingsPage.php`
- `laravel/app/Models/DashboardWidgetPreference.php`
- `laravel/database/migrations/2026_08_10_130000_create_dashboard_widget_preferences_table.php`

## Einstellungen

Im Settings-Cluster gibt es eine eigene Dashboard-Seite mit:

- Toggles für jedes Widget
- Auswahl des Bilanzmodus
- Eingabe der bevorzugten Währung
- Schnellaktionen zum Aktivieren oder Deaktivieren aller Widgets

## Eigene Widgets

Auf derselben Einstellungsseite können Benutzer eigene Dashboard-Widgets anlegen, bearbeiten, aktivieren/deaktivieren
und löschen. Beim Anlegen wird zuerst eine `NavigationGroup` (z. B. `BANKS`) und danach eine verfügbare Vorlage dieses
Bereichs ausgewählt. Die sechs vorhandenen Bereiche besitzen jeweils eine eigene Template-Klasse, sodass weitere
Vorlagen ohne Änderungen an der zentralen Dashboard-Seite ergänzt werden können.

Unterstützte Widget-Typen:

- `chart`: Chart.js-Daten mit Labels und Datensätzen
- `stat`: eine oder mehrere Filament-Stat-Karten
- `table`: userbezogene Filament-Tabelle mit fest definierten Spalten

Die Template-Formulare erzeugen die Konfiguration dynamisch. Benutzer bearbeiten niemals Raw-JSON; intern wird die
normalisierte Konfiguration in `custom_dashboard_user_widgets.configuration` gespeichert. Ein Beispiel sieht
konzeptionell
so aus:

```json
{
    "currency": "EUR",
    "months": 6
}
```

Template-Key, Widget-Typ, Breite (`1`, `2`, `4` oder `full`), Sortierung und Aktivstatus werden getrennt gespeichert.
Die Ausgabe wird durch jeweils einen Renderer-Service für Chart-, Stat- und Table-Widgets erzeugt. Vorlagen akzeptieren
keine freien Klassen-, PHP-, SQL- oder Query-Angaben; alle Datenabfragen bleiben serverseitig definiert und werden auf
den
angemeldeten Benutzer begrenzt.

Zum Hinzufügen einer Vorlage wird die passende Bereichsklasse unter
`laravel/app/Services/Dashboard/Widgets/Templates` erweitert. Das Template liefert dabei mindestens einen stabilen Key,
ein dynamisches Filament-Formschema, Default-/Normalisierungswerte und die typisierte Datenausgabe beziehungsweise
Tabellenkonfiguration.
