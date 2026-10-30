# Kontrollen

## Liste der Kontrollen {#list}

Diese Ansicht zeigt die Liste der Kontrollen an und ermöglicht es, sie zu filtern nach:

* Domäne,

* Klausel,

* Geltungsbereich,

* Planungszeitraum,

* Status der Kontrolle: alle, erledigt und zu erledigen.

Mit der Schaltfläche „Spalten“ können die angezeigten Spalten ausgewählt werden, und mit den Schaltflächen „Kopieren“
und „Exportieren“ kann die angezeigte Liste kopiert oder nach Excel exportiert werden.

[![Screenshot](images/c1.de.png)](images/c1.de.png)

Wenn Sie klicken auf:

* die Domäne, gelangen Sie zur [Anzeige der Sicherheitsdomäne](config.de.md#domains)

* die Klausel, gelangen Sie zur [Anzeige der Sicherheitsmaßnahme](measures.de.md#show)

* das Durchführungsdatum, das Planungsdatum oder das Datum der nächsten Kontrolle, gelangen Sie zur [Anzeige der Kontrolle](#show)

## Eine Kontrolle anzeigen {#show}

Diese Ansicht enthält die Informationen einer Kontrolle:

* den Namen der Kontrolle;

* das Ziel der Kontrolle;

* die Attribute;

* die Daten;

* das Modell;

* das Planungsdatum, das Durchführungsdatum und das Datum der nächsten Kontrolle;

* die der Kontrolle zugewiesene Note; und

* den der Kontrolle zugewiesenen Score (grün, orange oder rot).

Die Schaltflächen „Erledigen“ und „Planen“ sind vorhanden, wenn diese Kontrolle noch nicht durchgeführt wurde.

Die Schaltflächen „Editieren“, „Kopieren“, „Löschen“ und „Historie“ sind vorhanden, wenn der Benutzer Administrator ist.

[![Screenshot](images/c2.de.png)](images/c2.de.png)

Wenn Sie klicken auf:

* „Erledigen“, gelangen Sie zur [Ansicht zur Durchführung einer Kontrolle](#make)

* „Planen“, gelangen Sie zur [Ansicht zur Planung einer Kontrolle](#plan)

* „Editieren“, gelangen Sie zur [Ansicht zum Ändern der Kontrolle](#edit)

* „Kopieren“, gelangen Sie zur [Ansicht zum Erstellen einer Kontrolle](#create), vorausgefüllt mit den Daten dieser Kontrolle

* „Löschen“, wird die Kontrolle gelöscht und Sie gelangen zur [Liste der Kontrollen](#list)

* „Historie“, gelangen Sie zur [Änderungshistorie](config.de.md#logs) dieser Kontrolle

* „Abbruch“, gelangen Sie zur [Liste der Kontrollen](#list)

Ist der [KI-Agent](ai.de.md) aktiviert, öffnet das Roboter-Symbol „KI-Analyse“ oben rechts unter der Hilfe-Schaltfläche
ein Fenster mit einer KI-Analyse der Kontrolle. Siehe [Die Analyse verwenden](ai.de.md#use).

## Eine Kontrolle erstellen {#create}

Eine Kontrolle kann auf drei Arten erstellt werden:

* Ausgehend von einer Sicherheitsmaßnahme: Die Schaltfläche „Planen“ der [Anzeige der Maßnahme](measures.de.md#plan)
  erstellt eine neue Kontrolle, die mit den Daten der Maßnahme vorausgefüllt ist.

* Durch Kopieren einer Kontrolle: Die Schaltfläche „Kopieren“ der [Anzeige der Kontrolle](#show) öffnet das
  Erstellungsformular, vorausgefüllt mit den Daten dieser Kontrolle.

* Manuell: In der [Liste der Kontrollen](#list) öffnet die Schaltfläche „Neu“ das Erstellungsformular.

[![Screenshot](images/c4.de.png)](images/c4.de.png)

Diese Ansicht enthält die folgenden Felder:

* die zugehörige(n) Klausel(n);

* den Namen der Kontrolle;

* den Geltungsbereich;

* das Ziel der Kontrolle;

* die Attribute;

* die zu erhebenden Daten;

* das Berechnungsmodell des Scores;

* den Aktionsplan, der anzuwenden ist, wenn die Kontrolle fehlschlägt;

* das Planungsdatum und die Periodizität und

* die für die Durchführung der Kontrolle Verantwortlichen.

Wenn Sie klicken auf:

* „Sichern“, wird die Kontrolle gespeichert und Sie gelangen zur [Liste der Kontrollen](#list)

* „Abbruch“, gelangen Sie zur [Liste der Kontrollen](#list)

## Eine Kontrolle ändern {#edit}

Mit dieser Ansicht können die Informationen einer bestehenden Kontrolle geändert werden.

Sie ist nur für Benutzer mit der Rolle **Administrator** zugänglich.

Zusätzlich zu den Feldern der [Erstellungsansicht](#create) enthält sie das Ergebnis der Kontrolle: Durchführungsdatum,
Beobachtungen, Nachweise, Note, Score und Indikator.

Wenn Sie klicken auf:

* „Sichern“, werden die Änderungen gespeichert und Sie gelangen zur [Anzeige der Kontrolle](#show)

* „Abbruch“, gelangen Sie zur [Anzeige der Kontrolle](#show)

## Eine Kontrolle planen {#plan}

Mit dieser Ansicht kann eine Kontrolle geplant werden.

Diese Ansicht enthält die Informationen einer Kontrolle:

* den Namen der Kontrolle;

* das Ziel der Kontrolle;

* das Planungsdatum;

* die Periodizität; und

* die für die Durchführung der Kontrolle Verantwortlichen.

[![Screenshot](images/c5.de.png)](images/c5.de.png)

Wenn Sie klicken auf:

* „Planen“, werden das Planungsdatum, die Wiederholung und die Verantwortlichen aktualisiert und Sie gelangen zur [Anzeige der Kontrolle](#show)

* „Un-Planen“, wird die Kontrolle entfernt und Sie gelangen zur [Liste der Kontrollen](#list)

* „Abbruch“, gelangen Sie zur [Anzeige der Kontrolle](#show)


## Eine Kontrolle durchführen {#make}

Mit dieser Ansicht kann eine Sicherheitskontrolle durchgeführt werden.

Diese Ansicht enthält:

* die Klausel(n),

* den Namen der Kontrolle,

* das Ziel,

* die Eingabedaten,

* das Durchführungsdatum und das Planungsdatum,

* die Beobachtungen der Kontrolle,

* einen Bereich zum Speichern der Nachweise (mit **STRG+V** kann eine Datei oder ein Bildschirmfoto eingefügt werden),

* einen Link zum Herunterladen des Kontrollblatts,

* das angewandte Berechnungsmodell,

* die Note,

* den Score,

* den Aktionsplan (die Option „Aktionsplan erstellen“ erstellt einen [Aktionsplan](actions.de.md)),

* das Datum der nächsten Kontrolle.

[![Screenshot](images/c3.de.png)](images/c3.de.png)

Wenn Sie klicken auf:

* „Erledigen“, wird die Kontrolle gespeichert und eine neue Kontrolle zum geplanten Datum erstellt

* „Sichern“, wird die Kontrolle gespeichert

und Sie kehren zur [Liste der Kontrollen](#list) zurück.


## Kontrollblatt {#sheet}

Das Kontrollblatt ist ein Word-Dokument, das von der Anwendung auf Grundlage der Daten der Kontrolle erzeugt wird.

Mit diesem Blatt können die gemachten Beobachtungen beschrieben, Bildschirmfotos oder Tabellen hinzugefügt und die Beobachtungen __elektronisch signiert__ werden.

[![Screenshot](images/report3.png)](images/report3.png)

Das Kontrollblatt kann an die Dokumentvorlage Ihrer Organisation [angepasst](config.de.md#documents) werden.
