<?php

class GrandMa2Instance extends IPSModule
{
    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();

        $this->RegisterPropertyString('ServerAddress', '');
        $this->RegisterPropertyInteger('ServerPort', 30000);
        $this->RegisterPropertyString('Username', 'administrator');
        $this->RegisterPropertyString('Password', '');

        $this->RegisterVariableInteger('LastSequenceExecuted', 'Zuletzt ausgeführte Sequenz', '', 1);
        $this->RegisterVariableString('LastStatus', 'Letzter Status', '', 2);
    }

    // Überschreibt die interne IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        if (empty(trim($this->ReadPropertyString('ServerAddress')))) {
            $this->SetStatus(201);
        } else {
            $this->SetStatus(102);
        }
    }

    public function TestConnection() {
        $address = $this->ReadPropertyString('ServerAddress');
        $port    = $this->ReadPropertyInteger('ServerPort');

        if (empty(trim($address))) {
            echo 'Fehler: Keine Server-Adresse konfiguriert.';
            $this->SetStatus(201);
            return false;
        }

        $fp = @fsockopen($address, $port, $errno, $errstr, 5);
        if (!$fp) {
            $msg = 'Verbindung fehlgeschlagen: ' . $errstr . ' (Fehlercode ' . $errno . ')';
            $this->LogMessage('GMA2: ' . $msg, KL_ERROR);
            $this->SetValueIfChanged('LastStatus', $msg);
            $this->SetStatus(200);
            echo $msg;
            return false;
        }

        fclose($fp);

        $msg = 'Verbunden (' . $address . ':' . $port . ')';
        $this->SetValueIfChanged('LastStatus', $msg);
        $this->SetStatus(102);
        echo $msg;
        return true;
    }

    public function Execute(array $commands) {
        $address  = $this->ReadPropertyString('ServerAddress');
        $port     = $this->ReadPropertyInteger('ServerPort');
        $username = $this->ReadPropertyString('Username');
        $password = $this->ReadPropertyString('Password');

        if (empty(trim($address))) {
            $this->LogMessage('GMA2: Keine Server-Adresse konfiguriert', KL_ERROR);
            $this->SetStatus(201);
            return false;
        }

        $fp = @fsockopen($address, $port, $errno, $errstr, 5);
        if (!$fp) {
            $msg = 'Verbindung fehlgeschlagen: ' . $errstr . ' (Fehlercode ' . $errno . ')';
            $this->LogMessage('GMA2: ' . $msg, KL_ERROR);
            $this->SetValueIfChanged('LastStatus', $msg);
            $this->SetStatus(200);
            return false;
        }

        // Alles in einem Block senden – keine Wartezeiten auf Antworten
        $block = '';

        if (!empty(trim($username))) {
            $block .= 'Login "' . $username . '" "' . $password . '"' . "\r\n";
        }

        foreach ($commands as $cmd) {
            $this->SendDebug('GMA2 Telnet CMD', $cmd, 0);
            $block .= $cmd . "\r\n";
        }

        $block .= 'logout' . "\r\n";

        $this->SendDebug('GMA2 Telnet Block', $block, 0);
        fwrite($fp, $block);

        fclose($fp);

        $this->SetValueIfChanged('LastStatus', 'OK – ' . count($commands) . ' Kommando(s) gesendet');
        $this->SetStatus(102);
        return true;
    }

    // Daten vom Child empfangen
    public function ForwardData($JSONString) {
        $this->SendDebug('GMA2 ForwardData', $JSONString, 0);

        $data = json_decode($JSONString, true);

        if (($data['DataID'] ?? '') === '{94053D1B-05E5-BF8B-36BC-480E198272D0}') {
            $buf      = json_decode($data['Buffer'], true);
            $commands = $buf['Commands'] ?? [];
            $seqId    = $buf['SequenceID'] ?? 0;

            $this->SendDebug('GMA2 ForwardData Commands', implode(', ', $commands), 0);

            $success = $this->Execute($commands);

            if ($success && $seqId > 0) {
                $this->SetValueIfChanged('LastSequenceExecuted', $seqId);
            }
        }

        return '';
    }

    public function RequestAction($Ident, $Value) {
    }

    private function SetValueIfChanged($ident, $value) {
        $vid = @$this->GetIDForIdent($ident);
        if ($vid === 0) {
            return;
        }
        if (GetValue($vid) === $value) {
            return;
        }
        $this->SetValue($ident, $value);
    }
}
