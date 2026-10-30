# Aktionspläne

Jedes Mal, wenn eine Kontrolle fehlschlägt (orange oder rot), muss ein Aktionsplan eingeführt und verfolgt werden, um die Situation zu beheben.

## Liste der Aktionspläne <a name="list"></a>

Mit dieser Ansicht können die Aktionspläne verfolgt werden:

[![Screenshot](images/a1.de.png)](images/a1.de.png)

Die Liste kann nach Typ (Major, Minor, Beobachtung, Gelegenheit), nach Status (offen, geschlossen, alle) und nach
Geltungsbereich gefiltert werden. Sie zeigt für jeden Aktionsplan seine Referenz, seinen Typ, die zugehörigen Risiken,
seinen Status, seinen Fortschritt, seinen Namen, seinen Geltungsbereich, seine Verantwortlichen und sein Fälligkeitsdatum.
Überschrittene Fälligkeitsdaten werden rot angezeigt. Durch Klicken auf ein Risiko wird es geöffnet.

Mit der Schaltfläche „Spalten“ können zusätzliche Spalten (zugehörige Klauseln, Behebung) angezeigt werden,
und mit den Schaltflächen „Kopieren“ und „Exportieren“ kann die angezeigte Liste kopiert oder nach Excel exportiert
werden.

Durch Klicken auf die Referenz eines Aktionsplans [öffnen Sie ihn](#show). Mit der Schaltfläche „Neu“ können Sie
[einen neuen Aktionsplan erstellen](#create).

Ein Aktionsplan kann auch bei der [Durchführung einer Kontrolle](controls.de.md#make) erstellt werden, indem die
Option „Aktionsplan erstellen“ angekreuzt wird.

## Einen Aktionsplan erstellen {#create}

Mit dieser Ansicht kann ein Aktionsplan manuell erstellt werden.

[![Screenshot](images/a3.de.png)](images/a3.de.png)

Die Ansicht enthält:

* die Referenz des Aktionsplans;

* seinen Typ: Major, Minor, Beobachtung oder Gelegenheit;

* seinen Namen;

* den Geltungsbereich und die zugehörigen Klauseln;

* die zugehörigen Risiken;

* die Ursache;

* die durchzuführende Behebung;

* das Fälligkeitsdatum und

* die Verantwortlichen des Aktionsplans.

Wenn Sie klicken auf:

* „Sichern“, wird der Aktionsplan erstellt und Sie kehren zur [Liste der Aktionspläne](#list) zurück

* „Abbruch“, kehren Sie zur [Liste der Aktionspläne](#list) zurück

## Einen Aktionsplan anzeigen {#show}

Diese Ansicht zeigt einen Aktionsplan an: Referenz, Typ, Status, Geltungsbereich, Klauseln, Risiken, Name, Ursache,
Fälligkeitsdatum, Verantwortliche und, bei einem geschlossenen Aktionsplan, sein Abschlussdatum und seine
Begründung. Die Klauseln und Risiken werden nur angezeigt, wenn der Aktionsplan damit verknüpft ist. Die Klauseln,
die Risiken und die Kontrolle, aus der der Aktionsplan hervorgeht, sind von dieser Ansicht aus erreichbar.

[![Screenshot](images/a4.de.png)](images/a4.de.png)

Solange der Aktionsplan offen ist, können die Behebung und der Fortschritt (Schieberegler) direkt aktualisiert werden.

Wenn Sie klicken auf:

* „Sichern“, werden die Behebung und der Fortschritt gespeichert und Sie kehren zur [Liste der Aktionspläne](#list) zurück

* „Schließen“, gelangen Sie zur Ansicht [Abschluss des Aktionsplans](#close) (nur bei offenen Aktionsplänen)

* „Editieren“, gelangen Sie zur Ansicht [Ändern des Aktionsplans](#edit)

* „Löschen“, wird der Aktionsplan nach Bestätigung gelöscht

* „Abbruch“, kehren Sie zur [Liste der Aktionspläne](#list) zurück

Ist der [KI-Agent](ai.de.md) aktiviert, öffnet das Roboter-Symbol „KI-Analyse“ oben rechts unter der Hilfe-Schaltfläche
ein Fenster mit einer KI-Analyse des Aktionsplans. Siehe [Die Analyse verwenden](ai.de.md#use).

## Einen Aktionsplan schließen {#close}

Mit dieser Ansicht kann ein Aktionsplan abgeschlossen werden, sobald die Behebung durchgeführt wurde, oder er kann
abgelehnt werden.

Die Ansicht zeigt die Informationen des Aktionsplans an und enthält:

* den neuen Status: offen, geschlossen oder abgelehnt;

* das Abschlussdatum und

* die Begründung des Abschlusses.

Wenn Sie klicken auf:

* „Sichern“, werden Status, Abschlussdatum und Begründung gespeichert und Sie kehren zur [Liste der Aktionspläne](#list) zurück

* „Abbruch“, kehren Sie zum [Aktionsplan](#show) zurück

## Einen Aktionsplan ändern <a name="edit"></a>

Mit dieser Ansicht können alle Informationen eines Aktionsplans geändert werden: Referenz, Typ, Name,
Geltungsbereich, Klauseln, Risiken, Ursache, Behebung, Fälligkeitsdatum, Verantwortliche, Fortschritt, Status, Abschlussdatum
und Begründung.

[![Screenshot](images/a2.de.png)](images/a2.de.png)

Wenn Sie klicken auf:

* „Sichern“, wird der Aktionsplan gespeichert und Sie kehren zur [Liste der Aktionspläne](#list) zurück

* „Abbruch“, kehren Sie zur [Liste der Aktionspläne](#list) zurück
