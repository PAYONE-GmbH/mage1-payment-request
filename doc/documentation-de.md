# Dokumentation
Dieses Modul stellt die Payment Request API (PRA) Funktion für das Payone_Core Modul bereit.

Es ermöglicht einen intuitiven und sehr einfachen Checkoutprozess für Ihre Kunden. Daten wie z.B. die Adressen und
Zahlungsmethoden werden im Google Profil gespeichert und können sehr einfach im Checkoutprozess verwendet werden.

Aktuell unterstützt die PRA nur die folgenden Browser:

* Chrome 53 und neuer - Android
* Chrome 61 und neuer - Desktop & iOS
* Edge 15 und neuer - Desktop

Aktuell unterstützt die PRA die folgenden Zahlungsmethoden:

* Kreditkarte

Die Kreditkarten-Einstellungen und deren Verarbeitung finden über das Payone_Core Module statt.

## Backend Konfiguration

Die modulspezifische Konfiguration befindet sich im allgemeinen Bereich der Payone Modulkonfiguration.

### Paynow Button aktivieren
Diese Option aktiviert oder deaktiviert das gesamte PRA-Modul.

### Weiterleiten auf die Bestätigungsseite
Diese Option legt fest, ob der Kunde auf die Standard-Bestätigungsseite von Magento weitergeleitet wird oder auf der
aktuellen Seite verbleibt.
