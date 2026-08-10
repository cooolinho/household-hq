# Dynamisches Admin-Dashboard

Das Admin-Dashboard ist modular aufgebaut und kann pro Nutzer individuell angepasst werden. Der Fokus liegt auf einer
klaren, informativen Übersicht mit der monatlichen Kostenbilanz als Hauptkennzahl.

## Ziel

- eine strukturierte Dashboard-Übersicht ohne Überladung
- zentrale Darstellung der monatlichen Kostenbilanz
- einfache Ein-/Ausblendung einzelner Widgets pro Nutzer

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
