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

        // Non-blocking: Banner vollständig lesen und verwerfen (kein Blockieren möglich)
        stream_set_blocking($fp, false);
        usleep(80000); // 80ms – reicht für Banner im LAN
        $banner = '';
        while (($chunk = fread($fp, 65536)) !== false && $chunk !== '') {
            $banner .= $chunk;
        }
        $this->SendDebug('GMA2 Telnet Banner', $banner, 0);
        stream_set_blocking($fp, true);

        // Login + Kommandos + Logout als ein Block senden
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
        stream_set_blocking($fp, true);
        fwrite($fp, $block);

        // Warten bis die MA alle Kommandos verarbeitet hat (logout-Prompt abwarten)
        // Die MA sendet nach jedem Kommando "[Channel]>" – wir zählen die Prompts
        $expectedPrompts = count($commands) + 2; // Login + Kommandos + Logout
        stream_set_blocking($fp, false);
        $response      = '';
        $promptCount   = 0;
        $deadline      = microtime(true) + 3.0;
        while (microtime(true) < $deadline) {
            $chunk = fread($fp, 65536);
            if ($chunk !== false && $chunk !== '') {
                $response .= $chunk;
                $promptCount = substr_count($response, '[Channel]>');
                if ($promptCount >= $expectedPrompts) {
                    break;
                }
            } else {
                usleep(10000); // 10ms schlafen wenn keine Daten
            }
        }
        $this->SendDebug('GMA2 Telnet Response', $response, 0);

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
