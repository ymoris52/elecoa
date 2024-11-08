<?php

class Platform {

    private static $instance;

    private $activities;
    private $objectives;

    private function __construct() {}

    public static function createInstance(&$activities, &$objectives) {
        self::$instance = new Platform();
        self::$instance->activities = &$activities;
        self::$instance->objectives = &$objectives;
    }

    public static function getInstance() {
        if (!self::$instance) throw new \Exception('エラー: createInstanceを呼び出さずにgetInstanceを呼び出しました');
        return self::$instance;
    }

    // finalにすることで子クラスから上書きできないようにする
    final function __clone() {
        throw new \Exception('Clone is not allowed against' . get_class($this));
    }

    /**
     * 親オブジェクトを返す。
     */
    public function getParent($parent) {
        if (!is_array($this->activities)) {
            return null;
        }
        if ($parent < 0) {
            return null;
        }
        if ($parent >= count($this->activities)) {
            return null;
        }

        return $this->activities[$parent];
    }

    public function searchAct($tmpID) {
        $actArray = $this->activities;
        foreach ($actArray as $tmpAct) {
            if ($tmpAct->getID() === $tmpID) {
                return $tmpAct;
            }
        }
        return NULL;
    }

    public function searchObjective($id) {
        $objArray = $this->objectives;
        foreach ($objArray as $tmpObj) {
            if ($tmpObj->getID() === $id) {
                return $tmpObj;
            }
        }
        return NULL;
    }

    /**
     * objective配列から$idx番目のobjectiveを返す
     * @param $idx
     * @return mixed
     */
    public function getObjective($idx) {
        return $this->objectives[$idx];
    }

    public function getActivity($idx) {
        return $this->activities[$idx];
    }

    public function getParticipantObjectives($cid, $uid, $objectiveId) {
        //$objective = $this->searchObjective($objectiveId);
        //$objectiveKeyValue = array('owner' => TRUE, 'userId' => $uid, 'objectiveId' => $objectiveId, 'objectiveValue' => $objective->getValue());
        global $DB, $CFG;
        $elecoa = $DB->get_record_select('elecoa', 'id = ?', array($cid));
        $context = $DB->get_record_select('context', 'instanceid = ? and contextlevel = 50', array($elecoa->course));
        $path_array = explode('/', $context->path);
        $course_contextid = array_pop($path_array);
        if ($CFG->dbtype == 'pgsql') {
	        $unix_timestamp = "extract('epoch' from CURRENT_TIMESTAMP)";
        } else {
	        $unix_timestamp = "UNIX_TIMESTAMP()";
        }
	    $sql = "SELECT DISTINCT u.id, u.username, el.name, el.logvalue FROM " . '%1$s' . "user u JOIN (SELECT DISTINCT eu1_u.id FROM " . '%1$s' . "user eu1_u JOIN " . '%1$s' . "user_enrolments eu1_ue ON eu1_ue.userid = eu1_u.id JOIN " . '%1$s' . "enrol eu1_e ON (eu1_e.id = eu1_ue.enrolid AND eu1_e.courseid = '" . '%2$s' . "') JOIN " . '%1$s' . "role_assignments eu1_ra ON eu1_u.id = eu1_ra.userid AND eu1_ra.contextid = '" . '%4$s' . "' AND eu1_ra.roleid = 5 WHERE eu1_u.deleted = 0 AND eu1_u.id <> '1' AND eu1_ue.status = '0' AND eu1_e.status = '0' AND eu1_ue.timestart < " . $unix_timestamp . " AND (eu1_ue.timeend = 0 OR eu1_ue.timeend > ". $unix_timestamp . ")) e ON e.id = u.id LEFT JOIN ". '%1$s' . "elecoa_logs el ON (el.userid = u.id AND el.type = 'Objective' AND el.elecoaid = '" . $cid . "' AND el.name = '" . '%3$s' . "')";
        $prefix = $DB->get_prefix();
        $participantObjectives = array();
        $values = $DB->get_records_sql(sprintf($sql, $prefix, $elecoa->course, $objectiveId, $course_contextid));
        foreach ($values as $value) {
            if ($value->id === $uid) {
                if ($value->name === $objectiveId) {
                    $participantObjectives[] = array('owner' => TRUE, 'userId' => $value->id, 'objectiveId' => $objectiveId, 'objectiveValue' => $value->logvalue);
                } else {
                    $participantObjectives[] = array('owner' => TRUE, 'userId' => $value->id, 'objectiveId' => $objectiveId, 'objectiveValue' => NULL);
                }
            } else {
                if ($value->name === $objectiveId) {
                    $participantObjectives[] = array('owner' => FALSE, 'userId' => $value->id, 'objectiveId' => $objectiveId, 'objectiveValue' => $value->logvalue);
                } else {
                    $participantObjectives[] = array('owner' => FALSE, 'userId' => $value->id, 'objectiveId' => $objectiveId, 'objectiveValue' => NULL);
                }
            }
        }
        return $participantObjectives;
    }

