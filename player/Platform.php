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

    private function get_instance_property($obj, $propName) {
        if (!is_object($obj) || !is_string($propName)) {
            throw new Exception();
        }
        $arr = (array)$obj;
        $keys = array(
             $propName
            ,"\0*\0" . $propName
            ,"\0" . get_class($obj) . "\0" . $propName
        );
        foreach ($keys as $key) {
            if (array_key_exists($key, $arr)) {
                return $arr[$key];
            }
        }
        return null;
    }

    public function getDebugString() {
        $result = '';
        $actArray = $this->activities;
        foreach ($actArray as $act) {
            $result .= $act->getID() . ", ";
            $seqParam = $this->get_instance_property($act, "seqParam");
            if (is_object($seqParam)) {
                $debugstring =  str_replace("\n", '', var_export($seqParam, true));
                $result .= str_replace(' ', '', $debugstring);
            }
            $result .= "\n\n";
        }
        return $result;
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
        $objective = $this->searchObjective($objectiveId);
        $objectiveKeyValue = array('owner' => TRUE, 'userId' => $uid, 'objectiveId' => $objectiveId, 'objectiveValue' => $objective->getValue());
        $isUser = function ($var) {
            return (strpos($var, 'user') === 0);
        };
        $users = array_filter(scandir(log_path . '/' . $cid), $isUser);
        $participantObjectives = array();
        foreach ($users as $user) {
            if ($user === $uid) {
                $participantObjectives[] = $objectiveKeyValue;
            } else {
                $file = log_path . '/' . $cid . '/'. $user . '/GO/' . $objectiveId . '.ini';
                if (is_readable($file) && filesize($file) > 0) {
                    $fileContent = file_get_contents($file);
                    $participantObjectives[] = array('owner' => FALSE, 'userId' => $user, 'objectiveId' => $objectiveId, 'objectiveValue' => substr($fileContent, strlen('value=')));
                } else {
                    $participantObjectives[] = array('owner' => FALSE, 'userId' => $user, 'objectiveId' => $objectiveId, 'objectiveValue' => NULL);
                }
            }
        }
        return $participantObjectives;
    }

    public function getAppendedChildActivityCounts($cid, $uid, $activityId) {
        global $DB;
        $sql = "SELECT uid, count(uid) as c FROM " . '%1$s' . "elecoald_dynamic_manifest WHERE action = 1 AND cid = '" . '%2$s' . "' AND child = '" . '%3$s' . "' GROUP BY uid";
        $prefix = "";
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