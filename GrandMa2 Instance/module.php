<?php

class GrandMa2Instance extends IPSModule
{
    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();

        $this->RegisterPropertyString('ServerAddress', '');
        $this->RegisterPropertyString('ServerPort', '80');

        $this->RegisterVariableInteger('LastSequenceExecuted', 'Zuletzt ausgeführte Sequenz', '', 1);
        $this->RegisterVariableString('LastStatus', 'Letzter Status', '', 2);
    }

    // Überschreibt die interne IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        if (empty(trim($this->ReadPropertyString('ServerAddress')))) {
            $this->SetStatus(201); // Keine Adresse konfiguriert
        } else {
            $this->SetStatus(102); // Aktiv
        }
    }

    public function TestConnection() {
        $address = $this->ReadPropertyString('ServerAddress');
        $port    = $this->ReadPropertyString('ServerPort');

        if (empty(trim($address))) {
            echo 'Fehler: Keine Server-Adresse konfiguriert.';
            $this->SetStatus(201);
            return false;
        }

        $url = 'http://' . $address . ':' . $port . '/execute';
        $h = curl_init($url);
        curl_setopt($h, CURLOPT_CONNECT_ONLY, true);
        curl_setopt($h, CURLOPT_CONNECTTIMEOUT, 5);
        $result    = curl_exec($h);
        $curlError = curl_error($h);
        curl_close($h);

        if ($result === false || !empty($curlError)) {
            $msg = 'Server nicht erreichbar: ' . ($curlError ?: 'Unbekannter Fehler');
            $this->LogMessage('GMA2: ' . $msg, KL_ERROR);
            $this->SetValueIfChanged('LastStatus', $msg);
            $this->SetStatus(200);
            echo $msg;
            return false;
        }

        $msg = 'Server erreichbar (' . $address . ':' . $port . ')';
        $this->SetValueIfChanged('LastStatus', $msg);
        $this->SetStatus(102);
        echo $msg;
        return true;
    }

    public function Execute(array $commands) {
        $address = $this->ReadPropertyString('ServerAddress');
        $port    = $this->ReadPropertyString('ServerPort');

        if (empty(trim($address))) {
            $this->LogMessage('GMA2: Keine Server-Adresse konfiguriert', KL_ERROR);
            $this->SetStatus(201);
            return false;
        }

        $url     = 'http://' . $address . ':' . $port . '/execute';
        $payload = json_encode(['commands' => $commands]);

        $this->SendDebug('GMA2 Execute URL', $url, 0);
        $this->SendDebug('GMA2 Execute Payload', $payload, 0);

        $h = curl_init($url);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($h, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($h, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($h, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($h, CURLOPT_TIMEOUT, 10);

        $response  = curl_exec($h);
        $curlError = curl_error($h);
        $httpCode  = curl_getinfo($h, CURLINFO_HTTP_CODE);
        curl_close($h);

        if ($response === false || !empty($curlError)) {
            $msg = 'Server nicht erreichbar: ' . ($curlError ?: 'Unbekannter Fehler');
            $this->LogMessage('GMA2: ' . $msg, KL_ERROR);
            $this->SetValueIfChanged('LastStatus', $msg);
            $this->SetStatus(200);
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = 'HTTP Fehler ' . $httpCode . ' – Antwort: ' . $response;
            $this->LogMessage('GMA2: ' . $msg, KL_WARNING);
            $this->SetValueIfChanged('LastStatus', $msg);
            $this->SetStatus(200);
            return false;
        }

        $this->SendDebug('GMA2 Execute Response', $response, 0);
        $this->SetValueIfChanged('LastStatus', 'OK (HTTP ' . $httpCode . ')');
        $this->SetStatus(102);
        return true;
    }

    // Daten vom Child empfangen
    public function ForwardData($JSONString) {
        $data = json_decode($JSONString, true);

        if (($data['DataID'] ?? '') === '{94053D1B-05E5-BF8B-36BC-480E198272D0}') {
            $buf      = json_decode($data['Buffer'], true);
            $commands = $buf['Commands'] ?? [];
            $seqId    = $buf['SequenceID'] ?? 0;

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
