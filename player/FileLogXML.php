<?php
require_once dirname(__FILE__) . "/FileLog.php";

class FileLogXML extends FileLog
{
    public function writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system = FALSE) {
        $result = parent::writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system);
        if (!$result) {
            return FALSE;
        }
        $path = $this->log_dir($ctx, $type, $global_to_system);
        if (is_null($count)) {
            $log_file = $path . '/' . $activity_id . '-runtime.xml';
        } else {
            $log_file = $path . '/' . $activity_id . '.' . $count . '-runtime.xml';
        }
        $runtime_xml = NULL;
        foreach ($key_value_pairs as $key => $value) {
            if ($key == 'RTM') {
                if (preg_match('/^runtimeXML=(.+)$/m', $value, $matches)) {
                    $runtime_xml = $matches[1];
                    break;
                }
            } elseif ($key === 'runtimeXML') {
                $runtime_xml = $value;
                break;
            }
        }
        if (is_null($runtime_xml)) {
            return TRUE;
        }
        $dom = new DOMDocument();
        $dom->loadXML(rawurldecode($runtime_xml));
        $dom->formatOutput = true;
        $fp = fopen($log_file, "w");
        if (fwrite($fp, $dom->saveXML()) === FALSE) {
            fclose($fp);
            return FALSE;
        }else{
            fclose($fp);
            return TRUE;
        }
    }
}
