# Finanz-Webapp

## Voraussetzungen
- PHP >= 8.0
- MySQL/MariaDB
- Apache/Nginx mit mod_rewrite
- Cronjob für fetch_daily.php

## Installation
1. Datenbank `db_fin` erstellen.
2. Tabellen:
   - `t_tagesschlusskurse` mit Spalte `Tag DATE` + dynamische Spalten.
   - `t_myassets` mit (Tag DATE, Aktiensymbol VARCHAR(8), KaufVerkauf FLOAT, Tageskurs FLOAT, Kaufwährung VARCHAR(3)).
3. `config.php` mit DB-Zugang anpassen.
4. `watchlist.txt` mit Symbolen füllen (z. B. `NESN.SW`, `UBSG.SW`, `AAPL`, `TSLA`).
5. Cronjob

## Spezifikation für die KI
```
Mach mir in PHP, HTML, CSS und JavaScript eine funktionierende Webseite mit Datenbank-Anknüpfung.

Alles in Bootstrap-Look and -Feel.

Ich brauche eine einfache Verwaltungsseite, damt ich die watchlist.txt bearbeiten kann.
Wenn es ein neues Aktien-Symbol gibt, muss dafür sowohl eine neue Kolonne (Attribut) in der Tabelle "t_tagesschlusskurse" eingefügt werden damit dort die Kurse eingefügt werden können. Weiter soll für jede Währung (USD, EUR) ebenfalls in der Tabelle "t_tagesschlusskurse" eine Kolonne (Attribut) erstellt und bewirtschaftet werden für jeden Tag der Abfrage. Die Währungen sollen zum CHF gelten, also USD/CHF, EUR/CHF.

Benutze für die Abfragen die "Frankfurter.app".

Ich brauche weiter eine Verwaltungsseite, wo ich meine gekauften Aktien (Stocks) für den Kauf-Tag und auch für den Verkauf-Tag speichern kann. Die Datenhaltung soll in einer Tabelle t_myassets gemacht werden. Diese Tabelle muss mindestens die Attribute haben: (Tag in der Form YYYY-MM-DD date, Aktiensymbol varchar(8), KaufVerkauf (beim Kauf: +Zahl, beim Verkauf: -Zahl) float, Tageskurs float, Kaufwährung varchar(3) ).

Ich habe in einer Datei watchlist.txt eine Liste mit Aktien-Symbolen wie (UBSN, AAPL, TSLA, NESN usw.) aus verschiedenen Börsen. Ich brauche in PHP eine mit Gratisabfragen ohne API-Key für Tages-Schluss-Kurse dieser Aktien. Die Tagesschlusskurse sollen in die Datenbank "db_fin" in die Tabelle "t_tagesschlusskurse" eingefügt. Diese Tabelle hat ein Attribut "Tag" und auch für jede Aktie eine Kolonne/Spalte (Attribut). Falls an diesem Tag schon einen Eintrag in der Tabelle existieren sollte, dann muss man kein neuen Kurs im Internet abholen gehen, dann kann man den Wert aus der Tabelle nehmen.

Zur Darstellung auf der Seite "meine Assets", sollen alle Aktien aufgeführt werden, die einen positiven Wert der Käufe haben. Für jeden Aktienwert, will ich die Performanz für 1 Tag (gestern bis heute), für 3 Tage, für 7 Tage, für 30 Tage, für 90 Tage ausgewiesen werden sowie den absoluten Veränderungswert von gestern auf heute.
```

## Was die KI spezifikativ überarbeitet hat

```
/project-root
│── index.php              # Startseite / Dashboard
│── admin_watchlist.php    # Verwaltung watchlist.txt
│── admin_assets.php       # Verwaltung t_myassets
│── assets.php             # Anzeige "Meine Assets"
│── fetch_daily.php        # Script für Tages-Schlusskurse (Cronjob)
│── config.php             # DB-Config & Helper
│── lib/
│    ├── db.php            # PDO-Verbindung
│    ├── fx.php            # Frankfurter.app Abfragen
│    ├── stocks.php        # Yahoo Finance Abfragen
│    └── util.php          # Hilfsfunktionen
│── css/
│    └── style.css         # Custom CSS (Bootstrap override)
│── js/
│    └── charts.js         # Chart.js Integration
│── watchlist.txt          # Liste der Symbole
└── README.md              # Setup-Anleitung
```

### 🎨 Frontend (Bootstrap + Chart.js)

- admin_watchlist.php: CRUD für watchlist.txt, Sync mit DB-Spalten.
- admin_assets.php: Formular für Käufe/Verkäufe, Speicherung in t_myassets.
- assets.php: Tabelle mit Beständen + Performance (1/3/7/30/90 Tage), Sparklines via Chart.js..

### Qualitätskriterien und Betrieb

