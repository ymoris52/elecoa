<?php
// 教材オブジェクトで使用するプラットフォーム依存の関数
class colib {

    public static function all_users($runID) {
        global $DB;
        $elecoa = $DB->get_record(ELECOA_TABLE, array('id' => $runID), '*', MUST_EXIST);
        $sql = "SELECT u.id, u.username FROM " . '%1$s' . "user u JOIN (SELECT DISTINCT eu1_u.id FROM " . '%1$s' . "user eu1_u JOIN " . '%1$s' . "user_enrolments eu1_ue ON eu1_ue.userid = eu1_u.id JOIN " . '%1$s' . "enrol eu1_e ON (eu1_e.id = eu1_ue.enrolid AND eu1_e.courseid = '" . '%2$s' . "') WHERE eu1_u.deleted = 0 AND eu1_u.id <> '1' AND eu1_ue.status = '0' AND eu1_e.status = '0' AND eu1_ue.timestart < UNIX_TIMESTAMP() AND (eu1_ue.timeend = 0 OR eu1_ue.timeend > UNIX_TIMESTAMP())) e ON e.id = u.id";
        $prefix = $DB->get_prefix();
        $users = $DB->get_records_sql(sprintf($sql, $prefix, $elecoa->course));
        $list = array();
        foreach ($users as $user) {
            $list[] = array('id' => $user->id, 'name' => $user->username);
        }
        return $list;
    }

