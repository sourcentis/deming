# API

Deming kann über eine REST-API geändert oder aktualisiert werden.

Eine REST-API ([Representational State Transfer](https://de.wikipedia.org/wiki/Representational_State_Transfer))
ist eine Programmierschnittstelle, die die Vorgaben der REST-Architektur einhält
und die Interaktion mit RESTful-Webdiensten ermöglicht.

## Installation der API

Um die API zu installieren, muss Passport mit diesem Befehl installiert werden:

```bash
php artisan passport:install
```

Die Docker-Umgebung unterstützt diese Funktion nativ über den [Entrypoint](https://github.com/sourcentis/deming/blob/main/docker/entrypoint.sh).

## Die APIs

| Endpunkt | Rolle                                                                 |
|---|-----------------------------------------------------------------------|
| `/api/attributes` | Attribut-Taxonomien |
| `/api/domains` | Domänen / Rahmenwerke |
| `/api/controls` | **Sicherheitsmaßnahmen** (umzusetzende Anforderungen, mit einer Domäne verknüpft) |
| `/api/measures` | **Kontrollen** (periodische Überprüfungen der Sicherheitsmaßnahmen) |
| `/api/risks` | **Risiken** (Risikoliste, mit verknüpften Maßnahmen und Aktionen) |
| `/api/users` | Benutzer |
| `/api/documents` | An Kontrollen angehängte Dokumente |
| `/api/logs` | Audit-Logs |

> **Erinnerung:** `controls` = Sicherheitsmaßnahmen (`domain_id`, `clause`, `objective`…).  
> `measures` = Kontrollen (`plan_date`, `realisation_date`, `status`, `score`…).

## Vom Ressourcen-Controller verwaltete Aktionen

Die Anfragen und URIs jeder API sind in der folgenden Tabelle aufgeführt.

| Anfrage   | URI                 | Aktion                        |
|-----------|---------------------|-------------------------------|
| GET       | /api/objects        | Gibt die Liste der Objekte zurück |
| GET       | /api/objects/{id}   | Gibt das Objekt {id} zurück   |
| POST      | /api/objects        | Erstellt ein neues Objekt     |
| PUT/PATCH | /api/objects/{id}   | Aktualisiert das Objekt {id}  |
| DELETE    | /api/objects/{id}   | Löscht das Objekt {id}        |

## Zugriffsrechte

Um auf die APIs zuzugreifen, müssen Sie sich als Benutzer der Anwendung Deming anmelden.
Dieser Benutzer muss die Rolle „API“ haben. Standardmäßig wird kein API-Konto angelegt: Legen Sie eines über die
[Benutzerverwaltung](config.de.md#users) an. Die folgenden Beispiele verwenden `api@admin.localhost` / `12345678`.

Bei erfolgreicher Authentifizierung sendet die API ein „Token“, das im Header „Authorization“ der API-Anfrage übergeben werden muss.

## Struktur der Antworten der Endpunkte show

### GET /api/controls/{id} — Sicherheitsmaßnahme

Gibt die Felder der Sicherheitsmaßnahme sowie einen Schlüssel `measures` mit den IDs der
Kontrollen zurück, die diese Maßnahme überprüfen:

```json
{
  "id": 1,
  "domain_id": 1,
  "clause": "NIS2-Art.21.2.a",
  "name": "Risk Analysis",
  "objective": "...",
  "measures": [1, 2, 3]
}
```

### GET /api/measures/{id} — Kontrolle

Gibt die Felder der Kontrolle sowie einen Schlüssel `controls` mit den IDs der
von dieser Kontrolle abgedeckten Sicherheitsmaßnahmen zurück:

```json
{
  "id": 1,
  "name": "Formal review of the risk analysis",
  "plan_date": "2026-07-31",
  "realisation_date": null,
  "status": 0,
  "periodicity": 12,
  "controls": [1]
}
```

### GET /api/risks/{id} — Risiko

Gibt die Felder des Risikos sowie die Schlüssel `controls` und `actions` mit den IDs
der verknüpften Sicherheitsmaßnahmen und Aktionspläne zurück:

```json
{
  "id": 1,
  "name": "Data leakage",
  "description": "...",
  "owner_id": null,
  "probability": 3,
  "impact": 4,
  "status": "not_evaluated",
  "review_frequency": 12,
  "next_review_at": "2027-07-29",
  "controls": [1, 2],
  "actions": [1]
}
```

### POST/PUT /api/controls — optionale verknüpfte Felder

| Schlüssel | Wirkung |
|---|---|
| `measures` | Synchronisiert die Liste der IDs der Kontrollen, die diese Sicherheitsmaßnahme überprüfen |

### POST/PUT /api/risks — optionale verknüpfte Felder

| Schlüssel | Wirkung |
|---|---|
| `controls` | Synchronisiert die Liste der mit diesem Risiko verknüpften Sicherheitsmaßnahmen-IDs |
| `actions` | Synchronisiert die Liste der mit diesem Risiko verknüpften Aktionsplan-IDs |

### POST/PUT /api/measures — optionale verknüpfte Felder

Beim Erstellen oder Aktualisieren einer Kontrolle werden die folgenden Schlüssel akzeptiert,
um die verknüpften Datensätze zu aktualisieren:

| Schlüssel | Wirkung |
|---|---|
| `controls` | Synchronisiert die Liste der mit dieser Kontrolle verknüpften Sicherheitsmaßnahmen-IDs |
| `actions` | Weist dieser Kontrolle Aktions-IDs zu |
| `documents` | Weist dieser Kontrolle Dokument-IDs zu |
| `users` | Synchronisiert die Liste der zugewiesenen Benutzer-IDs |
| `groups` | Synchronisiert die Liste der zugewiesenen Benutzergruppen-IDs |

## Beispiele

Hier einige Beispiele für die Verwendung der API mit PHP:

### Authentifizierung

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query(
            array("email" => "api@admin.localhost",
                  "password" => "12345678")),
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "content-type: application/x-www-form-urlencoded",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $info = curl_getinfo($curl);

    curl_close($curl);

    if ($err) {
        set_error_handler($err);
    } else {
        if ($info['http_code'] == 200) {
            $token = json_decode($response)->token;
        } else {
            error_log($response);
            error_log("No login api status 403");
        }
    }
```

### Domänen auflisten

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/domains",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => null,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

### Eine Sicherheitsmaßnahme abrufen

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/controls/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => null,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

### Eine Sicherheitsmaßnahme aktualisieren

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/controls/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POST => true,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => http_build_query(
            array(
                'name' => 'Updated name',
                'objective' => 'Updated objective',
            )
        ),
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

### Eine Kontrolle abrufen

```php
<?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://127.0.0.1:8000/api/measures/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => null,
        CURLOPT_HTTPHEADER => array(
            "accept: application/json",
            "Authorization: " . "Bearer" . " " . $token . "",
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    var_dump($response);
```

## Python

Hier ein Beispiel für die Verwendung der API in Python:

```python
#!/usr/bin/python3

import requests

vheaders = {}
vheaders['accept'] = 'application/json'

print("Login")
response = requests.post("http://127.0.0.1:8000/api/login",
    headers=vheaders,
    data= {'email':'api@admin.localhost', 'password':'12345678'} )
print(response.status_code)

vheaders['Authorization'] = "Bearer " + response.json()['token']

print("Get security measures")
response = requests.get("http://127.0.0.1:8000/api/controls", headers=vheaders)
print(response.status_code)
print(response.json())

print("Get audit instances")
response = requests.get("http://127.0.0.1:8000/api/measures", headers=vheaders)
print(response.status_code)
print(response.json())
```

## bash

Hier ein Beispiel für die Verwendung der API über die Kommandozeile mit [CURL](https://curl.se/docs/manpage.html) und [JQ](https://stedolan.github.io/jq/):

```bash
# gültige Anmeldedaten
data='{"email":"api@admin.localhost","password":"12345678"}'

# nach erfolgreicher Anmeldung ein Token erhalten
token=$(curl -s -d ${data} -H "Content-Type: application/json" http://localhost:8000/api/login | jq -r .token)

# Sicherheitsmaßnahmen auflisten
curl -s -H "Content-Type: application/json" -H "Authorization: Bearer ${token}" "http://127.0.0.1:8000/api/controls" | jq .

# Kontrollen auflisten
curl -s -H "Content-Type: application/json" -H "Authorization: Bearer ${token}" "http://127.0.0.1:8000/api/measures" | jq .
```
