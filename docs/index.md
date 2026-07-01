# Projektbeschreibung

Mit diesem Portal möchte ich Versicherungen und Kontobewegungen verwalten. Das Portal soll als Tenant-Umgebung aufgebaut 
werden, sodass mehrere Benutzer ihre eigenen Versicherungen und Kontobewegungen verwalten können. Jeder Benutzer hat ein 
privates Profil, in dem persönliche Daten gespeichert werden.

## Privates Profil
- Persönliche Daten speichern.
  - Name
  - Vorname
  - Geburtsdatum
  - Geburtsort
  - Adresse
  - Avatare (Profilbild, etc.)
  - Telefonnummer
  - E-Mail-Adresse

## Versicherungen
- Versicherungen auflisten und Daten zu den Versicherungen speichern.
  - Adresse
  - Versicherungssumme
  - Versicherungsnummer
  - Versicherungsart
  - Versicherungsbeginn
  - Versicherungsende
  - Versicherungsgesellschaft
  - Ansprechpartner
  - Dokumente (Versicherungsvertrag, Schadensmeldung, etc.)

## Konten
- Konten erstellen und verwalten.
    - Kontonummer
    - Bankleitzahl
    - Bankname
    - Kontoinhaber
    - Kontostand
    - Kontotyp (Girokonto, Sparkonto, Kreditkarte, etc.)

- Kontobewegungen auflisten und Daten zu den Kontobewegungen speichern.
  - Datum
  - Betrag
  - Empfänger/Absender
  - Verwendungszweck
  - Kategorie
  - evtl. Beleg (als Datei hochladen)
  - Notizen
  - Versicherung verknüpfen (falls die Kontobewegung mit einer Versicherung zusammenhängt)
  - Taggen (z.B. "Miete", "Stromrechnung", "Gehalt", etc.)

## Funktionen
- Bei Umzug persönliche Adresse den Versicherungen mitteilen (per Email/per PDF Download)
- Bei Umzug persönliche Adresse den Konten mitteilen (per Email/per PDF Download)
- Importieren von Kontobewegungen (CSV, PDF, etc.)
- Exportieren von Kontobewegungen (CSV, PDF, etc.)
- Statistiken zu Kontobewegungen (z.B. Einnahmen/Ausgaben pro Monat, Kategorie, etc.)
- Erinnerungen für Versicherungszahlungen und Kontobewegungen (per Email/per PDF Download)
- Backup der Daten (per Email/per PDF Download)
