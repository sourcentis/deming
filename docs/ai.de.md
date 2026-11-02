# KI-Agent

Deming kann einen KI-Agenten aufrufen, um eine [Sicherheitsmaßnahme](measures.de.md#show), eine
[Kontrolle](controls.de.md#show), einen [Aktionsplan](actions.de.md#show) oder ein [Risiko](risks.de.md#show) zu
analysieren. Der Agent liefert eine strukturierte Stellungnahme: Zusammenfassung, bewerteter Status, Risikoniveau,
Feststellungen, Empfehlungen, offene Fragen und vier Standpunkte (CEO, CISO, Datenschutzbeauftragter und Auditor).

Der Agent ist eine **Entscheidungshilfe**: Er ändert niemals Daten in Deming. Seine Ergebnisse müssen von einem
Menschen geprüft werden.

Die KI ist **standardmäßig deaktiviert**. Solange ein Administrator sie nicht aktiviert, werden keine Daten gesendet
und das Analyse-Symbol wird nicht angezeigt.

Der KI-Agent wird vom **Sourcentis-KI-Gateway** bereitgestellt, einem privaten Dienst, der die Prompts, das
Antwortformat und die Modelle verwaltet. Deming sendet ihm nur die Daten des zu analysierenden Objekts. Die Funktion
ist daher Sourcentis-Kunden vorbehalten: Standardmäßig ist kein Agent konfiguriert, und Deming kann weder einen anderen
Anbieter noch ein lokales Modell direkt ansprechen.

## Aktivieren und konfigurieren {#config}

Die Konfiguration befindet sich in der Registerkarte „KI“ der Seite [Einstellungen](config.de.md#parameters) (Menü
„Konfiguration → Einstellungen“). Sie ist Administratoren vorbehalten.

<!-- TODO capture : images/ai-config.de.png -->

Die Seite enthält einen Hinweis „Datenschutz“, der zusammenfasst, was an den Agenten gesendet wird; lesen Sie den
Abschnitt [Datenschutz](#privacy), bevor Sie die KI aktivieren.

Die Felder sind:

* **KI aktivieren**: aktiviert oder deaktiviert den Agenten. Im deaktivierten Zustand wird keine Anfrage an den
  Agenten gesendet, die bereits gespeicherten Analysen bleiben jedoch erhalten (siehe
  [Alle Analysen löschen](#purge)). Solange die KI deaktiviert ist, sind die übrigen Felder ausgegraut; sie behalten
  ihre Werte und sind wieder bearbeitbar, sobald die KI aktiviert wird;

* **URL des KI-Agenten**: die von Sourcentis mitgeteilte Adresse, `https://ai.sourcentis.lu/v1`. Es gibt keinen
  Standardwert. `https` ist Pflicht, außer `http://localhost` oder `http://127.0.0.1` (Entwicklungs-Gateway);

* **API-Schlüssel**: der von Sourcentis mitgeteilte Zugangsschlüssel. Er wird nie wieder angezeigt: Die Seite zeigt
  nur „Schlüssel gespeichert“ oder „Kein Schlüssel“. Ein leeres Feld behält den aktuellen Schlüssel; das Kästchen
  „Schlüssel löschen“ entfernt ihn. Der Schlüssel wird in der Datenbank mit dem Anwendungsschlüssel (`APP_KEY`)
  verschlüsselt: Ändert sich der `APP_KEY`, muss der Schlüssel erneut eingegeben werden.

Weder Modell noch Prompt sind einzustellen: Sie werden vom Anbieter des Agenten verwaltet und versioniert. Ist die KI
aktiviert, zeigt die Seite schreibgeschützt die **verfügbaren Aufgaben** des Gateways, eine pro Objekttyp
(Sicherheitsmaßnahme, Kontrolle, Aktionsplan und Risiko), mit ihrer Version. Ist das Gateway nicht erreichbar oder
lehnt es den Schlüssel ab, erscheint statt der Liste ein Hinweis.

Die Schaltfläche „Verbindung testen“ fragt mit der gespeicherten Konfiguration den Aufgabenkatalog des Gateways ab.
Diese Anfrage ist kostenlos: Sie verbraucht keine Tokens. Sie zeigt den HTTP-Status, die Latenz und die Anzahl der
gefundenen Deming-Aufgaben an: „4/4 Deming-Aufgaben“ bedeutet, dass alles bereit ist. Der Schlüssel wird nie
angezeigt.

### Einen Schlüssel erhalten

URL und Schlüssel werden von Sourcentis mitgeteilt. Der Schlüssel wird nur einmal übermittelt: Fügen Sie ihn in das
Feld „API-Schlüssel“ von Deming ein.

### Alle Analysen löschen {#purge}

Die Schaltfläche „Alle KI-Analysen löschen“ unten in der Registerkarte löscht nach Bestätigung die zuletzt gespeicherte
Analyse jeder Sicherheitsmaßnahme, jeder Kontrolle, jedes Aktionsplans und jedes Risikos. Der Vorgang wird in den
[Logs](config.de.md#logs) protokolliert (Benutzer und Datum, ohne Inhalt).

## Die Analyse verwenden {#use}

Ist die KI aktiviert, erscheint ein Roboter-Symbol „KI-Analyse“ oben rechts auf den Anzeigeseiten einer
[Sicherheitsmaßnahme](measures.de.md#show), einer [Kontrolle](controls.de.md#show), eines
[Aktionsplans](actions.de.md#show) und eines [Risikos](risks.de.md#show), unter der Hilfe-Schaltfläche. Ein grüner
Punkt am Symbol zeigt an, dass bereits eine Analyse gespeichert ist.

Ein Klick auf das Symbol öffnet ein Fenster am rechten Bildschirmrand. Haben sich das Objekt und seine verknüpften
Objekte seit der letzten Analyse nicht geändert, wird diese sofort und ohne Aufruf des Agenten angezeigt, mit dem
Hinweis „Analyse vom … — seitdem unverändert“. Andernfalls wird eine neue Analyse gestartet; sie kann bis zu einer
Minute dauern.

<!-- TODO capture : images/ai-panel.de.png -->

Das Fenster enthält:

* den **bewerteten Status**, das **Risikoniveau** und das **Vertrauen** des Agenten in seine eigene Analyse;

* die **Zusammenfassung**;

* die **Feststellungen**: Stärken, Schwächen, fehlende Nachweise und Widersprüche;

* die **Empfehlungen** mit ihrer Priorität, Begründung, einer vorgeschlagenen Frist und einer Normreferenz;

* die **offenen Fragen**, falls vorhanden;

* vier bewusst gegensätzliche **Standpunkte**: der CEO (geschäftliche Aspekte und Kosten), der CISO (Risikobewertung
  und Prioritäten), der Datenschutzbeauftragte (personenbezogene Daten und DSGVO) und der Auditor (normative
  Anforderungen und Nachweise).

Die Bedeutung des Status hängt vom Objekttyp ab:

| Status | Sicherheitsmaßnahme / Kontrolle | Aktionsplan | Risiko |
|---|---|---|---|
| `conforme` | Konform | Zielführend und auf Kurs | Angemessene Behandlung |
| `partiellement_conforme` | Teilweise konform | Zu verstärken | Teilweise Behandlung |
| `non_conforme` | Nicht konform | Ungeeignet oder ins Stocken geraten | Unangemessene Behandlung |
| `non_evaluable` | Nicht bewertbar | Nicht bewertbar | Nicht bewertbar |

Die Schaltfläche „Analyse erneut ausführen“ erzwingt eine neue Analyse, auch wenn sich nichts geändert hat. Die
Fußzeile des Fensters erinnert daran, dass die Analyse von einer KI erstellt wurde und von einem Menschen geprüft
werden muss, und zeigt das Modell, das sie erstellt hat, sowie den Host des Agenten, an den die Daten gesendet wurden. Der Link „Gesendete
Daten“ zeigt den [übermittelten Kontext](#preview).

Nur die letzte Analyse jedes Objekts wird aufbewahrt. Eine neue Analyse wird automatisch ausgeführt, wenn sich das
Objekt, seine verknüpften Objekte oder die Sprache des Benutzers ändern, oder wenn der Anbieter eine neue Version der
Aufgabe veröffentlicht (Prompt, Format oder Modell). Ist das Gateway vorübergehend nicht erreichbar, bleibt die letzte
Analyse sichtbar.

### Fehlermeldungen {#errors}

Bei einem Fehler zeigt das Fenster eine allgemeine Meldung, nie die Antwort des Gateways, und die vorherige Analyse
bleibt erhalten. Die wichtigsten Meldungen sind:

| Meldung | Ursache | Was tun |
|---|---|---|
| „Zugangsschlüssel des KI-Agenten ungültig, gesperrt oder gekündigt“ | Das Gateway lehnt den Schlüssel ab (HTTP 401) oder erlaubt ihn nicht für diese Analyse (HTTP 403) | Schlüssel in der Konfiguration prüfen, dann Sourcentis kontaktieren |
| „KI-Monatskontingent erreicht — wenden Sie sich an Ihren Anbieter“ | Das Monatsbudget des Schlüssels ist erschöpft (HTTP 402), bis zum 1. des Folgemonats | Sourcentis kontaktieren |
| „Zu viele Anfragen, bitte in wenigen Augenblicken erneut versuchen“ | Ratenbegrenzung des Schlüssels oder des Anbieters (HTTP 429) | Etwas später erneut versuchen |
| „Objekt zu groß für eine Analyse“ | Der Kontext überschreitet die vom Gateway zugelassene Größe (HTTP 413) | Die langen Texte des Objekts kürzen |
| „KI-Agent nicht kompatibel mit dieser Deming-Version“ | Anfrage abgelehnt oder Aufgabe unbekannt (HTTP 400 oder 404) | Deming aktualisieren oder Sourcentis kontaktieren |
| „Ungültige Antwort des KI-Agenten“ / „Der KI-Agent hat nicht rechtzeitig geantwortet“ | Ungültige Antwort des Modells (HTTP 502) oder Zeitüberschreitung (HTTP 504) | Analyse erneut ausführen |

## Wer eine Analyse starten kann {#access}

Ein Objekt kann analysieren, wer es anzeigen darf, außer mit der Rolle Auditor oder API. Die Rechte werden bei jeder
Anfrage vom Server geprüft.

| Rolle | Sicherheitsmaßnahme | Kontrolle | Aktionsplan | Risiko |
|---|---|---|---|---|
| Administrator | ✓ | ✓ | ✓ | ✓ |
| Benutzer | ✓ (je nach Zuweisung und Gruppenansicht) | ✓ (ebenso) | ✓ | ✓ |
| Auditierter | ✓ wenn zugewiesen | ✓ wenn zugewiesen | — | ✓ (alle Risiken) |
| Auditor | — | — | — | — |
| API | — | — | — | — |

Einzelheiten:

* in der Gruppenansicht ist eine Sicherheitsmaßnahme nur zugänglich, wenn der Benutzer direkt einer ihrer Kontrollen
  zugewiesen ist; eine Kontrolle ist zugänglich, wenn der Benutzer ihr direkt oder über eine seiner
  [Gruppen](config.de.md#groups) zugewiesen ist;

* der Auditierte hat weder Zugriff auf Aktionspläne noch auf deren Analyse;

* der Auditierte kann alle Risiken anzeigen und damit analysieren;

* der Auditor hat weder Zugriff auf das Symbol noch auf das Fenster noch auf gespeicherte Analysen, auch nicht für ein
  Objekt, das er anzeigen kann.

## Datenschutz {#privacy}

Dieser Abschnitt beschreibt genau, was Deming bei einer Analyse verlässt. Er richtet sich insbesondere an den CISO und
den Datenschutzbeauftragten.

### An den Agenten gesendete Daten

Jede Analyse sendet dem Gateway, an die Aufgabe des Objekttyps:

* den nachstehend beschriebenen **Kontext** des analysierten Objekts, nach der [Maskierung](#redaction);

* die **Sprache** des Benutzers (`fr`, `en` oder `de`);

* das **heutige Datum**.

Sonst wird nichts übermittelt: weder Prompt noch Antwortformat noch Modellname. Prompt und Format fügt das Gateway
hinzu.

Lange Textfelder des analysierten Objekts sind auf 4.000 Zeichen begrenzt, jene der verknüpften Objekte
(Beobachtungen, Begründungen…) auf 300 Zeichen.

**Sicherheitsmaßnahme**: Klausel, Name, Domäne (Rahmenwerk und Titel), Ziel, Attribute, Eingabedaten, Modell,
Indikator, Aktionsplan, Periodizität, die letzten 5 durchgeführten Kontrollen (Datum, Bewertung, zusammengefasste
Beobachtungen und Aktionsplan), die nächsten 5 geplanten Kontrollen (geplantes Datum, Status, verspätet oder nicht),
verknüpfte offene Aktionspläne (Referenz, Name, Typ, Kritikalität, Status, Fortschritt, Frist, verspätet oder nicht),
verknüpfte Risiken (Name, Niveau, Behandlungsstatus), Ausnahmen (Name, Status, Beginn- und Enddatum, abgelaufen oder
nicht, zusammengefasste Begründung und kompensierende Maßnahmen) und bis zu 10 Zuordnungen zu anderen Rahmenwerken
(Rahmenwerk, Klausel, Name, Typ und Abdeckung).

**Kontrolle**: Name, Geltungsbereich, abgedeckte Sicherheitsmaßnahmen (Klausel und Name), Ziel, Attribute,
Eingabedaten, Modell, Indikator, Periodizität, Status, geplantes Datum, Durchführungsdatum, verspätet oder nicht,
Bewertung, Note, Beobachtungen, Aktionsplan, Dokumente (**nur Anzahl und Dateinamen**), aus der Kontrolle
hervorgegangene Aktionspläne (Referenz, Name, Typ, Kritikalität, Status, Fortschritt, Frist, verspätet oder nicht) und
die letzten 5 durchgeführten Kontrollen derselben Sicherheitsmaßnahme (Datum, Bewertung, zusammengefasste
Beobachtungen und Aktionsplan).

**Aktionsplan**: Referenz, Name, Typ, Kritikalität, Status, Geltungsbereich, Ursache, Abhilfe, Erstellungs-, Fälligkeits-
und Abschlussdatum, Fortschritt, Begründung, verspätet oder nicht, **Anzahl** der Verantwortlichen, verknüpfte
Sicherheitsmaßnahmen (Klausel, Name, Datum und Bewertung der letzten Kontrolle), verknüpfte Risiken (Name, Niveau,
Behandlungsstatus) und die letzten 5 Fortschrittsschritte (Datum und Prozentsatz, ohne Urheber).

**Risiko**: Name, Beschreibung, Wahrscheinlichkeit und Auswirkung (Wert, Bezeichnung und Kommentar), Exposition und
Verwundbarkeit (Wert und Bezeichnung), Restrisiko, aktive Bewertungsmethode (Name, Formel und Schwellenwerte),
berechnete Bewertung und berechnetes Niveau, Behandlungsstatus und dessen Kommentar, Überprüfungshäufigkeit, Datum der
nächsten Überprüfung, Überprüfung überfällig oder nicht, **ob** ein Eigentümer vorhanden ist (ja oder nein), Bedarf an
Maßnahmen oder Aktionen je nach Status, verknüpfte Sicherheitsmaßnahmen (Klausel, Name, Datum und Bewertung der letzten
Kontrolle) und verknüpfte Aktionspläne (Referenz, Name, Kritikalität, Fortschritt, Frist, abgeschlossen oder nicht,
verspätet oder nicht).

### Niemals gesendete Daten

* Namen, Logins und E-Mail-Adressen der Deming-Benutzer;

* die Identität der Verantwortlichen eines Aktionsplans und des Eigentümers eines Risikos;

* der Inhalt von Dokumenten (nur deren Namen werden gesendet);

* Audit-Protokolle;

* die Konfiguration von Deming und der API-Schlüssel;

* Objekte, die nicht mit dem analysierten Objekt verknüpft sind.

### Automatische Maskierung {#redaction}

Vor dem Senden werden alle Texte des Kontexts gefiltert. Ersetzt werden:

* E-Mail-Adressen durch `[email]`;

* Telefonnummern (internationale, belgische, luxemburgische, französische und deutsche Formate) durch `[téléphone]`;

* IBANs durch `[iban]`;

* IP-Adressen (IPv4 und IPv6) durch `[ip]`;

* Name, Login und E-Mail-Adresse jedes Deming-Benutzers, als ganze Wörter und ohne Berücksichtigung der
  Groß-/Kleinschreibung, durch `[utilisateur]`. Werte mit weniger als drei Zeichen werden ignoriert, um
  Fehlerkennungen zu vermeiden.

!!! warning "Wichtige Einschränkung — Freitextfelder"
    Die Maskierung ist **keine** Anonymisierung. Sie erkennt nicht alles: Der Name eines Patienten, eines Kunden oder
    eines externen Mitarbeiters in Beobachtungen, einer Beschreibung oder einer Begründung **wird an den Agenten
    übermittelt**. Geben Sie keine personenbezogenen Daten in die Textfelder von Deming ein und erinnern Sie die
    Benutzer an diese Regel.

### Vorschau der gesendeten Daten {#preview}

Der Link „Gesendete Daten“ unten im Analysefenster zeigt den Kontext genau so, wie er an den Agenten gesendet wird,
nach Minimierung und Maskierung. Diese Vorschau ruft den Agenten nicht auf: Sie kann vor oder nach einer
Analyse genutzt werden, um zu prüfen, was tatsächlich gesendet wird. Sie unterliegt denselben Rechten wie die Analyse.

<!-- TODO capture : images/ai-preview.de.png -->

### Wohin die Daten gehen

Die Daten werden ausschließlich an den in der URL des Agenten konfigurierten Host gesendet, der unten im Fenster
angezeigt wird. Deming folgt keiner Umleitung zu einem anderen Host, und `https` mit Zertifikatsprüfung ist Pflicht,
außer für ein Entwicklungs-Gateway auf `localhost`.

Der Weg der Daten ist folgender:

1. **Deming** minimiert und maskiert den Kontext vor jedem Versand;
2. das **Sourcentis-KI-Gateway** (Auftragsverarbeiter) fügt den Prompt und das Antwortformat hinzu und leitet die
   Anfrage an das Modell weiter;
3. **OVHcloud** (Unterauftragsverarbeiter) führt das Modell aus.

Laut seiner Dokumentation speichert und protokolliert das Gateway weder den Kontext noch den Prompt noch die Antwort:
Für die Abrechnung werden nur tägliche Summen aufbewahrt (Kunde, Modell, Anzahl der Tokens, Kosten und Datum). Die
Speicherung der Daten bei OVHcloud richtet sich nach dessen Bedingungen und Auftragsverarbeitungsvertrag (AVV).

### Was Deming speichert

* Nur die **letzte Analyse** jedes Objekts, gespeichert im Objekt selbst. Sie wird mit dem Objekt oder über die
  Schaltfläche „[Alle KI-Analysen löschen](#purge)“ gelöscht.

* Analysen erscheinen weder in der [REST-API](api.de.md) noch in Exporten oder Berichten und werden nicht in den
  [Logs](config.de.md#logs) protokolliert: Eine Analyse ist keine Änderung des Objekts.

* Die Anwendungsprotokolle enthalten nur Metadaten: Typ und Kennung des Objekts, Modell, Version der Aufgabe, Dauer,
  Anzahl der Tokens und Status. Weder Kontext noch Antwort oder Schlüssel werden protokolliert.

* Der API-Schlüssel wird mit dem `APP_KEY` der Anwendung verschlüsselt; eine Änderung des `APP_KEY` erfordert die
  erneute Eingabe des Schlüssels.

### Sicherheit der Funktion

* Die Analyse unterliegt denselben Rechten wie die Anzeige des Objekts; die Rollen Auditor und API sind ausgeschlossen
  (siehe [Wer eine Analyse starten kann](#access)).

* Die Anfragen sind gegen CSRF geschützt und auf 10 pro Minute begrenzt.

* Die Antworten der KI werden als Text angezeigt, ohne HTML-Interpretation.

* Die Daten des Objekts werden dem Agenten als Daten, niemals als Anweisungen übergeben: Ein Injektionsversuch in
  einem Textfeld wird als Widerspruch gemeldet.

* Die KI kann in Deming nichts ändern.

### Vor der Aktivierung der KI

Vom Verantwortlichen zu prüfende Punkte. Diese Empfehlungen stellen keine Rechtsberatung dar.

- [ ] Den Datenschutzbeauftragten konsultieren.
- [ ] Das Verzeichnis von Verarbeitungstätigkeiten (DSGVO, Artikel 30) aktualisieren, falls personenbezogene Daten
      übermittelt werden können.
- [ ] Einen Auftragsverarbeitungsvertrag (AVV, DSGVO Artikel 28) mit Sourcentis abschließen, der OVHcloud als
      Unterauftragsverarbeiter nennt.
- [ ] Den Ort der Verarbeitung prüfen und sicherstellen, dass keine Übermittlung außerhalb der Europäischen Union
      erfolgt, auch bei OVHcloud.
- [ ] Den Bedarf einer Datenschutz-Folgenabschätzung (DSFA) prüfen, insbesondere in Branchen mit sensiblen Daten wie
      dem Gesundheitswesen.
- [ ] Die Deming-Benutzer über die Funktion und die Regel zu Freitexten informieren.
- [ ] Bedenken, dass die Ergebnisse eine von einem Menschen geprüfte Entscheidungshilfe sind: Es wird keine
      Entscheidung automatisch getroffen, und der Agent behandelt keine Entscheidungen über Personen.

## Grenzen {#limits}

* Die KI kann sich irren, Daten falsch interpretieren oder einen wichtigen Punkt übersehen.

* Die zitierten Normreferenzen müssen überprüft werden.

* Die Ergebnisse sind nicht streng reproduzierbar: Zwei Analysen derselben Daten können sich unterscheiden.

* Kosten und Antwortzeit hängen vom Anbieter und vom Modell ab.

* Die Funktion setzt ein Abonnement des Sourcentis-KI-Gateways voraus.

* Die Qualität der Analyse hängt von der Qualität der in Deming erfassten Daten ab.
