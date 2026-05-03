# Sicherheitsanalyse und Behebung von Schwachstellen

### TODO-Listen-Applikation (LB2)

## 1\. Einleitung

Im Rahmen dieser Arbeit wurde eine bestehende TODO-Listen-Applikation einer Sicherheitsanalyse unterzogen. Ziel war es, potenzielle Schwachstellen zu identifizieren und geeignete Massnahmen zu deren Behebung umzusetzen, um die Sicherheit der Anwendung nachhaltig zu verbessern.
Die Analyse orientiert sich an etablierten Sicherheitsstandards sowie bekannten Angriffsmustern aus der Webentwicklung (z. B. OWASP Top 10).

## 2\. Identifizierte Schwachstellen und Behebung

### 2.1 SQL Injection

**Beschreibung:**

In mehreren Dateien (z. B. login.js, savetask.js) wurden SQL-Statements durch String-Konkatenation erstellt, wodurch Benutzereingaben direkt in SQL-Abfragen eingeflossen sind.**

**Risiko:**

- Auslesen sensibler Daten
- Manipulation oder Löschung von Daten
- Umgehung der Authentifizierung

**Behebung:**

Umstellung auf parametrisierte SQL-Queries (Prepared Statements)

**Vorher:**

SELECT \* FROM users WHERE username='\` + username + \`'

**Nachher:**

SELECT \* FROM users WHERE username = ?

### 2.2 Unsichere Session-Verwaltung

**Beschreibung:**  
Die Authentifizierung basierte ursprünglich auf manipulierbaren Cookies (username, userid).

**Risiko:**

- Session Hijacking
- Identitätsdiebstahl durch Cookie-Manipulation

**Behebung:**

- Einführung von serverseitigen Sessions (express-session)
- Speicherung sensibler Daten in der Session statt im Client
- Anpassung von activeUserSession()

### 2.3 Unsichere Cookie-Konfiguration

**Beschreibung:**  
Cookies wurden ohne Sicherheitsattribute gesetzt.

**Risiko:**

- Zugriff durch XSS
- CSRF-Angriffe
- Unsichere Übertragung

**Behebung:** 

Setzen folgender Attribute:

- httpOnly
- sameSite: 'strict'
- secure (bei HTTPS)

### 2.4 Server-Side Request Forgery (SSRF)

**Beschreibung:**  
In search.js konnte der Provider frei gewählt werden.

**Risiko:**

- Zugriff auf interne Systeme
- Datenexfiltration
- Umgehung interner Sicherheitsmechanismen

**Behebung:**

- Einführung einer Whitelist erlaubter Endpunkte

const allowedProviders = \['/search/v2/'\];

### 2.5 Fehlende Zugriffskontrolle

**Beschreibung:**  
Einige Routen waren ohne Authentifizierung erreichbar.

**Risiko:**

- Unautorisierter Zugriff
- Missbrauch von Funktionen

**Behebung:**

- Zentrale Sessionprüfung (activeUserSession)
- Absicherung aller sensiblen Endpunkte

### 2.6 Unsichere Verarbeitung von Benutzereingaben

**Beschreibung:**    
Eingaben wurden ungeprüft verarbeitet.

**Risiko:**

- Injection-Angriffe
- Systemfehler

**Behebung:**

- Validierung mit isNaN, encodeURIComponent
- Typprüfung und Einschränkung von Eingaben

### 2.7 Unsichere Passwortverarbeitung

**Beschreibung:**  
Passwörter wurden im Klartext verglichen.

**Risiko:**

- Hohe Gefahr bei Datenbank-Leaks
- Keine sichere Authentifizierung

**Behebung:**

- Aktuell weiterhin Klartext (technisch bedingt)
- Problem wurde dokumentiert

**Empfehlung:**

- Einsatz von bcrypt für Passwort-Hashing

## 3\. Weitere Sicherheitsverbesserungen

- res.clearCookie() beim Logout
- Einschränkung von API-Zugriffen
- Reduktion von Debug-Ausgaben
- Vereinfachung von HTTP-Requests

## 4\. Fazit

Durch die durchgeführten Massnahmen konnten mehrere kritische Sicherheitslücken geschlossen werden. Besonders die Behebung von SQL-Injection sowie die Einführung serverseitiger Sessions stellen wesentliche Verbesserungen dar.

Die Anwendung erfüllt nun grundlegende Anforderungen an die Sicherheit moderner Webapplikationen deutlich besser.

## 5\. Ausblick

Für eine weiterführende Absicherung werden folgende Massnahmen empfohlen:

