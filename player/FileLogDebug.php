<?php
require_once dirname(__FILE__) . "/FileLog.php";

class FileLogDebug extends FileLog
{

    public function readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system = FALSE) {
        $this->debug('readLog(');
        $this->debug(is_null($ctx) ? 'null' : $ctx);
        $this->debug(', ');
        $this->debug($activity_id);
        $this->debug(', ');
        $this->debug($count);
        $this->debug(', ');
        $this->debug($type);
        $this->debug(', (');
        $this->debug(implode(',', $keys));
        $this->debug('), ');
        $this->debug($global_to_system ? 'true' : 'false');
        $this->debug(")\n");
        $result = parent::readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system);
        $this->debug($result);
        $this->debug("\n");
        $this->debug("readLog end\n\n");
        return $result;
    }

    public function writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system = FALSE) {
        $this->debug('writeLog(');
        $this->debug(is_null($ctx) ? 'null' : $ctx);
        $this->debug(', ');
        $this->debug($type);
        $this->debug(', ');
        $this->debug($activity_id);
        $this->debug(', (');
        $this->debug($count);
        $this->debug(', (');
        $this->debug($key_value_pairs);
        $this->debug('), ');
        $this->debug($global_to_system ? 'true' : 'false');
        $this->debug(")\n");
        $result = parent::writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system);
        $this->debug($result ? "true\n" : "false\n");
        $this->debug("writeLog end\n\n");
        return $result;
    }

    private function debug($value) {
        $debug_file = $this -> log_path . '/debug';
        $fp = fopen($debug_file, "a+");
        $data = '';
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (strlen($data) > 0) {
                    $data .= ', ';
                }
                $data .= $k . ' => ' . $v;
            }
        } else {
            $data = $value;
        }
        fwrite($fp, $data);
    }
}

