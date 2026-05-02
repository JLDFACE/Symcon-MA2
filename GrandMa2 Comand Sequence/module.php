<?php

class GrandMa2CommandSequence extends IPSModule
{
    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();

        $this->RegisterPropertyString('Commands', '[]');

        $this->ConnectParent('{BCB7DD65-6CBA-79F4-D330-A9A2CE06CDA7}');
    }

    // Überschreibt die interne IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();
    }

    public function ExecuteCommands() {
        $cmds = [];
        foreach (json_decode($this->ReadPropertyString('Commands'), true) as $item) {
            $cmd = trim($item['Command'] ?? '');
            if ($cmd !== '') {
                $cmds[] = $cmd;
            }
        }

        if (empty($cmds)) {
            $this->LogMessage('GMA2: Keine Kommandos konfiguriert', KL_WARNING);
            return;
        }

        $this->SendDebug('GMA2 ExecuteCommands', implode(', ', $cmds), 0);

        $this->SendDataToParent(json_encode([
            'DataID' => '{94053D1B-05E5-BF8B-36BC-480E198272D0}',
            'Buffer' => json_encode([
                'SequenceID' => $this->InstanceID,
                'Commands'   => $cmds,
            ]),
        ]));
    }

    // Empfangene Daten vom Parent – wird nicht genutzt
    public function ReceiveData($JSONString) {
    }
}
