<?php
require_once dirname(__FILE__) . '/SimpleBlock.php';

class SCORMRootBlock extends SimpleBlock {
    /**
     * シーケンス
     * @var SimpleSequencing
     */
    protected $seqParam = null;
    /**
     * （不明）
     * @var unknown_type
     */
    private $isRollUp;
    
    
    /**
     * コンストラクタ
     * @param $ctx
     * @param $num
     * @param $node
     * @param $res
     * @param $objectives
     */
    function __construct(&$ctx, $num, $node, $res, &$objectives){
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->addTable();
        $this->isRollUp = FALSE;

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
    }

    
    /**
     * 機能定義を追加する。
     */
    function addTable() {
        // SimpleRoot のコマンドを実装
        $this->cmdTableFromChild['RETRYALL'] = array(
            'Func' => 'exeRetryAll',
            'Type' => 'seq',
            'View' => FALSE
        );
        // 子どものINITRTMコマンド処理（SCORMSco::exeInitRTM）から呼ばれる
        $this->cmdTableFromChild['INITS'] = array(
            'Func' => 'exeInitAll', 
            'Type' => 'cmd', 
            'View' => FALSE
        );
        $this->cmdTableFromChild['INITAB'] = array(
            'Func' => 'exeInitAll', 
            'Type' => 'cmd', 
            'View' => FALSE
        );
        $this->cmdTableFromChild['INITPB'] = array(
            'Func' => 'exeInitAll', 
            'Type' => 'cmd', 
            'View' => FALSE
        );
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

    public function terminate(){
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
                            'attemptCount' => $this->aCounter,
                            'successStatus' => $success_status,
                            'scaledScore' => $scaled_score,
                            'completionStatus' => $completion_status,
                            'runtimeXML' => rawurlencode($runtimeXML));

        return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array);
    }

    /**
     * アテンプトを開始する。
     * @see co/SimpleBlock::startAttempt()
     */
    protected function startAttempt() {
        $this->co_trace();
        $this->isActive = TRUE;
        if (!$this->isSus) {
            $this->aCounter++;
            $this->seqParam->addAttemptCount();
        }
        $this->isSus = FALSE;
    }

    private function getCommandList($val) {
        // コマンドリストの作成
        $postAry = array_clone($val);
        foreach (array_keys($this->cmdTableFromChild) as $k) {
            if (!array_key_exists($k, $postAry)) {
                $postAry[$k]['Type'] = $this->cmdTableFromChild[$k]['Type'];
                $postAry[$k]['View'] = $this->cmdTableFromChild[$k]['View'];
            }
        }
        return $postAry;
    }

    /**
     * 子どものINITRTMコマンド処理（SCORMSco::exeInitRTM）から呼ばれる。
     * @param unknown_type $id
     * @param unknown_type $val
     */
    public function exeInitAll($id, $val) {
        $this->co_trace();
        $retArray = array();
        if (!($this -> isActive)) {
            $this -> startAttempt();
            $this->seqParam->setCurrentStatus(FALSE);
        }
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;   // まだ親が存在するためTRUEを返す
        $retArray['Command'] = 'INIT';  // 親はSCORMオブジェクトではないのでINITにコマンド書き換え
        $retArray['Value'] = $this->getCommandList($val);
        return $retArray;
    }

    /**
     * INDEXを返すために親から呼ばれるメソッド。
     * @see co/SimpleBlock::exeIndexP()
     */
    function exeIndexP($val){
        $this->co_trace();
        $resultArray = array(
            'type'           => $this->getType(),
            'title'          => $this->getTitle(),
            'id'             => $this->getID(),
            'is_active'      => $this->isActive,
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

        for ($i = 0; $i < $len; $i++){
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

    private function exeRollup_Using_Rules($str, $isCurrenetO, $isCurrenetA) {
        $this->co_trace();
        $isUseRule = FALSE;
        $tmpAry = $this->seqParam->getRollupRules($str);
        $len = count($tmpAry);
        for ($i=0; $i < $len; $i++) {
            $isUseRule = TRUE;
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
                $tmpAct = $this->getChild($j);
                $condition = $tmpAct->callFromParent('GETVALUE', array('scorm.CheckConditionForRollUp', $str, $tmpCondAry, $tmpCondStr, $isCurrenetO, $isCurrenetA))['Value'];
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
        return $isUseRule;
    }

    // in : 呼び出し ID, 引数, アクティビティ配列, オブジェクティブ配列
    // out: 正常フラグ, 続行フラグ
    function exeRollUp($id,$val){
        $this->co_trace();

        // 自分自身のTRACKEDを調べる。
        if ($this->getDeliveryControlsTracked()) {
            $isCurrenetO = $this->seqParam->getControlModeParam('useCurrentAttemptObjectiveInfo');
            $isCurrenetA = $this->seqParam->getControlModeParam('useCurrentAttemptProgressInfo');

            $this->exeMeasure_Rollup_Process($isCurrenetO);// リーフではやらない

            $resultObj = FALSE;
            if($this->seqParam->exeObjectiveRollupUsingMeasure($this -> isActive)){
                $resultObj = TRUE;
            }
            if(!$resultObj){// めじゃーで確定していた場合はなにもしない
                // まずはNotSatisfiedの確認
                $resultObjNS = $this->exeRollup_Using_Rules('notSatisfied',$isCurrenetO,$isCurrenetA);
                // Satisfiedの確認
                $resultObjS = $this->exeRollup_Using_Rules('satisfied',$isCurrenetO,$isCurrenetA);
                if($resultObjNS || $resultObjS){
                    $resultObj = TRUE;
                }
            }
            $this->exeRollup_Using_Rules('incomplete',$isCurrenetO,$isCurrenetA);
            $this->exeRollup_Using_Rules('completed',$isCurrenetO,$isCurrenetA);
        }
    }

    /**
     * 事前の状態チェックを行なう
     * @see co/SimpleBlock::checkPreCondition()
     */
    function checkPreCondition($cmd, $id, $val, $isDescending){
        $this->co_trace();
        $rst = $this->makeCheckResult('', '');
        // まずはコマンドのチェック
        if ($cmd == 'CHOICE') {
            if ($this -> getID() === $val) {
                // CHOICEでRootが選ばれる場合は、SCORMRootの場合と同じで空の結果を返す
            } else {
                if ($this->hasChild($val)) {
                    if (!$this->getControlMode('choice')) {
                        return $this->makeCheckResult('error', 'SB.2.9-4');
                    }
                    if ($this->getControlMode('forwardOnly')) {
                        if ($this->checkSelectedActivityDirection($val, $id) === 'Backward') {
                            return $rst = $this->makeCheckResult('error', 'SB.2.4-2');
                        }
                    }
                    if ($this->seqParam->getConstrainedChoiceConsiderationsParam('preventActivation')) {
                        return $this->makeCheckResult('error', 'SB.2.9-6');
                    }
                } else if ($this->seqParam->getConstrainedChoiceConsiderationsParam('constrainChoice') and !is_null($id)) {
                    $parent = $this->getParent();
                    // get "next" activity
                    $idx = $parent->getChildPosition( $this->getID());
                    if ($idx + 1 < count($parent->children)) {
                        $next = $parent->getChild( $idx + 1);
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
        } else if(($cmd == 'CONTINUE')||($cmd == 'PREVIOUS')) {
            if (!$this->getControlMode('flow')) {
                $rst = $this->makeCheckResult('error',  $cmd === 'CONTINUE' ? 'SB.2.7-2' : 'SB.2.8-2');
            }
            if ($cmd == 'PREVIOUS' and $this->getControlMode('forwardOnly')) {
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

    
    /**
     * シーケンスからコントロールモードを取得して返す。
     * @param $param
     */
    function getControlMode($param) {
        return $this->seqParam->getControlModeParam($param);
    }

    
    /**
     * 子ノードに指定されたIDのものがあるかどうかを返す。
     * @param string $id
     * @param array $activities
     * @return 該当のノードがあればTRUE、なければFALSE
     */
    private function hasChild($id) {
        for ($i = 0; $i < count($this->children); $i++) {
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
        $idx_selected = $this->getChildPosition( $id_selected);
        $idx_current  = $this->getChildPosition( $id_current);
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

    /**
     * アテンプトを終了する
     * @see co/SimpleBlock::endAttempt()
     */
    function endAttempt($cmd) {
        $this->co_trace();
        if ($this->isActive) {
            $this->isActive = FALSE;
        }

        return array(
           'Result' => TRUE,
           'Continue' => TRUE  // SCORMオブジェクトのルートではあるが継続するためTRUEを返す
        );
    }

     function exeRetryAll($id, $val) {
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

    // ロールアップ用の状態取得関数群
    public function getDeliveryControlsTracked() {
        return $this->seqParam->getDeliveryControlsParam('tracked');
    }

    public function getRollupObjectiveMeasureWeight() {
        return $this->seqParam->getRollupObjectiveMeasureWeight();
    }

    public function getPrimaryObjectiveMeasureEvaluateWeight($isCurrent) {
        // 重さｘ得点率
        $retVal = $this->seqParam->getScaledScoreForRR($isCurrent);
        if ($retVal != "") {
            $weight = $this->getRollupObjectiveMeasureWeight();
            $retVal= floatval($retVal) * $weight;
        }
        return $retVal;
    }

    public function checkChildForRollUp($str) {
        return $this->seqParam->checkChildForRollUp($this->aCounter, $this->isSus, $str);
    }

    // ロールアップ用のステータスチェック
    public function checkStatusForRollUp($condAry, $condC, $isCurrent0, $isCurrentA) {
        return $this->seqParam->checkStatusForRollUp($this->aCounter, $condAry, $condC, $isCurrent0, $isCurrentA);
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
}