- Zeitzone: CET/CEST konsistent setzen; Tagesjob nach Börsenschluss (z. B. 18:30 CET).
- Idempotenz: Kein erneuter Netzabruf, wenn Wert für Tag vorhanden.
- Fehlerhandling: Zeitüberschreitungen, leere Antworten, NULL-Closes werden geloggt; UI zeigt Status/Retry.
- Validierung: Symbole gegen erlaubte Zeichen prüfen; Mapping für Börsen-Suffixe pflegen.
- Security: Prepared Statements, CSRF, Rate-Limits für Admin-Aktionen.
- Portabilität: Konfiguration in separater Datei; keine hartkodierten Pfade.
- Skalierung: Optional parallele Pflege der normalisierten Tabellen, um spätere Migration zu vereinfachen.

### Technische Details, Standards und Beispiel-Snippets

#### Technik-Stack

- Backend: PHP 8.x, PDO für MySQL, Prepared Statements.
- Frontend: Bootstrap 5.x, Chart.js für Sparklines, Vanilla JS/Fetch.
- Config: .env-ähnliche PHP-Datei für DB-Zugang, Zeitzone, Börsen-Mappings.
- Sicherheit: CSRF-Token für Admin-Forms, Input-Validierung, Rate-Limit für Abrufaktionen.

### Funktionen und Logik

#### Watchlist-Verwaltung

- Ziele: CRUD für watchlist.txt, Validierung der Symbole, Sync zur Tabelle t_tagesschlusskurse.
- Ablauf:
	- Einlesen: watchlist.txt zeilenweise (Trim, Großbuchstaben).
	- Validierung: Erkennen der Börse via Suffix-Konvention (.SW, .DE, .NS, .AS, etc.) oder Mapping-Tabelle; falls ohne Suffix, heuristisch ergänzen (z. B. UBSN → UBSG.SW).
	- Sync: Für neue Symbole Spalte in t_tagesschlusskurse anlegen (ALTER TABLE ADD COLUMN <SYMBOL> FLOAT NULL).
	- Löschen/Deaktivieren: Beim Entfernen aus watchlist.txt werden Spalten optional beibehalten (historische Integrität) oder in der UI „inaktiv“ markiert. Kein DROP ohne explizite Bestätigung.

#### Tagesschlusskurse abrufen und speichern

- Trigger: Manuell über Admin-UI oder Cron (z. B. täglich 18:30 CET).
- Stocks: Pro Symbol wird der letzte verfügbare Tages-Close über Yahoo Finance chart-Endpoint abgefragt.
	- Idempotenz: Vor Insert prüfen, ob für Tag bereits ein Wert existiert; wenn ja → aus DB lesen, nicht erneut abrufen.
	- Fehlertoleranz: Wenn heute kein Close verfügbar, verwende den letzten Handelstag.
- FX: Abfrage USD/CHF und EUR/CHF über Frankfurter.app (latest oder gezieltes Datum).
- Speichern:
	- Wenn Row für Tag nicht existiert: INSERT mit Tag, und setze initial alle neuen Werte.
	- Wenn Row existiert: UPDATE nur der betroffenen Spalten (Symbolspalte und FX-Spalten).
- Caching/Rate-Limits: Ergebnis pro Symbol/Tag lokal cachen (JSON-Datei oder Memory-Cache). Re-Use innerhalb eines Tages.

#### Assets-Seite und Performance-Berechnung

- Filter: Zeige alle Aktiensymbole mit positiver Summe KaufVerkauf (> 0).
- Aggregation:
	- Bestandsmenge: Summe KaufVerkauf je Symbol.
	- Einstandskurs/Betrag: Optional als gewichteter Durchschnitt, falls gewünscht (nicht verpflichtend in der Spezifikation).
- Performance-Fenster: 1, 3, 7, 30, 90 Tage.
	- Berechnung: Verwende Close-Werte aus t_tagesschlusskurse je Symbolspalte.
	- Absolute Veränderung: Close(heute) − Close(gestern).
	- Prozentuale Veränderung: (Close(heute) − Close(t0)) / Close(t0) × 100.
- Währungen: Wenn Kaufwährung ≠ CHF, konvertiere den Wert auf Basis der passenden FX-Spalte (USDCHF, EURCHF) zum jeweiligen Tag.

#### Seiten und Komponenten

- admin: Watchlist
	- Liste: Tabelle aller Symbole (aus watchlist.txt) mit Status.
	- Aktionen: Hinzufügen, Entfernen, Validieren, Synchronisieren mit DB.
	- Feedback: Log/Toasts für „Spalte hinzugefügt“, „Symbol ungültig“, „Already exists“.