- **Passwort-Hashing mit bcrypt**
- **Implementierung von CSRF-Tokens**
- **Input-Sanitizing gegen XSS**
- **Logging und Monitoring**
- **Einsatz von Security-Headers (z. B. Helmet)**

## 6\. Quellen

- **OWASP Top 10**
- **Express.js Dokumentation**
- **Best Practices Web Security**

# Testprotokoll – ToDo Webapplikation
## Allgemeine Angaben

- Applikation: ToDo Liste (Node.js / Express)
- Testtyp: Manuelle Tests
- Testphase: Systemtest (Phase 2)
- Tester: Lara Klomp
- Datum: 03.05.2026

## Testfälle

| **ID** | **Bereich** | **Testfall** | **Schritte** | **Erwartet** | **Ergebnis** | **Status** | **Schwachstelle** |
| --- | --- | --- | --- | --- | --- | --- | --- |
| **TC-01** | **Login** | **Login gültig** | **korrekte Daten** | **Startseite** | **funktioniert** | **✅** | **\-** |
| **TC-02** | **Login** | **falsches Passwort** | **falsches PW** | **Fehlermeldung** | **OK** | **✅** | **\-** |
| **TC-03** | **Login** | **leere Eingaben** | **Felder leer** | **Validierung** | **keine klare Meldung** | **⚠️** | **fehlende Validierung** |
| **TC-04** | **Login** | **Login via URL** | **GET Request** | **blockiert** | **Login möglich** | **❌** | **Passwort in URL** |
| **TC-05** | **Login** | **SQL Injection** | **' OR 1=1 --** | **blockiert** | **blockiert** | **✅** | **\-** |
| **TC-06** | **Passwort** | **Klartext** | **Analyse** | **Hashing** | **Klartext** | **❌** | **kein Hashing** |
| **TC-07** | **Session** | **ohne Login** | **Seite öffnen** | **Redirect** | **OK** | **✅** | **\-** |
| **TC-08** | **Admin** | **Zugriff** | **/admin/users** | **verweigert** | **Zugriff möglich** | **❌** | **keine Rollenprüfung** |
| **TC-09** | **Logout** | **Logout** | **zurück** | **kein Zugriff** | **OK** | **✅** | **\-** |
| **TC-12** | **Tasks** | **fremde bearbeiten** | **ID ändern** | **blockiert** | **möglich** | **❌** | **kein User-Check** |
| **TC-14** | **Task** | **leere Eingabe** | **leer** | **Fehler** | **akzeptiert** | **⚠️** | **keine Validierung** |
| **TC-15** | **Search** | **UserID manipulieren** | **fremde ID** | **blockiert** | **möglich** | **❌** | **kein Schutz** |
| **TC-17** | **Security** | **XSS** | **&lt;script&gt;** | **blockiert** | **unklar** | **⚠️** | **möglich** |
| **TC-18** | **Cookies** | **Analyse** | **prüfen** | **secure cookie** | **false** | **⚠️** | **unsicher** |

# Phase 3 – Überarbeitung der Applikation

**1\. Login-Sicherheit**

- Umstellung von GET auf POST
- Passwörter nicht mehr in URL sichtbar

**2\. Passwort-Hashing**

- Einführung von bcrypt
- Speicherung nur noch gehashter Passwörter
- Vergleich via bcrypt.compare()

**3\. Rollenbasierte Zugriffskontrolle**

- Rollen (Admin/User) in Session gespeichert
- Zugriff auf Admin-Bereich eingeschränkt

**4\. Admin-Schutz**

- Route /admin/users abgesichert
- Rückgabe von 403 für normale User

**5\. Task-Sicherheit**

- Prüfung: Task gehört User

WHERE ID = ? AND userID = ?

**6\. Search-Schutz**

- Nutzung von req.session.userid
- Keine Client-Manipulation mehr möglich

**7\. Input-Validierung**

- Prüfung auf leere oder ungültige Werte
- Verhindert fehlerhafte Daten

**8\. Fehlerbehandlung**

- Try/Catch im Login
- Schutz vor Server-Crash

**9\. Session-Sicherheit**

- httpOnly
- sameSite: 'strict'
- Schutz vor Session-Angriffen

**10\. Datenbank-Anpassung**

- Korrekte JOINs (permissions, roles)
- Fehlerfreie SQL-Abfragen

## Fazit Phase 3

Die Applikation wurde umfassend überarbeitet. Kritische Sicherheitslücken wurden geschlossen, insbesondere in den Bereichen Authentifizierung, Zugriffskontrolle und Datenvalidierung. Dadurch ist die Anwendung deutlich stabiler und sicherer geworden.