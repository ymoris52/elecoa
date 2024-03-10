<?php

class CommandEntry {

    private $commands;
    private $currentActivity;

    public function __construct($activity) {
        $this->currentActivity = $activity;
        $this->commands = array();
    }

    public function callCommand($cmd, $val) {
        //if (!in_array($cmd, $this ->commands)) {
        //    return array('Return' => FALSE);
        //}
        $simpleCommands = array('INDEX', 'INIT', 'INITRTM', 'FINRTM', 'READY', 'GETVALUE', 'SETVALUE');
        if (!in_array($cmd, $simpleCommands)) {
            $this->currentActivity->callCommand('PREROLLUP', NULL, NULL);
            $this->currentActivity->callCommand('ROLLUP', NULL, NULL);
            $result = $this->currentActivity->callCommand('EXITCOND', array('command' => $cmd, 'value' => $val), NULL);
            if (!$result['Result']) {
                return $result;
            }
            if (isset($result['Value']) and isset($result['Value']['command'])) {
                $command = $result['Value']['command'];
                $value = $result['Value']['value'];
                $targetId = isset($result['Value']['activityId']) ? $result['Value']['activityId'] : NULL;
            } else {
                $command = $cmd;
                $value = $val;
                $targetId = NULL;
            }
            $retval = $this->currentActivity->callCommand($command, $value, $targetId);
            return $retval;
        } else {
            return $this->currentActivity->callCommand($cmd, $val, NULL);
        }
    }
}
