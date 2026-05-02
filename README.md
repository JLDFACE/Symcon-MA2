# GrandMa2 Control

IP-Symcon Modul zur Steuerung einer **GrandMA2 Lichtsteuerung** über HTTP.

Entwickelt von **FACE GmbH** – [https://face-gmbh.de](https://face-gmbh.de)

---

## Überblick

Dieses Modul ermöglicht das Auslösen von GrandMA2-Kommandos direkt aus IP-Symcon heraus. Kommandosequenzen können frei konfiguriert und über Automationen oder Skripte ausgeführt werden.

**Funktionen:**
- Beliebige GrandMA2-Kommandos als Sequenz konfigurierbar
- HTTP-Kommunikation mit Fehlerbehandlung und Timeout
- Verbindungstest direkt aus der Konfiguration
- Statusanzeige im Modul (Verbunden / Fehler / Nicht konfiguriert)
- Debug-Ausgabe für alle Requests und Responses

---

## Repository-Struktur

```
grandma2control/
├── library.json
├── README.md
├── GrandMa2 Instance/        ← Parent-Modul (Verbindung)
│   ├── module.json
│   ├── module.php
│   └── form.json
└── GrandMa2 Comand Sequence/ ← Child-Modul (Kommandoliste)
    ├── module.json
    ├── module.php
    └── form.json
```

---

## Module

### GrandMa2 Instance (Parent)

Verwaltet die Verbindung zur GrandMA2 und sendet die HTTP-Requests.

**Konfiguration:**

| Feld              | Beschreibung                          | Standard |
|-------------------|---------------------------------------|----------|
| Server-Adresse    | IP-Adresse oder Hostname der GrandMA2 | –        |
| Port              | HTTP-Port                             | 80       |

**Variablen:**

| Ident                   | Typ     | Beschreibung                                    |
|-------------------------|---------|-------------------------------------------------|
| `LastSequenceExecuted`  | Integer | InstanceID der zuletzt ausgeführten Sequenz     |
| `LastStatus`            | String  | Ergebnis der letzten Kommunikation              |

**Statusanzeige:**
- **Verbunden** – Server erreichbar, letzte Ausführung erfolgreich
- **Server nicht erreichbar** – Verbindungsfehler oder HTTP-Fehler
- **Nicht konfiguriert** – Keine Server-Adresse eingetragen

**Verfügbare Funktionen:**
```php
GMA2_TestConnection($id);           // Verbindung zum Server testen
GMA2_Execute($id, $commands);       // Array von Kommandos direkt senden
```

---

### GrandMa2 Command Sequence (Child)

Konfigurierbare Liste von GrandMA2-Kommandos, die als Sequenz ausgeführt werden.

**Konfiguration:**

Eine beliebige Anzahl von Kommandos kann über die Liste in der Konfigurationsoberfläche gepflegt werden.

**Verfügbare Funktionen:**
```php
GMA2_ExecuteCommands($id);          // Alle konfigurierten Kommandos ausführen
```

---

## Ansteuerungskonzept

```
[IP-Symcon Skript / Automation]
        │
        ▼
[GrandMa2 Command Sequence]  ──(SendDataToParent)──▶  [GrandMa2 Instance]
                                                               │
                                                    HTTP POST /execute
                                                               │
                                                               ▼
                                                        [GrandMA2 Server]
```

Der Parent sendet einen HTTP POST-Request an `http://<ServerAddress>:<ServerPort>/execute` mit dem JSON-Body:

```json
{
  "commands": ["Befehl 1", "Befehl 2"]
}
```

---

## Inbetriebnahme

1. Modul in IP-Symcon als Bibliothek hinzufügen
2. Instanz **GrandMa2 Instance** anlegen
3. Server-Adresse und Port der GrandMA2 eintragen
4. Mit **„Verbindung testen"** die Erreichbarkeit prüfen
5. Beliebig viele **GrandMa2 Command Sequence** Instanzen als Children anlegen
6. Kommandos in der jeweiligen Sequenz konfigurieren
7. `GMA2_ExecuteCommands($id)` im Skript oder einer Automation aufrufen

---

## Troubleshooting

**Server nicht erreichbar:**
- IP-Adresse und Port prüfen
- Firewall-Regeln auf der GrandMA2 prüfen
- HTTP-API auf der GrandMA2 aktiviert?

**Kommandos werden nicht ausgeführt:**
- Debug-Fenster der GrandMa2 Instance öffnen (Protokollierung aktivieren)
- Kommando-Syntax in der GrandMA2-Dokumentation prüfen

---

## Kompatibilität

- IP-Symcon ab Version **7.0**
- PHP **8.x** kompatibel

---

## Changelog

### Version 1.1
- Fehlerbehandlung und Timeouts für HTTP-Requests hinzugefügt
- Verbindungstest direkt in der Konfiguration
- Statusanzeige im Modul (Verbunden / Fehler / Nicht konfiguriert)
- Variable `LastStatus` für Rückmeldung im Objektbaum
- `utf8_encode`/`utf8_decode` (PHP 8.2 deprecated) entfernt
- Debug-Ausgabe für alle Requests
- Umbau auf FACE GmbH Modulstandard

### Version 1.0
- Erstveröffentlichung durch hill concepts | Alexander Hill

---

## Lizenz

Dieses Modul wird von der **FACE GmbH** bereitgestellt.

---

## Support

Bei Fragen oder Problemen: [https://face-gmbh.de](https://face-gmbh.de)
