<?php
require_once dirname(__FILE__) . "/LogBase.php";

class MoodleLog extends LogBase
{

    protected $db;
    protected $logs_table;
    protected $id_column;

    function __construct($log_path, $logs_table, $id_column) {
        global $DB;
        $this->db = $DB;
        $this->logs_table = $logs_table;
        $this->id_column = $id_column;
    }

    public function getLastAttempt($uid, $cid) {
        if ($lastattempt = $this->db->get_record($this->logs_table, array('userid' => $uid, $this->id_column => $cid), 'max(attempt) as a')) {
            if (empty($lastattempt->a)) {
                return 0;
            } else {
                return $lastattempt->a;
            }
        } else {
            return FALSE;
        }
    }

    public function existsResumeData($uid, $cid, $attempt) {
        if ( $activity_id = $this->getCurrentIDForResumption($uid, $cid, $attempt) ) {
            if ($rec = $this->db->get_record($this->logs_table, array('userid' => $uid, $this->id_column => $cid, 'attempt' => $attempt, 'name' => $activity_id, 'logkey' => 'isSuspend'), 'max(id) as id') ) {
                $suspend = $this->db->get_record($this->logs_table, array('id' => $rec->id));
                if ($suspend) {
                    if ($suspend->logvalue == 'true') {
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    public function getCurrentIDForResumption($uid, $cid, $attempt) {
        if ($rec = $this->db->get_record($this->logs_table, array('userid' => $uid, $this->id_column => $cid, 'attempt' => $attempt, 'name' => 'sys.ini', 'type' => '', 'logkey' => ''), 'max(id) as id') ) {
            $sysini = $this->db->get_record($this->logs_table, array('id' => $rec->id));
            if ($sysini) {
                return $sysini->logvalue;
            }
        }
        return FALSE;
    }

    public function saveCurrentIDForResumption($uid, $cid, $attempt, $current) {
        $this->db->delete_records($this->logs_table, array($this->id_column => $cid, 'userid' => $uid, 'attempt' => $attempt, 'name' => 'sys.ini', 'type' => '', 'logkey' => '' ));

        $sysini = new stdClass();
        $sysini->{$this->id_column} = $cid;
        $sysini->userid = $uid;
        $sysini->attempt = $attempt;
        $sysini->name = 'sys.ini';
        $sysini->type = '';
        $sysini->logkey = '';
        $sysini->logvalue = $current;
        return $this->db->insert_record($this->logs_table, $sysini);
    }

    public function saveCurrentIDForResumption_old($uid, $cid, $attempt, $current) {
        $sysini = $this->db->get_record($this->logs_table, array('userid' => $uid, $this->id_column => $cid, 'attempt' => $attempt, 'name' => 'sys.ini', 'type' => '', 'logkey' => ''));
        if ($sysini === FALSE) {
            $sysini = new stdClass();
            $sysini->{$this->id_column} = $cid;
            $sysini->userid = $uid;
            $sysini->attempt = $attempt;
            $sysini->name = 'sys.ini';
            $sysini->type = '';
            $sysini->logkey = '';
            $sysini->logvalue = '$current';
            return $this->db->insert_record($this->logs_table, $sysini);
        } else {
            $sysini->logvalue = $current;
            return $this->db->update_record($this->logs_table, $sysini);
        }
    }

    public function makeLogReady($uid, $cid, $attempt, $sgo) {
        return TRUE;
    }

    public function clearAttempts($uid, $cid) {
        return FALSE;
    }

    private static function makeSelect($key_values) {
        $select = '';
        foreach ($key_values as $key => $value) {
            if (strlen($select) > 0) {
                $select .= ' AND ';
            }
            if (is_array($value)) {
                $select .= $key . " IN ('" . implode("','", $value) . "')";
            } else {
                if (is_null($value)) {
                    $select .= "$key IS NULL";
                } else if (is_string($value)) {
                    $select .= "$key = '$value'";
                } else {
                    $select .= "$key = $value";
                }
            }
        }
        return $select;
    }

    /**
    /*
    /* readLog
    /*
    /* @param $ctx          lerner's current context.
    /* @param $activity_id  activity id
    /* @param $counter      attempt count of the activity
    /* @param $type         ROOT/BLOCK/LEAF
    /* @param $keys         key value pairs
    /* @param $global_to_system
    /* @return
    /*
    */
    public function readLog($ctx, $activity_id, $counter, $type, $keys, $global_to_system = FALSE) {
        $elecoaid = $ctx->getCid();
        $userid = $ctx->getUid();
        $attempt = $ctx->getAttemptCount();

        if ($type === 'Objective' && $global_to_system) {
            $rs = $this->db->get_records_select($this->logs_table, MoodleLog::makeSelect(array('userid' => $userid, 'scope' => 1, 'type' => $type, 'name' => $activity_id, 'counter' => $counter, 'logkey' => $keys)), null, 'id');
        } else {
            $rs = $this->db->get_records_select($this->logs_table, MoodleLog::makeSelect(array($this->id_column => $elecoaid, 'userid' => $userid, 'attempt' => $attempt, 'scope' => 0, 'type' => $type, 'name' => $activity_id, 'counter' => $counter, 'logkey' => $keys)), null, 'id');
        }
        if ($rs === FALSE) {
            return NULL;
        }
        $values = array();
        foreach($rs as $r) {
            $values[$r->logkey] = $r->logvalue;
        }
        return $values;
    }

    /**
    /*
    /* writeLog
    /*
    /* @param $ctx          lerner's current context.
    /* @param $activity_id  activity id
    /* @param $counter      attempt count of the activity
    /* @param $type         ROOT/BLOCK/LEAF/Objective
    /* @param $key_value_pairs key value pairs
    /* @param $global_to_system
    /* @return
    /*
    */
    public function writeLog($ctx, $activity_id, $counter, $type, $key_value_pairs, $global_to_system = FALSE) {
        global $CFG;
        $elecoaid = $ctx->getCid();
        $userid = $ctx->getUid();
        $attempt = $ctx->getAttemptCount();

        if ($type === 'Objective' && $global_to_system) {
            $rs = $this->db->get_records($this->logs_table, array('userid' => $userid, 'scope' => 1, 'type' => $type, 'name' => $activity_id, 'counter' => $counter), 'id');
        }
        else {
            $rs = $this->db->get_records($this->logs_table, array($this->id_column => $elecoaid, 'userid' => $userid, 'attempt' => $attempt, 'scope' => 0, 'type' => $type, 'name' => $activity_id, 'counter' => $counter), 'id');
        }

        $prev_records = array();
        foreach ($rs as $r) {
            $prev_records[$r->logkey] = $r;
        }

        $timestamp = time();
        $insert_sql = '';
        $update_sql = '';
        $ismysql = ($CFG->dbtype === 'mysql' or $CFG->dbtype === 'mysqli');
        foreach ($key_value_pairs as $key => $value) {
            $sql = '';
            if ($type === 'Objective' && $global_to_system) {
                $sql = $userid . ', 0, 0, 1, \'' . db_escape_string($activity_id) . '\', ' . ($counter ? $counter : 'NULL') . ', \'' . db_escape_string($type) . '\', \'' . db_escape_string($key) . '\', \'' . (is_null($value) ? '' : db_escape_string($value)) . '\', ' . $timestamp;
                /*
                $log = new stdClass();
                $log->userid = $userid;
                $log->scope = 1;
                $log->name = $activity_id;
                $log->counter = $counter;
                $log->type = $type;
                $log->logkey = $key;
                $log->logvalue = is_null($value) ? '' : $value;
                $log->timestamp = $timestamp;
                $this->db->insert_record($this->logs_table, $log);
                */
            }
            else {
                $sql = $userid . ', ' . $elecoaid . ', ' . $attempt . ', 0, \'' . db_escape_string($activity_id) . '\', ' . ($counter ? $counter : 'NULL') . ', \'' . db_escape_string($type) . '\', \'' . db_escape_string($key) . '\', \'' . (is_null($value) ? '' : db_escape_string($value)) . '\', ' . $timestamp;
                /*
                $log = new stdClass();
                $log->userid = $userid;
                $log->{$this->id_column} = $elecoaid;
                $log->attempt = $attempt;
                $log->scope = 0;
                $log->name = $activity_id;
                $log->counter = $counter;
                $log->type = $type;
                $log->logkey = $key;
                $log->logvalue =  is_null($value) ? '' : $value;
                $log->timestamp = $timestamp;
                $this->db->insert_record($this->logs_table, $log);
                */
            }
            if (!array_key_exists($key, $prev_records)) {
                // insert
                if ($insert_sql != '') {
                    $insert_sql .= ',';
                }
                $insert_sql .= '(' . $sql . ')';
            } else if ($prev_records[$key]->logvalue !== (string)$value) {
                // update
                if ($ismysql) {
                    if ($update_sql != '') {
                        $update_sql .= ',';
                    }
                    $update_sql .= '(' . $prev_records[$key]->id . ', ' . $sql . ')';
                } else {
                    $log = new stdClass();
                    $log->id = $prev_records[$key]->id;
                    $log->logvalue = is_null($value) ? '' : $value;
                    $log->timestamp = $timestamp;
                    $this->db->update_record($this->logs_table, $log);
                }
            }
        }
        if ($insert_sql !== '') {
            $insert_sql = 'INSERT INTO {' . $this->logs_table . '}(userid, ' . $this->id_column . ', attempt, scope, name, counter, type, logkey, logvalue, timestamp) VALUES' . $insert_sql;
            $this->db->execute($insert_sql);
        }
        if ($update_sql !== '' and $ismysql) {
            $update_sql = 'INSERT INTO {' . $this->logs_table . '}(id, userid, ' . $this->id_column . ', attempt, scope, name, counter, type, logkey, logvalue, timestamp) VALUES' . $update_sql . ' ON DUPLICATE KEY UPDATE logvalue=VALUES(logvalue),timestamp=VALUES(timestamp)';
            $this->db->execute($update_sql);
        }

        $delete_keys = array_diff(array_keys($prev_records), array_keys($key_value_pairs));
        foreach ($delete_keys as $key) {
            $this->db->delete_records($this->logs_table, array('id' => $prev_records[$key]->id));
        }

        return TRUE;
    }
}

