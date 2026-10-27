# Risikoliste

Mit der Risikoliste werden die Informationssicherheitsrisiken gemäß den Anforderungen der Norm ISO 27001:2022 verwaltet, insbesondere:

- **§ 6.1.2** — Prozess zur Beurteilung von Informationssicherheitsrisiken
- **§ 6.1.3** — Prozess zur Behandlung von Informationssicherheitsrisiken
- **§ 8.2** — Beurteilung von Informationssicherheitsrisiken

Jedes Risiko wird nach einer konfigurierbaren Bewertungsmethode bewertet, mit den bestehenden [Sicherheitsmaßnahmen](measures.de.md) und [Aktionsplänen](actions.de.md) der Anwendung verknüpft und einem periodischen Überprüfungszyklus unterzogen.

## Risikomatrix {#matrix}

Diese Ansicht zeigt eine zusammenfassende Darstellung aller Risiken in Form einer Matrix.

[![Screenshot](images/risk.matrix.de.png)](images/risk.matrix.de.png)


Die Matrix kreuzt die Achsen Wahrscheinlichkeit (oder Eintrittswahrscheinlichkeit) und Auswirkung. Jede Zelle ist entsprechend der zugehörigen Risikostufe eingefärbt und zeigt die Anzahl der in dieser Zelle positionierten Risiken. Durch Klicken auf eine nicht leere Zelle gelangen Sie zur [Liste der Risiken](#list), gefiltert auf diesen Score.

> Die Achsen der Matrix und die Farben der Zellen passen sich automatisch an die [aktive Bewertungsmethode](#scoring) an.

Rechts neben der Matrix zeigt eine Tabelle die **Anzahl der Risiken pro Stufe** und die **Verteilung der Risiken nach Behandlungsstatus**, jeweils mit einem direkten Link zur Liste der Risiken, gefiltert auf das gewählte Kriterium.

## Liste der Risiken {#list}

Diese Ansicht zeigt alle erfassten Risiken.

[![Screenshot](images/risk.list.de.png)](images/risk.list.de.png)

Die Liste kann gefiltert werden nach:

- **Behandlungsstatus** — Nicht bewertet, Nicht akzeptiert, Akzeptiert, Gemindert usw.
- **Verantwortlicher** — die für die Überprüfung des Risikos verantwortliche Person.
- **Überprüfung überfällig** — zeigt nur die Risiken an, deren nächstes Überprüfungsdatum überschritten ist.

> Benutzer mit der Rolle **Auditierte** sehen nur die Risiken, für die sie verantwortlich sind.

Für jedes Risiko zeigt die Liste:

- den Namen des Risikos;
- den Verantwortlichen;
- die Wahrscheinlichkeit und die Auswirkung;
- den **berechneten Score** mit einem farbigen Abzeichen entsprechend der Risikostufe (Gering, Mittel, Hoch, Kritisch);
- den Behandlungsstatus;
- das nächste Überprüfungsdatum, rot bei Überschreitung.

Mit der Schaltfläche **Neu** oben rechts kann ein neues Risiko erstellt werden.

## Ein Risiko anzeigen {#show}

Diese Ansicht zeigt die Details eines Risikos.

[![Screenshot](images/risk.show.de.png)](images/risk.show.de.png)

Sie enthält:

- den **Namen** und die **Beschreibung** des Risikos;
- den **Verantwortlichen** und die Überprüfungshäufigkeit mit dem nächsten Überprüfungsdatum;
- die Bewertung:
    - die **Wahrscheinlichkeit** mit ihrer Bezeichnung und ihrem Kommentar (Standardformeln);
    - die **Exposition** und die **Verwundbarkeit** mit der berechneten Eintrittswahrscheinlichkeit (Formel BSI 200-3);
    - die **Auswirkung** mit ihrer Bezeichnung und ihrem Kommentar;
    - den berechneten **Score** und die **Risikostufe**;
- die Behandlung:
    - den **Behandlungsstatus** mit seinem Kommentar;
    - die **verknüpften Maßnahmen** (bei Status = Gemindert);
    - die **verknüpften Aktionspläne** (bei Status = Nicht akzeptiert);

Die verfügbaren Schaltflächen hängen von der Rolle des Benutzers ab:

- **Editieren** — öffnet die [Ansicht zum Ändern](#edit) (Administrator und User);
- **Löschen** — löscht das Risiko nach Bestätigung (nur Administrator);
- **Historie** — zeigt die [Änderungshistorie](config.de.md#logs) an (nur Administrator);
- **Abbruch** — kehrt zur [Liste der Risiken](#list) zurück.

Ist der [KI-Agent](ai.de.md) aktiviert, öffnet das Roboter-Symbol „KI-Analyse“ oben rechts unter der Hilfe-Schaltfläche
ein Fenster mit einer KI-Analyse des Risikos. Siehe [Die Analyse verwenden](ai.de.md#use).

## Ein Risiko erstellen {#create}

Mit dieser Ansicht wird ein neues Risiko erstellt.

[![Screenshot](images/risk.edit.de.png)](images/risk.edit.de.png)

Sie enthält die folgenden Felder:

- **Name** *(Pflichtfeld)* — kurze, eindeutige Bezeichnung des Risikos;
- **Beschreibung** — ausführliche Beschreibung des Risikos;
- **Verantwortlicher** — Benutzer, der für die periodische Überprüfung des Risikos verantwortlich ist;
- **Überprüfungshäufigkeit** — Intervall in Monaten zwischen zwei Überprüfungen. Das nächste Überprüfungsdatum wird automatisch berechnet, wenn es nicht manuell eingegeben wird.

Die Bewertung des Risikos hängt von der [aktiven Bewertungsmethode](#scoring) ab:

- Für die Standardformeln (Wahrscheinlichkeit × Auswirkung, Wahrscheinlichkeit + Auswirkung, max):
    - **Wahrscheinlichkeit** — Stufe von 1 bis N mit Bezeichnung und Beschreibung;
    - **Kommentar Wahrscheinlichkeit**;
- Für die Formel BSI 200-3 (Eintrittswahrscheinlichkeit × Auswirkung):
    - **Exposition** — Erreichbarkeit des Systems (z. B. 0 = offline, 1 = intern, 2 = Internet);
    - **Verwundbarkeit** — Grad der Ausnutzbarkeit bekannter Schwachstellen;
- Für die Formel MONARC (Auswirkung × Bedrohung × Verwundbarkeit):
    - **Bedrohung** — Wahrscheinlichkeit der Bedrohung (0 = nicht anwendbar bis 4 = sehr wahrscheinlich);
    - **Verwundbarkeit** — Niveau der vorhandenen Sicherheitsmaßnahmen (0 = nicht vorhanden bis 5 = sehr hoch);
- **Auswirkung** — Schwere der Folgen, wenn das Risiko eintritt;
- **Kommentar Auswirkung**;
- **Berechneter Score** — wird in Echtzeit anhand der eingegebenen Werte aktualisiert, mit einem farbigen Abzeichen, das die Stufe anzeigt;
- **Restrisiko** — nur bei der Formel MONARC: Score des nach der Behandlung verbleibenden Risikos, manuell eingegeben
  und in der Liste sowie in der Anzeige des Risikos mit der Farbe seiner Stufe dargestellt.

Die Behandlung des Risikos wird konfiguriert über:

- **Status** — einer von: Nicht bewertet, Nicht akzeptiert, Vorläufig akzeptiert, Akzeptiert, Gemindert, Übertragen, Vermieden.
  Bei der Formel MONARC sind die Behandlungen: Nicht behandelt, Reduzierung, Abgelehnt, Akzeptiert, Geteilt;
- **Kommentar Status**;
- **Verknüpfte Maßnahmen** — Mehrfachauswahl aus den bestehenden Sicherheitsmaßnahmen (nur angezeigt bei Status = *Gemindert*);
- **Verknüpfte Aktionspläne** — Mehrfachauswahl aus den bestehenden Aktionsplänen (nur angezeigt bei Status = *Nicht akzeptiert* oder *Reduzierung* bei der Formel MONARC).

> Eine Warnung wird angezeigt, wenn ein Risiko mit Status *Gemindert* ohne verknüpfte Maßnahme oder ein Risiko mit Status *Nicht akzeptiert* ohne verknüpften Aktionsplan gesichert wird.

Wenn Sie klicken auf:

- **Sichern** — das Risiko wird erstellt und Sie gelangen zur [Anzeige des Risikos](#show);
- **Abbruch** — Sie kehren zur [Liste der Risiken](#list) zurück.

## Ein Risiko ändern {#edit}

Mit dieser Ansicht wird ein bestehendes Risiko geändert. Sie enthält dieselben Felder wie die [Erstellungsansicht](#create) und zusätzlich:

- das manuell änderbare **nächste Überprüfungsdatum**;
- den vorausgefüllten aktuellen Score, der bei jeder Änderung dynamisch neu berechnet wird.

Wird die Überprüfungshäufigkeit geändert und kein nächstes Überprüfungsdatum eingegeben, wird dieses automatisch ab dem heutigen Datum neu berechnet.

Wenn Sie klicken auf:

- **Sichern** — das Risiko wird aktualisiert und Sie gelangen zur [Anzeige des Risikos](#show);
- **Abbruch** — Sie kehren ohne Änderung zur [Anzeige des Risikos](#show) zurück.

## Konfiguration der Bewertung {#scoring}

Die Risikobewertung ist vollständig konfigurierbar. Es können mehrere Konfigurationen definiert werden, aber nur eine ist jeweils aktiv. Ein Wechsel der aktiven Konfiguration gilt sofort für die gesamte Risikoliste.

### Liste der Konfigurationen {#scoring-list}

Diese Ansicht zeigt alle in der Anwendung definierten Bewertungskonfigurationen.

[![Screenshot](images/risk.scoring.de.png)](images/risk.scoring.de.png)

Für jede Konfiguration zeigt die Liste:

- eine Kennzeichnung der **aktiven** Konfiguration;
- den **Namen** der Konfiguration;
- die verwendete **Formel**;
- die Details der konfigurierten **Stufen** (Wahrscheinlichkeit oder Exposition/Verwundbarkeit, Auswirkung);
- die **Klassifizierungsschwellenwerte** als farbige Abzeichen mit ihrem Scorebereich als Tooltip.

Die für jede Zeile verfügbaren Aktionsschaltflächen sind:

- **Editieren** (Stift) — öffnet die [Ansicht zum Ändern der Konfiguration](#scoring-edit);
- **Aktivieren** (Haken) — aktiviert diese Konfiguration nach Bestätigung. Die bisher aktive Konfiguration wird automatisch deaktiviert. Die Schaltfläche fehlt, wenn die Konfiguration bereits aktiv ist;
- **Löschen** (Flamme) — löscht die Konfiguration nach Bestätigung. Die aktive Konfiguration kann nicht gelöscht werden.

Mit der Schaltfläche **Neu** oben rechts kann eine neue Konfiguration erstellt werden.

### Eine Konfiguration erstellen oder ändern {#scoring-edit}

Mit dieser Ansicht wird eine vollständige Bewertungsmethode definiert.

[![Screenshot](images/risk.scoring.create.de.png)](images/risk.scoring.create.de.png)

#### Name und Formel

- **Konfigurationsname** — Bezeichnung der Konfiguration, die in der Liste angezeigt wird;
- **Berechnungsformel** — die Methode zur Berechnung des Scores. Fünf Formeln stehen zur Verfügung:

| Formel | Berechnung | Empfohlene Verwendung |
|---|---|---|
| Wahrscheinlichkeit × Auswirkung | Score = W × A | Klassisch ISO 27005 / ISO 27001 |
| Wahrscheinlichkeit × Auswirkung (BSI 200-3) | Score = (Exposition + Verwundbarkeit) × A | BSI 200-3 / ISACA CSC-IT |
| Wahrscheinlichkeit + Auswirkung | Score = W + A | Schnelle Triage |
| max(Wahrscheinlichkeit, Auswirkung) | Score = max(W, A) | Konservativer Ansatz |
| MONARC | Score = Auswirkung × Bedrohung × Verwundbarkeit | Methode MONARC (max. 4 × 4 × 5 = 80) |

> Bei Auswahl der Formel *BSI 200-3* werden anstelle des Abschnitts **Wahrscheinlichkeit** die Abschnitte **Exposition** und **Verwundbarkeit** angezeigt. Die Formel *MONARC* zeigt die Abschnitte **Bedrohung** und **Verwundbarkeit** an.

#### Stufen

Jede Bewertungsachse verfügt über eine Tabelle anpassbarer Stufen. Jede Stufe wird definiert durch:

- **Wert** — eindeutige ganze Zahl, die in die Berechnung des Scores einfließt;
- **Label** — Kurzbezeichnung, die im Risikoformular angezeigt wird;
- **Beschreibung** — erläuternder Text, der die Benutzer bei der Bewertung unterstützt.

Mit der Schaltfläche **Level hinzufügen** wird eine zusätzliche Zeile eingefügt. Die Schaltfläche zum Löschen (Papierkorb) entfernt die Zeile. Pro Achse sind mindestens zwei Stufen erforderlich.

Die konfigurierbaren Achsen sind:

- **Wahrscheinlichkeit** — bei den Standardformeln vorhanden;
- **Exposition** — bei der Formel BSI vorhanden (0 = offline, 1 = internes Netz, 2 = aus dem Internet erreichbar);
- **Verwundbarkeit** — bei der Formel BSI vorhanden (1 = keine bekannt, 2 = bekannt, nicht ausnutzbar, 3 = intern ausnutzbar, 4 = extern ausnutzbar) sowie bei der Formel MONARC (0 = nicht vorhanden bis 5 = sehr hoch);
- **Bedrohung** — bei der Formel MONARC anstelle der Wahrscheinlichkeit vorhanden (0 = nicht anwendbar bis 4 = sehr wahrscheinlich);
- **Auswirkung** — bei allen Formeln vorhanden.

#### Klassifizierungsschwellenwerte

Die Schwellenwerte legen die Zuordnung zwischen einem numerischen Score und einer qualifizierten Risikostufe fest. Jeder Schwellenwert umfasst:

- **Interner Schlüssel** — technische Kennung (z. B. `low`, `medium`, `high`, `critical`);
- **Label** — Bezeichnung, die in den Abzeichen und Berichten angezeigt wird;
- **Max Wert** — Obergrenze des Schwellenwerts. Der letzte Schwellenwert hat keine Obergrenze (Catch-All);
- **Farbe des Abzeichens** — mit einer Farbauswahl gewählt.

Die Spalte **Vorschau** zeigt in Echtzeit ein Abzeichen mit der eingegebenen Farbe und Bezeichnung.

> Sortieren Sie die Schwellenwerte vom niedrigsten zum höchsten Score. Beim letzten Schwellenwert muss das Feld Max Wert immer leer bleiben.

Wenn Sie klicken auf:

- **Sichern** — die Konfiguration wird gespeichert (bei der Erstellung standardmäßig inaktiv) und Sie kehren zur [Liste der Konfigurationen](#scoring-list) zurück;
- **Abbruch** — Sie kehren ohne Änderung zur [Liste der Konfigurationen](#scoring-list) zurück.

---

## Integration mit den anderen Modulen

Die Risikoliste ist in die bestehenden Module von Deming integriert:

- **Sicherheitsmaßnahmen** — ein Risiko mit Status *Gemindert* kann mit einer oder mehreren Sicherheitsmaßnahmen verknüpft werden. Die Verknüpfung ist bidirektional: Die Detailseite des Risikos listet die zugehörigen Maßnahmen auf und ermöglicht den direkten Zugriff darauf.

- **Aktionspläne** — ein Risiko mit Status *Nicht akzeptiert* muss mit einem oder mehreren Aktionsplänen verknüpft werden. Die Detailseite des Risikos listet die zugehörigen Aktionspläne auf und ermöglicht den direkten Zugriff darauf.

- **Planung** — das nächste Überprüfungsdatum jedes Risikos wird unabhängig von der Planung der Kontrollen verwaltet, kann aber manuell an die Überprüfungszyklen Ihres ISMS angepasst werden.

- **Rollen** — die Zugriffsbeschränkungen folgen demselben Modell wie der Rest der Anwendung:

| Rolle | Zugriff |
|---|---|
| Administrator | Lesen, Erstellen, Ändern, Löschen, Konfiguration der Bewertung |
| User | Lesen, Erstellen, Ändern |
| Auditor | Nur Lesen (alle Risiken) |
| Auditierte | Lesen und Ändern nur der Risiken, für die sie verantwortlich sind |