    public static function isAdmin($runID, $userID) {
        global $DB;
        $elecoa = $DB->get_record_select('elecoa', 'id = ?', array($runID));
        $rs = $DB->get_records_select('context', 'instanceid = ?', array($elecoa->coursemodule));
        foreach ($rs as $row) {
            $ra = $DB->get_records_select('role_assignments', 'contextid = ? and userid = ? and roleid = 3', array($row->id, $userID));
            foreach ($ra as $a) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public static function isTeacher($runID, $userID) {
        global $DB;
        $elecoa = $DB->get_record_select('elecoa', 'id = ?', array($runID));
        $rs = $DB->get_records_select('context', 'instanceid = ?', array($elecoa->coursemodule));
        foreach ($rs as $row) {
            $ra = $DB->get_records_select('role_assignments', 'contextid = ? and userid = ? and roleid = 4', array($row->id, $userID));
            foreach ($ra as $a) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public static function appendChildNode($node, $id, $href, $title, $classname, &$actArray, &$objArray) {
        $item = $node->ownerDocument->createElement('item');
        $item->setAttribute('identifier', $id);
        $item->setAttribute('href', $href);
        $item->setAttribute('coType', $classname);
        $t = $item->ownerDocument->createElement('title');
        $t->nodeValue = $title;
        $item->appendChild($t);
        $node->appendChild($item);
    }

    public static function appendChildActivity(ActivityBlock &$block, $id, $href, $title, $classname, &$actArray, &$objArray) {
        $blockidx = colib::searchActivity($block->getID(), $actArray);
        if ($blockidx < 0) {
            return null;
        }
        $ctx = $actArray[$blockidx]->getContext();
        $doc = new DOMDocument();
        $doc->loadXML('<item identifier="' . $id . '" href="' . $href . '"><title>' . $title . '</title></item>');
        $node = $doc->documentElement;
        $newobj = new $classname($ctx, $blockidx, $node, FALSE, $objArray);
        $actArray[] = $newobj;
        $lastNum = count($actArray) - 1;
        $actArray[$blockidx]->addChild($lastNum);
        $makeTree = function ($node, $parent, $res, &$context, &$actArray, &$objArray) use (&$makeTree) {
            foreach (selectDOMNodes($node, 'item') as $item) {
                $classname = $item->getAttribute('coType');
                $actArray[] = new $classname($context, $parent, $item, $res, $objArray);
                $lastNum = count($actArray) - 1;
                $actArray[$parent]->addChild($lastNum);
                if ($actArray[$lastNum]->getType() === 'BLOCK') {
                    $makeTree($item, $lastNum, $res, $context, $actArray, $objArray);
                }
            }
        };
        $makeTree($node, $lastNum, FALSE, $ctx, $actArray, $objArray);
        return $newobj;
    }

    private static function appendByStoredManifest($userId, $activityId, $manifest, &$actArray, &$objArray) {
        $blockidx = colib::searchActivity($activityId, $actArray);
        if ($blockidx < 0) {
            return null;
        }
        $ctx = $actArray[$blockidx]->getContext();
        $doc = new DOMDocument();
        $doc->loadXML($manifest);
        $ogs = $doc->documentElement->getAttribute('oGS');
        $sgo =  $ogs === 'true';
        $objectives = selectSingleDOMNode($doc->documentElement, 'objectives');
        if (!is_null($objectives)) {
            foreach (selectDOMNodes($objectives, 'objective') as $objective) {
                $objective_cotype = $objective->getAttribute('coType');
                $objective_id = $objective->getAttribute('id');
                $objArray[$objective_id] = new $objective_cotype($ctx, $objective_id, $objective, FALSE, $sgo);
            }
        }
        //$item = selectSingleNode($doc->documentElement, 'item');
        //$classname = $item->getAttribute('coType');
        //$topid = $item->getAttribute('identifier');
        $uid = $ctx->getUid();
        //$newobj = new $classname($ctx, $blockidx, $item, FALSE, $objArray);
        //$actArray[] = $newobj;
        //$lastNum = count($actArray) - 1;
        //$actArray[$blockidx]->addChild($lastNum);
        $searchActivity = function ($activityId, $actArray) {
            return colib::searchActivity($activityId, $actArray);
        };
        $makeTree = function ($node, $parent, $res, &$context, &$actArray, &$objArray) use (&$makeTree, &$searchActivity, $uid) {
            foreach (selectDOMNodes($node, 'item') as $item) {
                $classname = $item->getAttribute('coType');
                $id = $item->getAttribute('identifier');
                //$item->setAttribute('identifier', $topid . '-' . $id);
                $idx = $searchActivity($id, $actArray);
                if ($idx === -1) {
                    $actArray[] = new $classname($context, $parent, $item, $res, $objArray);
                    $lastNum = count($actArray) - 1;
                    $actArray[$parent]->addChild($lastNum);
                } else {
                    $lastNum = $idx;
                }
                if ($actArray[$lastNum]->getType() === 'BLOCK') {
                    $makeTree($item, $lastNum, $res, $context, $actArray, $objArray);
                }
            }
        };
        $makeTree($doc->documentElement, $blockidx, FALSE, $ctx, $actArray, $objArray);
    }

    public static function clear_session_data($cid) {
        unset($_SESSION['elecoa_session']["elecoa_session_sync_${cid}"]);
    }

    public static function dynamicAppend(&$ctx, &$actArray, &$objArray) {
        global $DB;
        if (TRUE) {
            $recent = null;
            $cid = $ctx->getCid();
            if (isset($_SESSION['elecoa_session']["elecoa_session_sync_${cid}"])) {
                $recent = $_SESSION['elecoa_session']["elecoa_session_sync_${cid}"];
            }
            $where_clause = "where cid = '$cid' and action = 1";
            if (!is_null($recent)) {
                $where_clause .= " and created_at > $recent";
            }
            $records = $DB->get_records_sql('select * from {elecoald_dynamic_manifest} ' . $where_clause . ' order by id');
            foreach ($records as $record) {
                colib::appendByStoredManifest($record->uid, $record->activity, $record->manifest, $actArray, $objArray);
                $recent = $record->created_at;
            }
            $_SESSION['elecoa_session']["elecoa_session_sync_${cid}"] = $recent;
        }
    }

    public static function checkIsSameGroupingGroup($ownerId, $userId, $contentId, $groupingName) {
        $owner_ctx = makeContext($ownerId, $contentId, 1);
        $owner_log = readLog($owner_ctx, $groupingName, null, 'Objective', array('value'), FALSE);
        $user_ctx = makeContext($userId, $contentId, 1);
        $user_log = readLog($user_ctx, $groupingName, null, 'Objective', array('value'), FALSE);
        if (!is_null($user_log) and !is_null($owner_log)) {
            if ($owner_log['value'] === $user_log['value']) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            if (is_null($owner_log)) {
                return FALSE;
            } else {
                $platform = Platform::getInstance();
                $objective = $platform->searchObjective($groupingName);
                if (is_null($objective)) {
                    if ($owner_log['value'] === $objective->getValue()) {
                        return TRUE;
                    } else {
                        return FALSE;
                    }
                } else {
                    return FALSE;
                }
            }
        }
    }

    private static function searchActivity($strID, $activities) {
        $len = count($activities);
        for ($i=0; $i < $len; $i++) {
            if ($strID === $activities[$i]->getID()) {
                return $i;
            }
        }
        return -1;
    }

    public static function appendObjective(ActivityBase &$activity, $id, $classname, &$objArray) {
        $ctx = $activity->getContext();
        $objArray[$id] = new $classname($ctx, $id, FALSE, FALSE);
    }
}
?>
