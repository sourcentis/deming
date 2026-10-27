# Sicherheitsmaßnahmen

In diesem Bereich können neue Sicherheitsmaßnahmen definiert, geändert und geplant werden.

## Liste der Sicherheitsmaßnahmen <a name="list"></a>

Die Liste der Sicherheitsmaßnahmen zeigt alle Maßnahmen an, ermöglicht es, sie nach Domäne zu filtern oder eine Maßnahme anhand eines Teils ihres Namens zu suchen.
Mit den Schaltflächen „Spalten“, „Kopieren“ und „Exportieren“ können die angezeigten Spalten ausgewählt und die Liste kopiert oder nach Excel exportiert werden.

[![Screenshot](images/m1.de.png)](images/m1.de.png)

Wenn Sie klicken auf:

* die Domäne, gelangen Sie zur [Definition der gewählten Domäne](config.de.md#domains)

* die Klausel, gelangen Sie zur [Beschreibung der Sicherheitsmaßnahme](#show)

* die Anzahl der Kontrollen, gelangen Sie zur [Liste der Kontrollen](controls.de.md#list) dieser Sicherheitsmaßnahme

* die Schaltfläche „Planen“, gelangen Sie zur [Planung der Sicherheitsmaßnahme](#plan)

Mit der Schaltfläche „Neu“ können Sie [eine neue Sicherheitsmaßnahme erstellen](#create).

## Eine Sicherheitsmaßnahme anzeigen <a name="show"></a>

Diese Ansicht zeigt eine Sicherheitsmaßnahme an.

[![Screenshot](images/m2.de.png)](images/m2.de.png)

Eine Sicherheitsmaßnahme besteht aus:

* einer Domäne;

* einer Klausel;

* einem Namen;

* einem Sicherheitsziel;

* Attributen;

* den Messdaten;

* dem Prüfmodell;

* einem Indikator (grün, orange, rot); und

* einem Aktionsplan, der anzuwenden ist, wenn die Kontrolle dieser Sicherheitsmaßnahme fehlschlägt.

Die Ansicht listet außerdem die Kontrollen dieser Sicherheitsmaßnahme auf (Geltungsbereich, Planungsdatum,
Durchführungsdatum und Score) sowie die [Zuordnungen](crosswalk.de.md) dieser Maßnahme zu den Maßnahmen anderer
Rahmenwerke.

Wenn Sie klicken auf:

* „Planen“: Sie gelangen zur Ansicht [Planung der Sicherheitsmaßnahme](#plan).

* „Editieren“: Sie gelangen zur [Ansicht zum Ändern der Maßnahme](#edit)

* „Kopieren“: Sie gelangen zur Ansicht [Erstellen einer Sicherheitsmaßnahme](#create), vorausgefüllt mit den Daten dieser Maßnahme

* „Löschen“: Die Maßnahme wird gelöscht und Sie kehren zur [Liste der Sicherheitsmaßnahmen](#list) zurück

* „Abbruch“: Sie kehren zur [Liste der Sicherheitsmaßnahmen](#list) zurück

Ist der [KI-Agent](ai.de.md) aktiviert, öffnet das Symbol ✦ „KI-Analyse“ oben rechts unter der Hilfe-Schaltfläche
ein Fenster mit einer KI-Analyse der Sicherheitsmaßnahme. Siehe [Die Analyse verwenden](ai.de.md#use).

## Eine Sicherheitsmaßnahme erstellen {#create}

Mit dieser Ansicht kann einem Rahmenwerk eine Sicherheitsmaßnahme hinzugefügt werden. Sie ist über die Schaltfläche
„Neu“ der [Liste der Sicherheitsmaßnahmen](#list) oder über die Schaltfläche „Kopieren“ der Ansicht
[Anzeige einer Maßnahme](#show) erreichbar.

[![Screenshot](images/m4.de.png)](images/m4.de.png)

Die Ansicht enthält dieselben Felder wie die [Anzeige einer Sicherheitsmaßnahme](#show): Domäne, Klausel, Name,
Sicherheitsziel, Attribute, Messdaten, Prüfmodell, Indikator und Aktionsplan.

Wenn Sie klicken auf:

* „Sichern“: Die Sicherheitsmaßnahme wird erstellt und Sie kehren zur [Liste der Sicherheitsmaßnahmen](#list) zurück.

* „Abbruch“: Sie kehren zur [Liste der Sicherheitsmaßnahmen](#list) zurück.

Sicherheitsmaßnahmen können auch über die Ansicht [Import](config.de.md#import) aus einer Tabelle oder aus einem
Rahmenwerk des Repositorys importiert werden.


## Eine Sicherheitsmaßnahme ändern <a name="edit"></a>

Mit dieser Ansicht kann eine Sicherheitsmaßnahme geändert werden.

[![Screenshot](images/m3.de.png)](images/m3.de.png)

Wenn Sie klicken auf:

* „Sichern“: Die Änderungen werden gesichert und Sie kehren zur [Anzeige der Sicherheitsmaßnahme](#show) zurück.

* „Abbruch“: Sie kehren zur [Anzeige der Sicherheitsmaßnahme](#show) zurück.


## Eine Kontrolle planen <a name="plan"></a>

Mit dieser Ansicht kann ausgehend von einer Sicherheitsmaßnahme eine Sicherheitskontrolle geplant werden.

Die Ansicht enthält:

* eine oder mehrere Klauseln;

* den Namen der Maßnahme;

* das Sicherheitsziel;

* das Planungsdatum;

* den Geltungsbereich;

* die Periodizität der Kontrolle und

* die für die Durchführung der Kontrolle Verantwortlichen.

[![Screenshot](images/plan.de.png)](images/plan.de.png)

Wenn Sie klicken auf:

* „Planen“: Für den angegebenen Geltungsbereich wird eine neue Kontrolle erstellt, indem alle Daten der Maßnahme
  kopiert werden, und zum angegebenen Datum geplant. Sie kehren anschließend zur [Liste der Sicherheitsmaßnahmen](#list) zurück.

* „Abbruch“: Sie kehren zur [Liste der Sicherheitsmaßnahmen](#list) zurück.
