<?php
require_once dirname(__FILE__) . "/LogBase.php";

class FileLog extends LogBase
{

    protected $log_path;

    function __construct($log_path) {
        $this -> log_path = $log_path;
    }

    static function is_attempt($var) {
        return preg_match('/^\d+$/', $var);
    }

    public function getLastAttempt($uid, $cid) {
        $last_attempt = 0;
        $log_base = $this->log_base($uid, $cid);
        if (is_dir($log_base) and $a = scandir($log_base)) {
            $a = array_filter($a, 'FileLog::is_attempt');
            if (!sort($a, SORT_NUMERIC)) {
                return FALSE;
            }
            if (count($a) >= 1) {
                $last_attempt = array_pop($a);
            }
        }
        return $last_attempt;
    }

    public function existsResumeData($uid, $cid, $attempt) {
        $resume_file = $this->log_base($uid, $cid) . '/' . $attempt . '/sys.ini';
        return is_readable($resume_file) && filesize($resume_file) > 0;
    }

    public function getCurrentIDForResumption($uid, $cid, $attempt) {
        $log_base = $this->log_base($uid, $cid);
        $logDir = $log_base . '/' . $attempt;
        $sys_ini = $logDir . '/sys.ini';
        if (!file_exists($sys_ini)) {
            return FALSE;
        }
        return file_get_contents($sys_ini);
    }

    public function saveCurrentIDForResumption($uid, $cid, $attempt, $current) {
        $log_base = $this->log_base($uid, $cid);
        $logDir = $log_base . '/' . $attempt;
        $sys_ini = $logDir . '/sys.ini';
        $fp = fopen($sys_ini, "w");
        return fwrite($fp, $current);
    }

    public function makeLogReady($uid, $cid, $attempt, $sgo) {
        $log_base = $this->log_base($uid, $cid);
        $logDir = $log_base . '/' . $attempt;
        if (!file_exists($logDir) and !mkdir($logDir, 0755, TRUE)) {
            return FALSE;
        }
        if($sgo) {
            $objDir = $this->log_path . '/GO/' . urlencode($uid);
        } else {
            // FIX: OB-03b
            //$objDir = $logDir . '/GO';
            $objDir = $log_base . '/GO';
        }
        if (!file_exists($objDir) and !mkdir($objDir, 0755, TRUE)) {
            return FALSE;
        }
        return TRUE;
    }

    public function clearAttempts($uid, $cid) {
        $log_base = $this->log_base($uid, $cid);
        rmdir_r($log_base);
    }

    /**
    /*
    /* readLog
    /*
    /* @param $ctx          lerner's current context.
    /* @param $activity_id  activity id
    /* @param $count        attempt count of the activity
    /* @param $type         ROOT/BLOCK/LEAF
    /* @param $keys         key value pairs
    /* @param $global_to_system
    /* @return
    /*
    */
    public function readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system = FALSE) {
        $path = $this->log_dir($ctx, $type, $global_to_system);
        if (is_null($count)) {
            $log_file = $path . '/' . $activity_id . '.ini';
        } else {
            $log_file = $path . '/' . $activity_id . '.' . $count . '.ini';
        }
        $fp = @fopen($log_file, "r");
        if ($fp !== FALSE) {
            $lines = array();
            $lcount = 0;
            while (!feof($fp)) {
                if (!isset($keys[$lcount])) {
                    break;
                }
                $lines[$keys[$lcount]] = trim(fgets($fp));
                $lcount++;
            }
            if ($lcount > 0 && strpos($lines[$keys[0]], '=') === FALSE) {
                return $lines;
            } else {
                $values = array();
                foreach ($keys as $key) {
                    foreach ($lines as $line) {
                        $tmpLine = explode('=', $line);
                        if($tmpLine[1] != '') {
                            if ($key == $tmpLine[0]) {
                                $values[$key] = $tmpLine[1];
                            }
                        }
                    }
                }
                return $values;
            }
        } else {
            return null;
        }
    }

    public function writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system = FALSE) {
        $path = $this->log_dir($ctx, $type, $global_to_system);
        if (is_null($count)) {
            $log_file = $path . '/' . $activity_id . '.ini';
        } else {
            $log_file = $path . '/' . $activity_id . '.' . $count . '.ini';
        }
        $fp = fopen($log_file, "w");
        $save_data = '';
        foreach ($key_value_pairs as $key => $value) {
            if ($key == 'RTM') {
                $save_data .= $value . "\n";
            } else {
                $save_data .= $key . '=' . $value . "\n";
            }
        }
        $save_data = trim($save_data);
        if (fwrite($fp, $save_data) === FALSE) {
            fclose($fp);
            return FALSE;
        }else{
            fclose($fp);
            return TRUE;
        }
    }

    private function log_dir($ctx, $type, $global_to_system) {
        if ($type == 'Objective') {
            if ($global_to_system) {
                return $this->log_path . '/GO/' . urlencode($ctx->getUid());
            } else {
                //return $this->log_base($ctx->getUid(), $ctx->getCid()) . '/' . $ctx->getAttemptCount() . '/GO';
                // FIX: OB-03b
                return $this->log_base($ctx->getUid(), $ctx->getCid()) . '/GO';
            }
        } else {
            return $this->log_base($ctx->getUid(), $ctx->getCid()) . '/' . $ctx->getAttemptCount();
        }
    }

    // コンテンツ名 (格納ディレクトリ名) から、ログディレクトリ名 (アテンプト番号は含まない) を返す
    private function log_base($uid, $cid) {
        $log_base = $this->log_path . '/' . $cid . '/' . urlencode($uid);
        return $log_base;
    }
}
