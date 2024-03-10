<?php
require_once dirname(__FILE__) . '/SimpleBlock.php';

class SCORMBlock extends SimpleBlock {
    private $seqParam;
    private $activeStatus;
    private $startTime;
    private $totalTime;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->addTable();
        $this->activeStatus = FALSE;
        $this->totalTime = 0;
        $this->startTime = NULL;

        $tmpSuccessStatus    = 'unknown';
        $tmpScaledScore      = '';
        $tmpCompletionStatus = 'unknown';
        $current = TRUE;

        if ($res) {
            $key_value_pairs = readLog($this->getContext(), $this->getID(), NULL, $this->getType(), array('current', 'isActive', 'isSuspend', 'attemptCount', 'successStatus', 'scaledScore', 'completionStatus', 'totalTime', 'runtimeXML'));
            if ((isset($key_value_pairs['isSuspend']) ? $key_value_pairs['isSuspend'] : '') == 'true') {
                $this->isSus = TRUE;
            } else {
                $this->isSus = FALSE;
            }
            if ((isset($key_value_pairs['isActive']) ? $key_value_pairs['isActive'] : '') == 'true') {
                $this->isActive = TRUE;
            } else {
                $this->isActive = FALSE;
            }
            if ((isset($key_value_pairs['current']) ? $key_value_pairs['current'] : '') == 'true') {
                $current = TRUE;
            } else {
                $current = FALSE;
            }
            $this->aCounter = intval(isset($key_value_pairs['attemptCount']) ? $key_value_pairs['attemptCount'] : $this->aCounter);
            $tmpSuccessStatus = isset($key_value_pairs['successStatus']) ? $key_value_pairs['successStatus'] : $tmpSuccessStatus;
            $tmpScaledScore = isset($key_value_pairs['scaledScore']) ? $key_value_pairs['scaledScore'] : $tmpScaledScore;
            $tmpCompletionStatus = isset($key_value_pairs['completionStatus']) ? $key_value_pairs['completionStatus'] : $tmpCompletionStatus;
            $this->totalTime = isset($key_value_pairs['totalTime']) ? $key_value_pairs['totalTime'] : $this->totalTime;
            $this->startTime = time();
        }
        $sNode = selectSingleNode($this->dataNode, 'imsss:sequencing');
        $this->seqParam = new SimpleSequencing($this->getID(), $sNode, $tmpSuccessStatus, $tmpScaledScore, $tmpCompletionStatus, $this->aCounter, $current, $objectives);
        $this->rollup->addWriteTargetObjectiveIdArray($this->seqParam->getWriteObjectiveIdArray());
    }

    private function addTable() {
        $this->cmdTableFromChild['INITS']         = array();
        $this->cmdTableFromChild['INITS']['Func'] = 'exeInitFromS';
        $this->cmdTableFromChild['INITS']['Type'] = 'cmd';
        $this->cmdTableFromChild['INITS']['View'] = FALSE;

        $this->cmdTableFromChild['INITAB']         = array();
        $this->cmdTableFromChild['INITAB']['Func'] = 'exeInitFromAB';
        $this->cmdTableFromChild['INITAB']['Type'] = 'cmd';
        $this->cmdTableFromChild['INITAB']['View'] = FALSE;

        $this->cmdTableFromChild['INITPB']         = array();
        $this->cmdTableFromChild['INITPB']['Func'] = 'exeInitFromPB';
        $this->cmdTableFromChild['INITPB']['Type'] = 'cmd';
        $this->cmdTableFromChild['INITPB']['View'] = FALSE;

        // 親からくる命令
        $this->cmdTableFromParent['INITC']            = array();
        $this->cmdTableFromParent['INITC']['Func']    = 'exeInitCurrent';
        $this->cmdTableFromParent['INITC']['Type']    = 'cmd';
    }

    private function setData($node) {
    }

    public function getStatus($str) {
        $tmpStatus = NULL;
        if ($str == 'successStatus') {
            $tmpStatus = $this->seqParam->getSuccessStatus(TRUE);
        } else if ($str == 'completionStatus') {
            $tmpStatus = $this->seqParam->getCompletionStatus();
        }
        return $tmpStatus;
    }

    private function set_node_value(&$parent_node, $node_name, $node_value) {
        $node = selectSingleNode($parent_node, $node_name);
        if ($node) {
            $node->nodeValue = $node_value;
        } else {
            $node = $parent_node->ownerDocument->createElement($node_name, $node_value);
            $parent_node->appendChild($node);
        }
    }

    public function terminate() {
        $this->co_trace();

        $success_status = $this->seqParam->getSuccessStatus(FALSE);
        $scaled_score = $this->seqParam->getScaledScore(FALSE);
        $completion_status = $this->seqParam->getCompletionStatus();

        $dom = new DOMDocument();
        $cmi_node = $dom->createElement('cmi');
        $dom->appendChild($cmi_node);
        $this->set_node_value($dom->documentElement, 'success_status', $success_status);
        $this->set_node_value($dom->documentElement, 'scaled_score', $scaled_score);
        $this->set_node_value($dom->documentElement, 'completion_status', $completion_status);
        $runtimeXML = $dom->saveXML();

        $data_array = array('isSuspend' => $this->isSus ? 'true' : 'false',
                            'isActive' => $this->activeStatus ? 'true' : 'false',
                            'current' => $this->seqParam->getCurrentStatus() ? 'true' : 'false',
                            'attemptCount' => $this->aCounter,
                            'successStatus' => $success_status,
                            'scaledScore' => $scaled_score,
                            'completionStatus' => $completion_status,
                            'totalTime' => $this->totalTime,
                            'runtimeXML' => rawurlencode($runtimeXML));
        return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array);
    }

    public function saveGrade($grademodule) {
        $len = count($this->children);
        for ($i=0; $i < $len; $i++) {
            $tmpAct = $this->getChild($i);
            $tmpAct->saveGrade($grademodule);
        }

        $grade = new stdClass();
        $grade->completionStatus = $this->seqParam->getCompletionStatus();
        $grade->successStatus = $this->seqParam->getSuccessStatus(TRUE);
        $grade->scaledScore = $this->seqParam->getScaledScore(TRUE);
        $grademodule->writeGrade($this->getContext(), $this->getID(), NULL, $this->getType(), $grade);
    }

    protected function startAttempt() {
        $this->co_trace();
        $this->isActive = TRUE;
        if (!$this->isSus) {
            $this->aCounter++;
            $this->seqParam->addAttemptCount();
            $this->totalTime = 0;
        }
        $this->startTime = time();
        $this->isSus = FALSE;
    }

    protected function getCommandList($val) {
        $this->co_trace();
        // コマンドリストの作成
        $postAry = array_clone($val);
        //foreach (array_keys($this->cmdTableFromChild) as $k) {
        //    if (!array_key_exists($k, $postAry)) {
        //        $postAry[$k]['Type'] = $this->cmdTableFromChild[$k]['Type'];
        //        $postAry[$k]['View'] = $this->cmdTableFromChild[$k]['View'];
        //    }
        //}
        return $postAry;
    }

    protected function exeInitFromS($id, $val) {
        $this->co_trace();
        $retArray = array();
        $tmpCmd = 'INITAB';
        if (!($this->isActive)) {
            $this->startAttempt();
            $tmpCmd = 'INITPB';
            $this->seqParam->setCurrentStatus(FALSE);

            $len = count($this->children);
            for ($i=0; $i < $len; $i++) {
                $tmpAct = $this->getChild($i);
                $tmpAct->callFromParent('INITC', 'ALL');
            }
        } else {
            $this->isSus = FALSE;
        }
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;
        $retArray['Command'] = $tmpCmd;
        $retArray['Value'] = $this->getCommandList($val);
        return $retArray;
    }

    protected function exeInitFromAB($id, $val) {
        $this->co_trace();
        $retArray = array();
        $tmpCmd = 'INITAB';
        if (!($this->isActive)) {
            $this->startAttempt();
            $this->seqParam->setCurrentStatus(FALSE);
        } else {
            $this->isSus = FALSE;
        }
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;
        $retArray['Command'] = $tmpCmd;
        $retArray['Value'] = $this->getCommandList($val);
        return $retArray;
    }

    protected function exeInitFromPB($id, $val) {
        $this->co_trace();
        $retArray = array();
        $tmpCmd = 'INITAB';
        if (!($this->isActive)) {
            $this->startAttempt();
            $tmpCmd = 'INITPB';
            $this->seqParam->setCurrentStatus(FALSE);

            $len = count($this->children);
            for ($i=0; $i < $len; $i++) {
                $tmpAct = $this->getChild($i);
                $tmpAct->callFromParent('INITC', 'SELF');
            }
        }

        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;
        $retArray['Command'] = $tmpCmd;
        $retArray['Value'] = $this->getCommandList($val);
        return $retArray;
    }

    protected function exeInitCurrent($val) {
        $this->co_trace();
        if (!($this->isActive)) {
            $this->seqParam->setCurrentStatus(TRUE);
            if ($val == 'ALL') {
                $len = count($this->children);
                for ($i=0; $i < $len; $i++) {
                    $tmpAct = $this->getChild($i);
                    $tmpAct->callFromParent('INITC', 'ALL');
                }
            }
        }
        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;
        return $retArray;
    }

    // in : 引数
    // out: 正常フラグ, 続行フラグ, 結果値
    protected function exeIndexP($val) {
        $this->co_trace();
        $resultArray = array(
            'type'               => $this->getType(),
            'title'              => $this->getTitle(),
            'id'                 => $this->getID(),
            'is_active'          => $this->isActive,
            'hidden_from_choice' => FALSE,
            'scaled_score'       => $this->seqParam->getScaledScore(TRUE),
            'success_status'     => $this->seqParam->getSuccessStatus(TRUE),
            'completion_status'  => $this->seqParam->getCompletionStatus(),
            'current_status'     => $this->seqParam->getCurrentStatus(),
            'children'           => array(),
        );

        $len = count($this->children);
        $continue = TRUE;
        if ($this->seqParam->checkPreCondition() == 'hiddenFromChoice') {
            $resultArray['hidden_from_choice'] = TRUE;
        }
        if ($this->seqParam->checkAttemptLimitExceeded()) {
            $resultArray['hidden_from_choice'] = TRUE;
        }

        for ($i = 0; $i < $len; $i++) {
            $tmpAct = $this->getChild($i);
            $retArray = $tmpAct->callFromParent('INDEX', $val);

            if (!$retArray['Result']) {
                // 目次で失敗はしない
            } else { // 子供の情報を追加
                $resultArray['children'][] = $retArray['Value'];
            }

            if (!$retArray['Continue']) {
                $continue = FALSE;
            }
        }

        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = $continue;
        $retArray['Value'] = $resultArray;
        return $retArray;
    }

    protected function exePreviousP($val) {
        $this->co_trace();
        // 親からのPreviousはFowardOnlyに注意
        if ($this->seqParam->getControlModeParam('forwardOnly')) {
            return parent::exeContinueP($val);
        } else {
            return parent::exePreviousP($val);
        }
    }

    private function exeMeasure_Rollup_Process($isCurrenetO) {
        $this->co_trace();
        $dblNumerator   = 0.0;      //分子
        $dblDenominator = 0.0;      //分母
        $valid_data = FALSE;

        $len = count($this->children);
        for ($i=0; $i < $len; $i++) {
            $tmpAct = $this->getChild($i);
            $deliveryControlsTracked = $tmpAct->callFromParent('GETVALUE', array('scorm.DeliveryControlsTracked'));
            if ($deliveryControlsTracked['Value']) {
                $dblDenominator += $tmpAct->callFromParent('GETVALUE', array('scorm.RollupObjectiveMeasureWeight'))['Value'];
                $tmpScore = $tmpAct->callFromParent('GETVALUE', array('scorm.PrimaryObjectiveMeasureEvaluateWeight', $isCurrenetO))['Value'];
                if ($tmpScore !== '') {
                    $dblNumerator += floatval($tmpScore);
                    $valid_data = TRUE;
                }
            }
        }
        if (!$valid_data) {
            $this->seqParam->setScaledScore('', TRUE);
        } else {
            if ($dblDenominator > 0) {
                $this->seqParam->setScaledScore($dblNumerator / $dblDenominator, TRUE);
            } else {
                $this->seqParam->setScaledScore('', TRUE);
            }
        }
    }

    private function exeRollup_Using_Rules($checkSatisfied, $isCurrenetO, $isCurrenetA) {
        $this->co_trace();
        $actions = $checkSatisfied ? array('satisfied', 'notSatisfied', 'incomplete', 'completed') : array('incomplete', 'completed');
        foreach ($actions as $str) {
            $tmpAry = $this->seqParam->getRollupRules($str);
            $len = count($tmpAry);
            for ($i=0; $i < $len; $i++) {
                $childActivitySet = $tmpAry[$i]['childActivitySet'];
                $aCnt = 0;
                $tCnt = 0;
                $fCnt = 0;
                $uCnt = 0;

                // 条件の洗い出し
                $tmpCondAry = $tmpAry[$i]['rollupConditions'];
                $tmpCondStr = $tmpAry[$i]['conditionCombination'];

                $clen = count($this->children);
                for ($j=0; $j < $clen; $j++) {
                    $condition = $this->getChild($j)->callFromParent('GETVALUE', array('scorm.CheckConditionForRollUp', $str, $tmpCondAry, $tmpCondStr, $isCurrenetO, $isCurrenetA))['Value'];
                    if (!is_null($condition)) {
                        $aCnt++;
                        if ($condition === 'true') {
                            $tCnt++;
                        }
                        if ($condition === 'unknown') {
                            $uCnt++;
                        }
                        if ($condition === 'false') {
                            $fCnt++;
                        }
                    }
                }

                $isResult = FALSE;
                if ($childActivitySet == 'all') {
                    if ($aCnt == $tCnt) { $isResult = TRUE; }
                } else if ($childActivitySet == 'any') {
                    if (0 < $tCnt) {
                        $isResult = TRUE;
                    }
                } else if ($childActivitySet == 'none') {
                    if ($aCnt == $fCnt) { $isResult = TRUE; }
                } else if ($childActivitySet == 'atLeastCount') {
                    if (intval($tmpAry[$i]['minimumCount']) <= $tCnt) {
                        $isResult = TRUE;
                    }
                } else if ($childActivitySet == 'atLeastPercent') {
                    if ($aCnt > 0) {
                        $dMin = floatval($tmpAry[$i]['minimumPercent']);
                        if (($tCnt / $aCnt) > $dMin) { $isResult = TRUE; }
                    }
                }

                if ($isResult) {
                    if ($str == 'satisfied' || $str == 'notSatisfied') {
$this->co_trace('setSuccessStatus, ' . $str);
                        $this->seqParam->setSuccessStatus($str, TRUE);
                    } else if ($str == 'completed' || $str == 'incomplete') {
$this->co_trace('setCompletionStatus, ' . $str);
                        $this->seqParam->setCompletionStatus($str);
                    }
                }
            }
        }
    }

    protected function exeRollUpMain() {
        $this->co_trace();

        // 自分自身のTRACKEDを調べる。
        if ($this->getDeliveryControlsTracked()) {
            $isCurrenetO = $this->seqParam->getControlModeParam('useCurrentAttemptObjectiveInfo');
            $isCurrenetA = $this->seqParam->getControlModeParam('useCurrentAttemptProgressInfo');

            $this->exeMeasure_Rollup_Process($isCurrenetO); // リーフではやらない

            $resultObj = FALSE;
            if ($this->seqParam->exeObjectiveRollupUsingMeasure($this->isActive)) {
                $resultObj = TRUE;
            }
            $this->exeRollup_Using_Rules(!$resultObj, $isCurrenetO, $isCurrenetA);
        }
    }

    protected function exeExitCond($id, $val) {
        $this->co_trace();
        $rst = $this->seqParam->checkExitCondition();
        $retArray = array();
        if ($rst) {
            $retStr = strtoupper($this->seqParam->checkPostCondition());
            if ($retStr != '') {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                $retArray['Value'] = array('command' => $retStr, 'value' => NULL, 'activityId' => $this->getID());
            } else {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                // FIX: T-01b
                $retArray['Value'] = array('command' => $val['command'], 'value' => NULL, 'activityId' => $this->getID());
            }
        } else {
            if (isset($val['command'])) {
                if ($val['command'] == 'EXITPARENT') {
                    $retStr = strtoupper($this->seqParam->checkPostCondition());
                    if ($retStr != '') {
                        $retArray['Result'] = TRUE;
                        $retArray['Continue'] = TRUE;
                        $retArray['Value'] = array('command' => $retStr, 'value' => NULL, 'activityId' => $this->getID());
                    } else {
                        $retArray['Result'] = TRUE;
                        $retArray['Continue'] = TRUE;
                        $retArray['Value'] = array('command' => 'EXITPARENT', 'value' => NULL, 'activityId' => $this->getID());
                    }
                } else {
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = TRUE;
                    $retArray['Value'] = $val;
                }
            } else {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                $retArray['Value'] = $val;
            }
        }
        return $retArray;
    }

    protected function checkPreCondition($cmd, $id, $val, $isDescending) {
        $this->co_trace();
        $rst = $this->makeCheckResult('', '');
        // まずはコマンドのチェック
        if ($cmd === 'CHOICE') {
            if ($this->getID() === $val) {
                if (!$this->getParent()->callFromChild($this->getID(), 'GETVALUE', array('scorm.ControlMode', 'choice'), NULL)['Value']) {
                    return $this->makeCheckResult('error', 'SB.2.9-4');
                }
            } else {
                if ($this->hasChild($val)) {
                    if (!$this->getControlMode('choice')) {
                        return $this->makeCheckResult('error', 'SB.2.9-4');
                    }
                    if ($this->getControlMode('forwardOnly')) {
                        if ($this->checkSelectedActivityDirection($val, $id) === 'Backward') {
                            return $this->makeCheckResult('error', 'SB.2.4-2');
                        }
                    }
                    if ($this->seqParam->getConstrainedChoiceConsiderationsParam('preventActivation')) {
                        return $this->makeCheckResult('error', 'SB.2.9-6');
                    }
                } else if ($this->seqParam->getConstrainedChoiceConsiderationsParam('constrainChoice') and !is_null($id)) {
                    $parent = $this->getParent();
                    // get "next" activity
                    $idx = $parent->getChildPosition($this->getID());
                    if ($idx + 1 < count($parent->children)) {
                        $next = $parent->getChild($idx + 1);
                        if ($next->getType() === 'LEAF' and $next->getID() !== $val) {
                            return $this->makeCheckResult('error', 'SB.2.9-8');
                        }
                        if ($next->getType() === 'BLOCK') {
                            if (!$next->hasDescendant($val)) {
                                return $this->makeCheckResult('error', 'SB.2.9-8');
                            }
                        }
                    }
                }
            }
        } else if (($cmd === 'CONTINUE') || ($cmd === 'PREVIOUS')) {
            if (!$this->getControlMode('flow')) {
                $rst = $this->makeCheckResult('error',  $cmd === 'CONTINUE' ? 'SB.2.7-2' : 'SB.2.8-2');
            }
            if ($cmd === 'PREVIOUS' and $this->getControlMode('forwardOnly')) {
                $rst = $this->makeCheckResult('error', 'SB.2.4-2');
            }
        }
        // 次に状態のチェック
        if ($isDescending) {
            $rst = $this->makeCheckResult($this->seqParam->checkPreCondition(), '');
        }
        if ($rst['Result'] == 'stopForwardTraversal') {
            $rst = $this->makeCheckResult('', '');
        }
        if ($rst['Result'] == 'hiddenFromChoice' and $cmd == 'CHOICE') {
            $rst = $this->makeCheckResult('error', 'SB.2.9-3');
        }
        return $rst;
    }

    private function getControlMode($param) {
        return $this->seqParam->getControlModeParam($param);
    }

    private function hasChild($id) {
        $len = count($this->children);
        for ($i = 0; $i < $len; $i++) {
            if ($this->getChild($i)->getID() === $id) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function hasDescendant($id) {
        $len = count($this->children);
        $found = FALSE;
        for ($i = 0; $i < $len; $i++) {
            $child = $this->getChild($i);
            if ($child->getID() === $id) {
                $found = TRUE;
                break;
            }
            if ($child->getType() === 'BLOCK') {
                $found = $child->hasDescendant($id);
                if ($found) {
                    break;
                }
            }
        }
        return $found;
    }

    private function checkSelectedActivityDirection($id_selected, $id_current) {
        $idx_selected = $this->getChildPosition($id_selected);
        $idx_current  = $this->getChildPosition($id_current);
        if ($idx_selected > -1 and $idx_current > -1) {
            if ($idx_selected > $idx_current) {
                return 'Forward';
            }
            if ($idx_selected < $idx_current) {
                return 'Backward';
            }
            if ($idx_selected == $idx_current) {
                return '';
            }
        } else {
            return 'Unknown';
        }
    }

    // 終了処理。bool値を返す
    protected function endAttempt($cmd) {
        $this->co_trace();
        if ($cmd === 'INITS') {
            return FALSE;
        }
        if (in_array($cmd, array('EXITCOND', 'ROLLUP', 'PREROLLUP', 'INDEX', 'INIT', 'INITRTM', 'FINRTM', 'READY', 'GETVALUE', 'SETVALUE'))) {
            return FALSE;
        }
        if ($cmd === 'SUSPEND') {
            $this->activeStatus = $this->isActive;
        }
        if ($this->isActive) {
            // まずは自分自身の終了
            $this->isActive = FALSE;
            // 子供に中断があるかどうか
            $len = count($this->children);
            for ($i=0; $i < $len; $i++) {
                $tmpAct = $this->getChild($i);
                if ($tmpAct->isSuspend()) {
                    $this->isSus = TRUE;
                    break;
                }
            }

            //$this->exeRollUpStart();
            $this->seqParam->fixPendingStatus();
            $tmpArray['Result'] = TRUE;
            $tmpArray['Command']  = '';
            $tmpArray['Value']  = '';

            if ($cmd == 'CHOICE') {
                if (!$this->seqParam->getControlModeParam('choiceExit')) {
                    $tmpArray['Result'] = FALSE;
                    $tmpArray['Command'] = '';
                    $tmpArray['Value'] = '';
                    $tmpArray['Error'] = 'NB.2.1-8';
                }
            }

            $session_time = time() - $this->startTime;
            $this->totalTime += $session_time;
            $grademodule = $this->getGradeModule();
            if ($grademodule) {
                // 成績一覧用のデータ保存
                $gradedata = new stdClass();
                $gradedata->completionStatus = $this->seqParam->getCompletionStatus();
                $gradedata->successStatus = $this->seqParam->getSuccessStatus(TRUE);
                $gradedata->scaledScore = $this->seqParam->getScaledScore(TRUE);
                $gradedata->sessionTime = $session_time;
                $gradedata->totalTime = $this->totalTime;
                $grademodule->writeGrade($this->getContext(), $this->getID(), $this->aCounter, $this->getType(), $gradedata);
            }

            return $tmpArray;
        } else {
            $tmpArray['Result'] = TRUE;
            $tmpArray['Command']  = '';
            $tmpArray['Value']  = '';
            return $tmpArray;
        }
    }

    protected function exeGetValue($id, $val) {
        $value = NULL;
        if ($val[0] === 'scorm.ControlMode') {
            $value = $this->getControlMode($val[1]);
        }
        return array('Value' => $value);
    }

    protected function exeGetValueP($params) {
        $this->co_trace();
        $value = NULL;
        // ロールアップ用の状態取得関数群
        if ($params[0] === 'scorm.DeliveryControlsTracked') {
            $value = $this->getDeliveryControlsTracked();
        }
        if ($params[0] === 'scorm.RollupObjectiveMeasureWeight') {
            $value = $this->getRollupObjectiveMeasureWeight();
        }
        if ($params[0] === 'scorm.PrimaryObjectiveMeasureEvaluateWeight') {
            $value = $this->getPrimaryObjectiveMeasureEvaluateWeight($params[1]);
        }
        if ($params[0] === 'scorm.CheckConditionForRollUp') {
            $value = $this->checkConditionForRollUp($params[1], $params[2], $params[3], $params[4], $params[5]);
        }
        return array('Value' => $value);
    }

    private function getDeliveryControlsTracked() {
        return $this->seqParam->getDeliveryControlsParam('tracked');
    }

    private function getRollupObjectiveMeasureWeight() {
        return $this->seqParam->getRollupObjectiveMeasureWeight();
    }

    private function getPrimaryObjectiveMeasureEvaluateWeight($isCurrent) {
        // 重さｘ得点率
        $retVal = $this->seqParam->getScaledScoreForRR($isCurrent);
        if ($retVal != "") {
            $weight = $this->getRollupObjectiveMeasureWeight();
            $retVal= floatval($retVal) * $weight;
        }
        return $retVal;
    }

    private function checkConditionForRollUp($str, $condAry, $condC, $isCurrentO, $isCurrentA) {
        $result = NULL;
        $deliveryControlsTracked = $this->seqParam->getDeliveryControlsParam('tracked');
        if ($deliveryControlsTracked) {
            if ($this->seqParam->checkChildForRollUp($this->aCounter, $this->isSus, $str)) {
                $retNum = $this->seqParam->checkStatusForRollUp($this->aCounter, $condAry, $condC, $isCurrentO, $isCurrentA);
                if ($retNum == 1) {        // true
                    $result = 'true';
                } else if($retNum == 0) {  // unknown
                    $result = 'unknown';
                } else if($retNum == -1) { // false
                    $result = 'false';
                }
            }
        }
        return $result;
    }

    public function ignoreTrace() {
        $this->ignoreTrace = TRUE;
        $this->seqParam->ignoreTrace();
    }
}
