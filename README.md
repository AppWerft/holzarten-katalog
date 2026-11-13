# Holzarten-Katalog

Wordpressmodul, das woocommerce um Holzarten erweitert

## Datenquellen

- EN 13556 – Holzarten und Handelsbezeichnungen
- Erweiterte Eigenschaften aus Wikipedia

![Holzarten-Übersicht](images/holzartenadmin.png)

## Datenmodell

Die Holzarten werden anhand ihrer EN-13556-Codes identifiziert.
Die zusammengeführten Daten befinden sich in holzarten_gesamt.json. Daten (Hölzer und Bilder) lassen sich exportieren und wieder in einer anderen Instanz importieren.

## Holzbilder
Bilder lassen sich klassisch mit lokaler Dateiauswahl oder per Pasting über die Zwischenanlage einfügen.

![Holzarten-Hochlade](images/holzhochlade.png)


## Holzschlagort

Die Geoposition lässt sich im woocommerce Teil als Attribut komfortable einpflegen. Eine Ortssuche unterstützt dabei.
Nach Eingabe erscheint eine Liste von möglichen Treffern. 

![Holzarten-Holzort](images/magdala.png)

Nach Auswahl wird die Karte auf den Punkt zentriert und die Position makiert. Die Geodaten können dann dem Produkt zugeordnet werden.

![Holzarten-Holzort](images/holzverorte.png)

## Installation

Das Plugin lässt sich [hier runterladen](holzarten-katalog-plugin.zip). Danach lässt es sich in Pluginverwaltung von WP einbinden 

## Anwenung im Frontend (öffentliche Seite) 

### Liste der Holzarten

Eine bebilderte Liste aller aktiven Holzarten lässt sich mit einem Shortcode im Blockeditor anzeigen:

`[hk_holzarten]`

### Holzart in der Urnenbeschreibung

### Fundstelle des Holzes