    public function getAppendedChildActivityCounts($cid, $uid, $activityId) {
        global $DB;
        $sql = "SELECT uid, count(uid) as c FROM " . '%1$s' . "elecoald_dynamic_manifest WHERE action = 1 AND cid = '" . '%2$s' . "' AND child = '" . '%3$s' . "' GROUP BY uid";
        $prefix = $DB->get_prefix();;
        $childCounts = array();
        $values = $DB->get_records_sql(sprintf($sql, $prefix, $cid, $activityId));
        $childCounts = array();
        foreach ($values as $value) {
            if ($uid === $value->uid) {
                $childCounts[] = array('owner' => TRUE, 'userId' => $value->uid, 'activityId' => $activityId, 'childCount' => intval($value->c));
            } else {
                $childCounts[] = array('owner' => FALSE, 'userId' => $value->uid, 'activityId' => $activityId, 'childCount' => intval($value->c));
            }
        }
        return $childCounts;
    }

    public function appendByManifest(ActivityBlock &$block, $manifest) {
        global $DB;
        $searchActivity = function ($strID, $activities) {
            $len = count($activities);
            for ($i=0; $i < $len; $i++) {
                if ($strID === $activities[$i]->getID()) {
                    return $i;
                }
            }
            return -1;
        };
        $actArray = &$this->activities;
        $objArray = &$this->objectives;
        $blockidx = $searchActivity($block->getID(), $actArray);
        if ($blockidx < 0) {
            return null;
        }
        $ctx = $actArray[$blockidx]->getContext();
        $doc = new DOMDocument();
        $doc->loadXML($manifest);
        $ogs = $doc->documentElement->getAttribute('oGS');
        if ($ogs === 'true') {
            $sgo = TRUE;
        } else {
            $sgo = FALSE;
        }
        $objectives = selectSingleDOMNode($doc->documentElement, 'objectives');
        if (!is_null($objectives)) {
            foreach (selectDOMNodes($objectives, 'objective') as $objective) {
                $objective_cotype = $objective->getAttribute('coType');
                $objective_id = $objective->getAttribute('id');
                $objArray[$objective_id] = new $objective_cotype($ctx, $objective_id, $objective, FALSE, $sgo);
            }
        }
        $item = selectSingleDOMNode($doc->documentElement, 'item');
        //$classname = $item->getAttribute('coType');
        $topid = $item->getAttribute('identifier');
        $lastNum = $blockidx;
        //$topidx = $searchIndex($actArray, $topid);
        //if ($topidx === -1) {
        //    $newobj = new $classname($ctx, $blockidx, $item, FALSE, $objArray);
        //    $actArray[] = $newobj;
        //    $lastNum = count($actArray) - 1;
        //    $actArray[$blockidx]->addChild($lastNum);
        //} else {
        //    $item = selectSingleNode($item, 'item');
        //    $lastNum = $topidx;
        //}
        $makeTree = function ($node, $parent, $res, &$context, &$actArray, &$objArray) use (&$makeTree, &$searchActivity, $topid) {
            $appendedTopIndex = -1;
            foreach (selectDOMNodes($node, 'item') as $item) {
                $classname = $item->getAttribute('coType');
                $id = $item->getAttribute('identifier');
                //$item->setAttribute('identifier', $topid . '-' . $id);
                $idx = $searchActivity($id, $actArray);
                if ($idx === -1) {
                    $item->setAttribute('identifier', $id);
                    $activity = new $classname($context, $parent, $item, $res, $objArray);
                    $actArray[] = $activity;
                    $lastNum = count($actArray) - 1;
                    $actArray[$parent]->addChild($lastNum);
                    $appendedTopIndex = $lastNum;
                } else {
                    $lastNum = $idx;
                }
                if ($actArray[$lastNum]->getType() === 'BLOCK') {
                    $resultIndex = $makeTree($item, $lastNum, $res, $context, $actArray, $objArray);
                    if ($appendedTopIndex === -1) {
                        $appendedTopIndex = $resultIndex;
                    }
                }
            }
            return $appendedTopIndex;
        };
        $appendedTopIndex = $makeTree($doc->documentElement, $lastNum, FALSE, $ctx, $actArray, $objArray);
        $data = new stdClass();
        $cid = $ctx->getCid();
        $data->cid = $cid;
        $data->uid = $ctx->getUid();
        $data->activity = $block->getID();
        $data->child = $topid;
        $data->action = 1; //追加
        $data->manifest = $manifest;
        $time = time();
        $data->created_at = $time;
        $DB->insert_record('elecoald_dynamic_manifest', $data);
        $_SESSION['elecoa_session']["elecoa_session_sync_${cid}"] = $time;
        $createdArray = array_slice($actArray, $lastNum);
        foreach ($createdArray as $obj) {
           $obj->callCommand('EVENT', 'APPENDED', null);
        }
        $newobj = $actArray[$appendedTopIndex];
        return $newobj;
    }

}
