<?php
require_once dirname(__FILE__) . '/SimpleRoot.php';

class SCORMRoot extends SimpleRoot {
    private $seqParam;
    private $totalTime;
    private $sessionTime;
    private $startTime;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->addTable();

        $tmpSuccessStatus    = 'unknown';
        $tmpScaledScore      = '';
        $tmpCompletionStatus = 'unknown';
        $current = TRUE;

        $this->totalTime     = 'PT0H0M0S';
        $this->sessionTime   = NULL;

        if ($res) {
            $key_value_pairs = readLog($this->getContext(), $this->getID(), NULL, $this->getType(), array('current', 'isSuspend', 'attemptCount', 'successStatus', 'scaledScore', 'completionStatus', 'runtimeXML'));
            if ((isset($key_value_pairs['isSuspend']) ? $key_value_pairs['isSuspend'] : '') == 'true') {
                $this->isSus = TRUE;
            } else {
                $this->isSus = FALSE;
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
        }
        $sNode = selectSingleNode($this->dataNode, 'imsss:sequencing');
        $this->seqParam = new SimpleSequencing($this->getID(), $sNode, $tmpSuccessStatus, $tmpScaledScore, $tmpCompletionStatus, $this->aCounter, $current, $objectives);
        $this->rollup->addWriteTargetObjectiveIdArray($this->seqParam->getWriteObjectiveIdArray());
    }

    private function addTable() {
        $this->cmdTableFromChild['INITS']     = array();
        $this->cmdTableFromChild['INITS']['Func'] = 'exeInitAll';
        $this->cmdTableFromChild['INITS']['Type'] = 'cmd';
        $this->cmdTableFromChild['INITS']['View'] = FALSE;

        $this->cmdTableFromChild['INITAB']     = array();
        $this->cmdTableFromChild['INITAB']['Func'] = 'exeInitAll';
        $this->cmdTableFromChild['INITAB']['Type'] = 'cmd';
        $this->cmdTableFromChild['INITAB']['View'] = FALSE;

        $this->cmdTableFromChild['INITPB']     = array();
        $this->cmdTableFromChild['INITPB']['Func'] = 'exeInitAll';
        $this->cmdTableFromChild['INITPB']['Type'] = 'cmd';
        $this->cmdTableFromChild['INITPB']['View'] = FALSE;
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
        $node = selectSingleDOMNode($parent_node, $node_name);
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
                            'current' => $this->seqParam->getCurrentStatus() ? 'true' : 'false',
                            'attemptCount' => $this->aCounter,
                            'successStatus' => $success_status,
                            'scaledScore' => $scaled_score,
                            'completionStatus' => $completion_status,
                            'runtimeXML' => rawurlencode($runtimeXML));

