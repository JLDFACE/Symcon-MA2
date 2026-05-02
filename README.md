# GrandMa2 Control

IP-Symcon Modul zur Steuerung einer **GrandMA2 Lichtsteuerung** über Telnet.

Entwickelt von **FACE GmbH** – [https://face-gmbh.de](https://face-gmbh.de)

---

## Überblick

Dieses Modul ermöglicht das Auslösen von GrandMA2-Kommandos direkt aus IP-Symcon heraus. Kommandosequenzen können frei konfiguriert und über Automationen oder Skripte ausgeführt werden.

**Funktionen:**
- Beliebige GrandMA2-Kommandos als Sequenz konfigurierbar
- Telnet-Kommunikation (TCP Port 30000) mit Login/Logout
- Verbindungstest direkt aus der Konfiguration
- Statusanzeige im Modul (Verbunden / Fehler / Nicht konfiguriert)
- Debug-Ausgabe für alle gesendeten Kommandos und Antworten

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

Verwaltet die Telnet-Verbindung zur GrandMA2 und sendet die Kommandos.

**Konfiguration:**

| Feld            | Beschreibung                          | Standard |
|-----------------|---------------------------------------|----------|
| Server-Adresse  | IP-Adresse oder Hostname der GrandMA2 | –        |
| Telnet-Port     | TCP-Port der GrandMA2 Telnet-Remote   | 30000    |
| Benutzername    | GrandMA2 Benutzername                 | administrator |
| Passwort        | GrandMA2 Passwort                     | –        |

**Variablen:**

| Ident                   | Typ     | Beschreibung                                |
|-------------------------|---------|---------------------------------------------|
| `LastSequenceExecuted`  | Integer | InstanceID der zuletzt ausgeführten Sequenz |
| `LastStatus`            | String  | Ergebnis der letzten Kommunikation          |

**Statusanzeige:**
- **Verbunden** – Server erreichbar, letzte Ausführung erfolgreich
- **Server nicht erreichbar** – Verbindungsfehler oder Login fehlgeschlagen
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
                                                    Telnet TCP :30000
                                                    Login → Kommandos → Logout
                                                               │
                                                               ▼
                                                        [GrandMA2 Pult]
```

Pro Ausführung wird eine TCP-Verbindung aufgebaut, Login gesendet, alle Kommandos in einem Block übertragen und anschließend sauber ausgeloggt.

---

## Inbetriebnahme

1. Modul in IP-Symcon als Bibliothek hinzufügen (`https://github.com/JLDFACE/Symcon-MA2`)
2. Instanz **GrandMa2 Instance** anlegen
3. IP-Adresse, Port (30000), Benutzername und Passwort der GrandMA2 eintragen
4. Telnet-Remote auf der GrandMA2 aktivieren (Setup → Console → Remote → Telnet Remote)
5. Mit **„Verbindung testen"** die Erreichbarkeit prüfen
6. Beliebig viele **GrandMa2 Command Sequence** Instanzen als Children anlegen
7. Kommandos in der jeweiligen Sequenz konfigurieren
8. `GMA2_ExecuteCommands($id)` im Skript oder einer Automation aufrufen

---

## Troubleshooting

**Verbindung fehlgeschlagen:**
- IP-Adresse und Port (Standard: 30000) prüfen
- Telnet-Remote auf der GrandMA2 aktiviert?
- Firewall zwischen Symcon und GrandMA2 prüfen

**Login fehlgeschlagen:**
- Benutzername und Passwort prüfen
- Benutzer auf der GrandMA2 vorhanden und berechtigt?

**Kommandos werden nicht ausgeführt:**
- Debug-Fenster der GrandMa2 Instance öffnen (Protokollierung aktivieren)
- Kommando-Syntax direkt per Telnet-Client testen (z. B. PuTTY auf Port 30000)

---

## Kompatibilität

- IP-Symcon ab Version **7.0**
- PHP **8.x** kompatibel

---

## Changelog

### Version 1.1
- Umstieg von HTTP auf Telnet (TCP Port 30000)
- Login/Logout pro Verbindung
- Non-blocking Banner-Lesen für schnelle Ausführung
- Prompt-Erkennung (`[Channel]>`) sichert vollständige Befehlsübertragung
- Fehlerbehandlung, Statusanzeige und Debug-Ausgabe
- Benutzername und Passwort konfigurierbar
- Umbau auf FACE GmbH Modulstandard, PHP 8.x kompatibel

### Version 1.0
- Erstveröffentlichung durch hill concepts | Alexander Hill

---

## Lizenz

Dieses Modul wird von der **FACE GmbH** bereitgestellt.

---

## Support

Bei Fragen oder Problemen: [https://face-gmbh.de](https://face-gmbh.de)