- admin: Assets
	- Formular: Tag (Datepicker), Aktiensymbol (Select mit Watchlist), KaufVerkauf (Float), Tageskurs (Float), Kaufwährung (Select: CHF, USD, EUR).
	- Liste: Grid mit Einträgen, Summen pro Symbol, Bearbeiten/Löschen.
	- Import/Export: CSV/JSON-Export für t_myassets.

- kurse: Tagesschluss
	- Aktion: Button „Heute abrufen“ und „Beliebiges Datum abrufen“.
	- Status: Anzeige je Symbol, ob heute gespeichert ist; Retry für fehlgeschlagene Abrufe.
	- FX: Anzeige USD/CHF und EUR/CHF mit Zeitstempel.

- Meine Assets (Auswertung)
	- Tabelle/Karten: Pro Symbol: Bestand, Close heute, Close gestern, absolute Veränderung, Performance 1/3/7/30/90 Tage, optional Sparklines (Chart.js).
	- Filter: Zeitraum, nur positive Bestände.
	- Hinweis: Bei fehlenden historischen Daten wird die Zeile gekennzeichnet (z. B. „Daten lückenhaft“).
	
<hr>

## Tabellendefinition

```
CREATE TABLE `t_tagesschlusskurse` (
  `DATUM` date NOT NULL,
  `USDCHF` float NOT NULL,
  `EURCHF` float NOT NULL,
  `BABA` float DEFAULT NULL,
  `BIDU` float DEFAULT NULL,
  `CFR` float DEFAULT NULL,
  `GOOGL` float DEFAULT NULL,
  `KO` float DEFAULT NULL,
  `LLY` float DEFAULT NULL,
  `MSFT` float DEFAULT NULL,
  `NVDA` float DEFAULT NULL,
  `PYPL` float DEFAULT NULL,
  `TSLA` float DEFAULT NULL,
  `AAPL` float DEFAULT NULL,
  `AMZN` float DEFAULT NULL,
  `META` float DEFAULT NULL,
  `ORCL` float DEFAULT NULL,
  `LMT` float DEFAULT NULL,
  `OXY` float DEFAULT NULL,
  `PEP` float DEFAULT NULL,
  `XOM` float DEFAULT NULL,
  `NESN.SW` float DEFAULT NULL,
  `UBSN.SW` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `t_tagesschlusskurse`
--

INSERT INTO `t_tagesschlusskurse` (`DATUM`, `USDCHF`, `EURCHF`, `BABA`, `BIDU`, `CFR`, `GOOGL`, `KO`, `LLY`, `MSFT`, `NVDA`, `PYPL`, `TSLA`, `AAPL`, `AMZN`, `META`, `ORCL`, `LMT`, `OXY`, `PEP`, `XOM`, `NESN.SW`, `UNSN.SW`) VALUES
('2025-06-04', 0, 0, 119.45, 84.89, 126.53, 168.05, 71.37, 765.84, 463.87, 141.92, 72.8, 332.05, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('2025-06-05', 0, 0, 119.96, 85.85, 126.37, 168.21, 70.91, 765.68, 467.68, 139.99, 72.47, 284.7, 200.63, 207.91, 684.62, 171.14, NULL, NULL, NULL, NULL, NULL, NULL),
('2025-06-06', 0, 0, 119.38, 85.81, 129.22, 173.68, 71.35, 769.88, 470.38, 141.72, 73.43, 295.14, 203.92, 213.57, 697.71, 174.02, NULL, NULL, NULL, NULL, NULL, NULL);


--
-- Indizes für die Tabelle `t_tagesschlusskurse`
--
ALTER TABLE `t_tagesschlusskurse`
  ADD PRIMARY KEY (`DATUM`);
COMMIT;

```

```
CREATE TABLE `t_myassets` (
  `id` int(11) NOT NULL,
  `Datum` date NOT NULL,
  `Aktiensymbol` varchar(8) NOT NULL,
  `KaufVerkauf` float NOT NULL,
  `Tageskurs` float NOT NULL,
  `Kaufwaehrung` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Daten für Tabelle `t_myassets`
--

INSERT INTO `t_myassets` (`id`, `Datum`, `Aktiensymbol`, `KaufVerkauf`, `Tageskurs`, `Kaufwaehrung`) VALUES
(1, '2025-10-09', 'GOOGL', 2, 240.00, 'USD'),
(2, '2025-09-11', 'GOOGL', 2, 239.94, 'USD'),
(3, '2025-09-08', 'LLY', 1, 726.00, 'USD'),
(4, '2025-08-08', 'LLY', 1, 640.86, 'USD'),
(5, '2025-07-07', 'TSLA', 1, 291.33, 'USD'),
(6, '2025-10-30', 'TSLA', 2, 441.1, 'USD');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `t_myassets`
--
ALTER TABLE `t_myassets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_symbol_tag` (`Aktiensymbol`,`Tag`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `t_myassets`
--
ALTER TABLE `t_myassets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;
```