        return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array);
    }

    public function saveGrade($grademodule) {
        $len = count($this->children);
        for ($i=0; $i < $len; $i++) {
            $tmpAct = $this->getChild($i);
            if (method_exists($tmpAct, 'saveGrade')) {
                $tmpAct->saveGrade($grademodule);
            }
        }

        $grade = new stdClass();
        $grade->completionStatus = $this->seqParam->getCompletionStatus();
        $grade->successStatus = $this->seqParam->getSuccessStatus(TRUE);
        $grade->scaledScore = $this->seqParam->getScaledScore(TRUE);
        $grade->sessionTime = $this->sessionTime;
        $grade->totalTime = $this->totalTime;
        $grademodule->writeGrade($this->getContext(), $this->getID(), NULL, $this->getType(), $grade);
    }

    protected function startAttempt() {
        $this->co_trace();
        $this->isActive = TRUE;
        if (!$this->isSus) {
            $this->aCounter++;
            $this->seqParam->addAttemptCount();
        }
        $this->sessionTime = NULL;
        $this->startTime = time();
        $this->isSus = FALSE;
    }

    private function getCommandList($val) {
        // コマンドリストの作成
        $postAry = array_clone($val);
        foreach (array_keys($this->cmdTableFromChild) as $k) {
            if (!array_key_exists($k, $postAry)) {
                if (isset($this->cmdTableFromChild[$k]['Type'])) {
                    $postAry[$k]['Type'] = $this->cmdTableFromChild[$k]['Type'];
                }
                if (isset($this->cmdTableFromChild[$k]['View'])) {
                    $postAry[$k]['View'] = $this->cmdTableFromChild[$k]['View'];
                }
            }
        }
        return $postAry;
    }

    protected function exeInitAll($id, $val) {
        $this->co_trace();
        $retArray = array();
        if (!($this->isActive)) {
            $this->startAttempt();
            $this->seqParam->setCurrentStatus(FALSE);
        }
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = FALSE;
        $retArray['Value'] = $this->getCommandList($val);
        return $retArray;
    }

    //  exeIndex
    protected function exeIndex($id,$val){
        $this->co_trace();
        $resultArray = array(
            'type'               => $this->getType(),
            'title'              => $this->getTitle(),
            'id'                 => $this->getID(),
            'is_active'          => $this->isActive,
            'disabled'           => FALSE,
            'hidden_from_choice' => FALSE,
            'scaled_score'       => $this->seqParam->getScaledScore(TRUE),
            'success_status'     => $this->seqParam->getSuccessStatus(TRUE),
            'completion_status'  => $this->seqParam->getCompletionStatus(),
            'current_status'     => $this->seqParam->getCurrentStatus(),
            'children'           => array(),
        );

        $len = count($this->children);
        if ($this->seqParam->checkPreCondition() === 'hiddenFromChoice') {
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

            if (isset($retArray['Continue']) and !$retArray['Continue']) {
                // choiceExit = false のため、choice を無効にする
                $resultArray['disabled'] = TRUE;
            }
        }

        // 自分自身の追加

        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = FALSE;
        $retArray['Value'] = $resultArray;
        return $retArray;
    }
    //  exeSuspend Simpleと同じでいい
    //  exeExitAll Simpleと同じでいい

    private function exeMeasure_Rollup_Process($isCurrenetO) {
        $this->co_trace();
        $dblNumerator   = 0.0;      //分子
        $dblDenominator = 0.0;      //分母
        $valid_data = FALSE;        //

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
        $actions = $checkSatisfied ? array('notSatisfied', 'satisfied', 'incomplete', 'completed') : array('incomplete', 'completed');
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
                        $this->seqParam->setSuccessStatus($str, TRUE);
                    } else if ($str == 'completed' || $str == 'incomplete') {
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
        $retArray = array();
        $rst = $this->seqParam->checkExitCondition();
        if ($rst) {
            $retStr = strtoupper($this->seqParam->checkPostCondition());
            if ($retStr != '') {
                if ($retStr == 'RETRY') {
                    $retStr = 'RETRYALL';
                }
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                $retArray['Value'] = array('command' => $retStr, 'value' => NULL, 'activityId' => $this->getID());
            } else {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                $retArray['Value'] = array();
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
                        // FIX: RU-08b
                        //$retArray['Value'] = array('command' => 'EXITALL', 'value' => NULL, 'activityId' => $this->getID());
                        $retArray['Value'] = array('command' => $val['value'], 'value' => NULL, 'activityId' => $this->getID());
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
        if ($cmd === 'CHOICE') {
            if (!is_null($val)) {
                if ($this->getID() !== $val) {
                    if ($this->seqParam->getControlModeParam('forwardOnly')) {
                        if ($this->checkSelectedActivityDirection($val, $id) === 'Backward') {
                            return $this->makeCheckResult('error', 'SB.2.4-2');
                        }
                    }
                }
            }
        }
        if ($cmd === 'CONTINUE') {
            if (!$this->seqParam->getControlModeParam('flow')) {
                return $this->makeCheckResult('error', 'SB.2.7-2');
            }
        } else if ($cmd === 'PREVIOUS') {
            if (!$this->seqParam->getControlModeParam('flow')) {
                return $this->makeCheckResult('error', 'SB.2.8-2');
            }
            if ($this->seqParam->getControlModeParam('forwardOnly')) {
                return $this->makeCheckResult('error', 'SB.2.4-2');
            }
        }
        return $this->makeCheckResult('', '');
    }

    private function checkSelectedActivityDirection($id_selected, $id_current) {
        $getAncestorChildPosition = function ($root, $id) use (&$getAncestorChildPosition) {
            if ($root->getID() === $id) {
                return -1;
            }
            $idx = $root->getChildPosition($id);
            if ($idx > -1) {
                return $idx;
            } else {
                $platform = Platform::getInstance();
                $activity = $platform->searchAct($id);
                $parent = $activity->getParent();
                return $getAncestorChildPosition($root, $parent->getID());
            }
        };

        $idx_selected = $getAncestorChildPosition($this, $id_selected);
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

    private function getControlMode($param) {
        return $this->seqParam->getControlModeParam($param);
    }

    // 終了処理。bool値を返す
    protected function endAttempt($cmd) {
        $this->co_trace();
        if ($this->isActive) {
            $this->isActive = FALSE;
            $session_time = time() - $this->startTime;
            $this->totalTime += $session_time;
            $this->sessionTime = $session_time;
        }
        return array('Result' => TRUE, 'Continue' => FALSE);
    }

    protected function exeRetryAll($id, $val) {
        $this->co_trace();
        $this->seqParam->setCurrentStatus(TRUE);
        $this->seqParam->addAttemptCount();
        $len = count($this->children);
        $flg = FALSE;
        for ($i = 0; $i < $len; $i++) {
            $tmpAct = $this->getChild($i);
            $tmpAct->callFromParent('INITC', 'ALL');
        }
        return $this->exeStart();
    }

    protected function exeGetValue($id, $val) {
        $value = NULL;
        if ($val[0] === 'scorm.ControlMode') {
            $value = $this->getControlMode($val[1]);
        }
        return array('Value' => $value);
    }

    private function getDeliveryControlsTracked() {
        return $this->seqParam->getDeliveryControlsParam('tracked');
    }

    public function ignoreTrace() {
        $this->ignoreTrace = TRUE;
        $this->seqParam->ignoreTrace();
    }
}
