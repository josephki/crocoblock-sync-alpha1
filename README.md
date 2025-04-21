# Crocoblock Sync

Ein konfigurierbares WordPress-Plugin zur Synchronisation von Custom Post Type - Meta-Feldern mit Taxonomien für JetEngine/Crocoblock und ACF.

## Beschreibung

Das Crocoblock Sync Plugin ermöglicht es, Custom Post Type (CPT) - Meta-Felder in WordPress automatisch mit entsprechenden Taxonomien zu synchronisieren. Es wurde speziell für den Einsatz mit JetEngine/Crocoblock entwickelt, funktioniert aber auch mit anderen Meta-Feld-Lösungen, wie sie in ACF verwendung finden.

### Hauptfunktionen

* Automatische Synchronisation zwischen Meta-Feldern und Taxonomien
* Intuitiver "Synchronisieren & Speichern"-Button im Gutenberg/Elementor-Editor
* Alphabetische Sortierung der Taxonomie-Terme
* Warnungen bei mehreren ausgewählten Reisethemen
* Unterstützung für beliebige Meta-Felder und Taxonomien
* Anpassbare Meldungen und Warnungen
* Kompatibilität mit JetEngine/Crocoblock und ACF.

## Anforderungen

* WordPress 5.0 oder höher
* PHP 7.4 oder höher
* Kompatibel mit Gutenberg und Elementor

## Installation

1. Lade das Plugin als ZIP-Datei herunter
2. Gehe im WordPress-Admin zu "Plugins" > "Installieren" > "Plugin hochladen"
3. Wähle die ZIP-Datei aus und klicke auf "Jetzt installieren"
4. Aktiviere das Plugin

## Konfiguration

Nach der Aktivierung findest du die Einstellungen unter "Einstellungen" > "Crocoblock Sync". Hier kannst du:

### 1. Feld-Zuordnungen konfigurieren

* **Meta-Feld**: Der Name des Meta-Felds (z.B. "reisethemen_meta")
* **Taxonomie**: Die zugehörige Taxonomie (z.B. "reisethemen")
* **Post-Typ**: Der Post-Typ, für den die Synchronisation gelten soll (z.B. "ir-tours")

### 2. Benutzerdefinierte Nachrichten anpassen

* Warnungen bei mehreren ausgewählten Themen
* Text für den Synchronisationsbutton
* Erfolgs- und Fehlermeldungen

## Verwendung

Nach der Konfiguration erscheint ein "Synchronisieren & Speichern"-Button im Editor von Beiträgen der konfigurierten Post-Typen. Verwende diesen Button, bevor du den Beitrag speicherst, um die Meta-Felder mit den Taxonomien zu synchronisieren.

## Technische Details

Das Plugin:
* Ermöglicht die Konfiguration mehrerer Meta-Feld-Taxonomie-Zuordnungen
* Unterstützt alphabetische Sortierung der Taxonomie-Terme
* Kann mit benutzerdefinierten Post-Typen verwendet werden
* Bietet einen anpassbaren Synchronisationsprozess

## Entwickler

Joseph Kisler - Webwerkstatt  
Freiung 16/2/4, A-4600 Wels

## Versionsverlauf

### 1.0
* Erstveröffentlichung
* Konfigurierbare Feld-Zuordnungen
* Anpassbare Meldungen
* Unterstützung für Gutenberg und Elementor

## Lizenz

[GPLv2 oder später](https://www.gnu.org/licenses/gpl-2.0.html)

## Support

Für Support-Anfragen kontaktiere bitte den Entwickler unter info@web-werkstatt.at.

## Mitwirken

Beiträge zum Projekt sind willkommen. Bitte reiche einen Pull Request ein oder erstelle ein Issue auf der GitHub-Projektseite.
