# Konfiguration

Das Menü „Konfiguration“ bietet Zugriff auf die Attribute, die Domänen, die Benutzer, die Gruppen, den Import,
die [Zuordnungen zwischen Rahmenwerken](crosswalk.de.md), die [Einstellungen](#parameters) und die Logs.

## Attribute {#tags}

Mit dieser Ansicht werden die den Sicherheitsmaßnahmen zugeordneten Attribute verwaltet.
Sie enthält die Liste der Attribute und ermöglicht es, Attributlisten zu erstellen, zu löschen oder zu ändern.

[![Screenshot](images/tags.de.png)](images/tags.de.png)

Nur Administratoren verfügen über eine Schaltfläche „Verwalten“, die ein Formular öffnet, mit dem ein bestehender Attributwert global durch einen neuen Wert ersetzt werden kann. Die Ersetzung gilt überall dort, wo der Wert verwendet wird: in den Attributlisten, in den Sicherheitsmaßnahmen sowie in den noch nicht durchgeführten Kontrollen. Bereits durchgeführte Kontrollen werden nicht geändert, da sie eine Historie darstellen.

## Domänen {#domains}

Mit dieser Ansicht können Listen von Sicherheitsdomänen erstellt, geändert oder gelöscht werden.

[![Screenshot](images/domains.de.png)](images/domains.de.png)

Die Anwendung wird mit einer an die Norm ISO 27001:2022 angelehnten Basis von Sicherheitsmaßnahmen ausgeliefert, es ist aber möglich, neue Sicherheitsdomänen auf Grundlage anderer Normen wie PCI DSS, HDS usw. zu definieren.

## Benutzer {#users}

Die Benutzer werden direkt in der Anwendung definiert.

[![Screenshot](images/users.de.png)](images/users.de.png)

Ein Benutzer besteht aus einem Login, einem Namen, einem Titel, einer Rolle, einer E-Mail-Adresse, einer Sprache,
einem Passwort und der Liste der ihm zugewiesenen Kontrollen. Die Sprache wird für die Benutzeroberfläche, die
Benachrichtigungen und die erzeugten Dokumente verwendet.

Es gibt sechs verschiedene Rollen:

* Administrator: Der Administrator kann Benutzer, Maßnahmen und Attribute erstellen und bereits durchgeführte Kontrollen ändern.

* User: Benutzer können die Anwendung verwenden, ohne die Maßnahmen, die Attribute und die bereits durchgeführten Kontrollen ändern zu können.

* Auditierte: Auditierte können nur die Kontrollen durchführen und sehen, die ihnen zugewiesen sind oder die sie
  zuvor durchgeführt haben (siehe [Delegation](delegation.de.md)).

* Auditor: Der Auditor hat Lesezugriff auf alle Informationen der Anwendung. Der Auditor kann den [KI-Agenten](ai.de.md#access) nicht verwenden.

* API: Konto für den Zugriff auf die [API](api.de.md).

* Deaktiviert: Das Konto bleibt erhalten, damit seine Historie bewahrt wird, kann sich aber nicht mehr anmelden.

Die Schaltfläche „Historie“ der Benutzeranzeige zeigt die [Änderungshistorie](#logs) dieses Benutzers an.

## Gruppen {#groups}

Mit dieser Ansicht werden Benutzergruppen definiert.
Eine Gruppe fasst eine Reihe von Benutzern und Kontrollen zusammen.

[![Screenshot](images/groups.de.png)](images/groups.de.png)

Eine Gruppe besteht aus:

* einem Gruppennamen

* einem Typ: *Fachbereich* oder *Sicherheit* (nur durch einen Administrator änderbar)

* einer Beschreibung der Gruppe

* einer Liste von Benutzern

* einer Liste von Kontrollen

Der Gruppentyp unterscheidet in der Ansicht [Steuerung](dashboards.de.md#steering) die Kontrollen, die vom
Sicherheitsteam getragen werden, von denen der anderen Fachbereiche.

Im Formular werden nur die noch nicht durchgeführten Kontrollen angeboten; durchgeführte oder vorgeschlagene
Kontrollen bleiben beim Ändern der Gruppe mit ihr verknüpft.

## Reports {#reports}

Diese Ansicht steht Administratoren und Benutzern zur Verfügung. Mit ihr werden der ISMS-Steuerungsbericht, die
Erklärung der Anwendbarkeit und die Datenexporte erzeugt.

[![Screenshot](images/reports.de.png)](images/reports.de.png)

**ISMS-Steuerungsbericht**: Wählen Sie ein Rahmenwerk (optional) und einen Zeitraum und klicken Sie dann auf
„Erstellen“. Ein Word-Dokument wird auf Grundlage der [Vorlage des Steuerungsberichts](#documents) erzeugt.

Hier ist der ISMS-Steuerungsbericht:

[![Screenshot](images/report1.png){: style="width:600px"}](images/report1.png)

[![Screenshot](images/report2.png){: style="width:600px"}](images/report2.png)

**Erklärung der Anwendbarkeit (SoA)**: erzeugt eine Excel-Datei, die für jede Domäne und jede Klausel die
Sicherheitsmaßnahmen und das Planungsdatum ihrer Kontrollen für jeden Geltungsbereich auflistet.

**Daten exportieren**: exportiert die Benutzer, die Domänen, die Attribute, die Sicherheitsmaßnahmen, die Kontrollen,
die Aktionspläne und die Risikoliste in Excel-Dateien.

## Import {#import}

Sicherheitsmaßnahmen können aus einer .XLSX-Datei oder aus der Vorlagendatenbank importiert werden.

Beim Import können alle anderen Kontrollen und Maßnahmen gelöscht und Testdaten erzeugt werden.

[![Screenshot](images/import.de.png)](images/import.de.png)

## Einstellungen {#parameters}

Die Seite „Einstellungen“, die Administratoren vorbehalten ist, fasst die Einstellungen der Anwendung in Registerkarten
zusammen:

* **Dokumente**: die [Prüfung der Dokumente und ihre Aufbewahrungsdauer](#documents);

* **Vorlagen**: die [Dokumentvorlagen](#templates);

* **Klassifizierung**: die [Konfiguration der Risikobewertung](risks.de.md#scoring);

* **KI**: die Konfiguration des [KI-Agenten](#ai);

* **Benachrichtigungen**: die an die Benutzer gesendeten [Benachrichtigungen](#notifications).

Jede Registerkarte hat ihre eigenen Schaltflächen. Nach dem Speichern kehrt die Seite zur geänderten Registerkarte
zurück.

<!-- TODO capture : images/parameters.de.png -->

## Dokumente {#documents}

Die Registerkarte „Dokumente“ der Seite [Einstellungen](#parameters) dient zur Prüfung der in Deming gespeicherten
Dokumente und zur Konfiguration ihrer Aufbewahrungsdauer.

[![Screenshot](images/documents.de.png)](images/documents.de.png)

### Speicher

In diesem Teil der Registerkarte kann die Integrität der in der Anwendung gespeicherten Dokumente überprüft werden.

Durch Klicken auf „Prüfen“ startet Deming die Integritätsprüfung der in der Anwendung gespeicherten Dokumente mithilfe eines CRC32.

### Aufbewahrung

In diesem Teil der Registerkarte wird die Aufbewahrungsdauer (in Monaten) der in der Anwendung durchgeführten Kontrollen konfiguriert.

Alle nach Ablauf dieser Dauer durchgeführten Kontrollen werden zusammen mit den zugehörigen Dokumenten und Aktionsplänen gelöscht.

Diese Löschung erfolgt durch Ausführen des Befehls

    php artisan deming:cleanup

über einen Cron-Job.

## Vorlagen {#templates}

Mit der Registerkarte „Vorlagen“ der Seite [Einstellungen](#parameters) werden die Dokumentvorlagen für die
Kontrollblätter und den ISMS-Steuerungsbericht geändert.

Die Vorlage des Steuerungsberichts ist ein Word-Dokument (.docx), dessen Platzhalter bei der Erzeugung des Berichts
ersetzt werden. Das neben den Vorlagen angezeigte Feld „Platzhalter der Vorlage“ listet die verfügbaren Platzhalter
auf; ein Klick auf einen Platzhalter kopiert ihn in die Zwischenablage.

* Variablen: `${today}`, `${start_date}`, `${end_date}`, `${framework}` sowie die vordefinierten Tabellen
  `${control_table}`, `${kpi_table}`, `${made_control_table}` und `${action_plans_table}`.

* Benutzerdefinierte Tabellen: `${table:<tabelle>:<spalte>[=Überschrift],...[|option]}`, wobei `<tabelle>` eine der
  Tabellen `domains`, `controls`, `measures`, `actions`, `risks`, `exceptions`, `attributes`, `users` oder
  `user_groups` ist. Der Platzhalter muss allein in seinem Absatz stehen. Die verfügbaren Optionen sind
  `period:<spalte>` (Zeilen innerhalb des Berichtszeitraums), `where:<spalte>=<wert>` (Filter, `!=` und `null` sind
  zulässig), `order:[-]<spalte>` (Sortierung, `-` für absteigende Reihenfolge) und `limit:<n>`.

Beispiel:

    ${table:actions:reference,name,owners,due_date|where:status=0|order:due_date}

## KI {#ai}

Die Registerkarte „KI“ der Seite [Einstellungen](#parameters), die Administratoren vorbehalten ist, dient zum Aktivieren und Konfigurieren des [KI-Agenten](ai.de.md),
der Sicherheitsmaßnahmen, Kontrollen, Aktionspläne und Risiken analysiert: Aktivierung, URL des Agenten, Modell,
API-Schlüssel, Prompts, Verbindungstest und Löschen aller Analysen. Die KI ist standardmäßig deaktiviert.

Siehe [Aktivieren und konfigurieren](ai.de.md#config) und vor jeder Aktivierung [Datenschutz](ai.de.md#privacy).

## Benachrichtigungen {#notifications}

Mit der Registerkarte „Benachrichtigungen“ der Seite [Einstellungen](#parameters) werden die Benachrichtigungen konfiguriert, die an die Benutzer gesendet werden, wenn sie Kontrollen durchführen müssen.

Die Ansicht enthält:

* den Betreff der an den Benutzer gesendeten E-Mail;

* den Absender der E-Mail;

* die Periodizität des Versands der Benachrichtigungen;

* die Benachrichtigungsfristen;

* den SMTP-Server für den Versand: Server, Port, Verschlüsselung (keine, SSL oder TLS), automatisches STARTTLS,
  Authentifizierung, Benutzer und Passwort sowie die optionale DKIM-Signatur (Domäne, Selektor, Pfad des privaten
  Schlüssels und Passphrase).

Die Einstellungen des SMTP-Servers werden in der Datenbank gespeichert. Das Passwort und die DKIM-Passphrase werden mit
dem Anwendungsschlüssel (`APP_KEY`) verschlüsselt und nie wieder angezeigt: Ein leeres Feld behält den gespeicherten
Wert, das Kästchen „Löschen“ entfernt ihn. Bei der Aktualisierung von Deming werden die `MAIL_*`-Werte der Datei `.env`
einmalig in die Datenbank übernommen; danach werden sie nicht mehr verwendet.

[![Screenshot](images/config.de.png)](images/config.de.png)

Wenn Sie klicken auf:

* „Sichern“ – die Konfiguration wird gespeichert;

* „Testen“ – eine Test-E-Mail wird mit den eingegebenen Werten (ohne sie zu speichern) an den aktuellen Benutzer gesendet;

* „Abbruch“ – Sie kehren zur Hauptseite zurück.

## Logs {#logs}

Diese Ansicht, die über das Menü „Konfiguration“ erreichbar ist, listet – vom neuesten zum ältesten – alle Änderungen
an den Sicherheitsmaßnahmen, den Kontrollen, den Aktionsplänen, den Risiken und den Benutzern auf: Aktion (Erstellung,
Änderung, Löschung), Typ und Kennung des Objekts, Benutzer, IP-Adresse und Datum.

Wenn Sie klicken auf:

* die Kennung eines Eintrags oder die Schaltfläche „Anzeigen“, wird das Detail der Änderung mit allen Daten des
  Objekts zu diesem Zeitpunkt angezeigt;

* die Kennung eines Objekts, wird dieses Objekt angezeigt;

* die Schaltfläche „Historie“, wird die Änderungshistorie dieses Objekts angezeigt.

Die Änderungshistorie eines Objekts ist auch über die Schaltfläche „Historie“ der Ansichten einer
[Kontrolle](controls.de.md#show), eines [Risikos](risks.de.md#show) oder eines [Benutzers](#users) erreichbar.

Logs, die älter als die [Aufbewahrungsdauer](#documents) sind, werden durch den Befehl `deming:cleanup` gelöscht.
