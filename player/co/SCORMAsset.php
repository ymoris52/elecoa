<?php
require_once dirname(__FILE__) . '/SCORMLeaf.php';

class SCORMAsset extends SCORMLeaf {
    protected $cmdTableFromMain;

    private $isRollUp;
    private $successStatus;
    private $scaledScore;
    private $passingScore;
    private $completionStatus;
    private $progressMeasure;
    private $completionThreshold;
    private $totalTime;
    private $sessionTime;
    private $startTime;

    // コンストラクタ
    function __construct(&$ctx, $num,$node,$res,&$objectives){
        parent::__construct($ctx, $num,$node,$res, $objectives);
        $this->addTable();
        $this->isRollUp = FALSE;
        if($res){
            $values = readLog($this -> getContext(), $this -> getID(), null, $this -> getType(), array('successStatus', 'scaledScore', 'completionStatus', 'progressMeasure', 'isActive', 'runtimeXML'));
            if(!is_null($values)) {
                $this->successStatus    = trim($values['successStatus']);
                $this->scaledScore      = trim($values['scaledScore']);
                $this->completionStatus = trim($values['completionStatus']);
                $this->progressMeasure  = trim($values['progressMeasure']);
                $values['isActive'] = trim($values['isActive']);
                if($values['isActive'] == 'true'){
                    $this->isActive = TRUE;
                }else{
                    $this->isActive = FALSE;
                }
            }else{
                $this->successStatus    = 'unknown';
                $this->scaledScore      = '';
                $this->completionStatus = 'unknown';
                $this->progressMeasure  = '';
            }
        }else{
            $this->successStatus    = 'unknown';
            $this->scaledScore      = '';
            $this->completionStatus = 'unknown';
            $this->progressMeasure  = '';
        }
        $sNode = selectSingleNode($this -> dataNode,'imsss:sequencing');
        $this->seqParam = new SimpleSequencing($this->getID(), $sNode, $this->successStatus, $this->scaledScore, $this->completionStatus, $this->aCounter, TRUE, $objectives);

        $this->passingScore = $this->seqParam->getPassingScore();
        $this->completionThreshold = '';
        $ctNode = selectSingleNode($this -> dataNode,"adlcp:completionThreshold");
        if($ctNode != null){
            $this->completionThreshold = $ctNode->nodeValue;
        }
    }

    function addTable(){
        $this->cmdTableFromSelf['EXITCOND']     = array();
        $this->cmdTableFromSelf['EXITCOND']['Func'] = 'exeExitCondition';
        $this->cmdTableFromSelf['EXITCOND']['Type'] = 'cmd';
        $this->cmdTableFromSelf['EXITCOND']['View'] = FALSE;
    }
    
    private function set_node_value(&$parent_node, $node_name, $node_value) {
        $node = selectSingleNode($parent_node, $node_name);
        if ($node) {
            $node->nodeValue = $node_value;
        }
        else {
            $node = $parent_node->ownerDocument->createElement($node_name, $node_value);
            $parent_node->appendChild($node);
        }
    }

    function terminate(){
        $this->co_trace($this->strID . ' => ' . "MAINからのSAVEM処理");

        $dom = new DOMDocument();
        $cmi_node = $dom->createElement('cmi');
        $dom->appendChild($cmi_node);
        $this->set_node_value($dom->documentElement, 'success_status', $this->successStatus);
        $this->set_node_value($dom->documentElement, 'scaled_score', $this->scaledScore);
        $this->set_node_value($dom->documentElement, 'completion_status', $this->completionStatus);
        $this->set_node_value($dom->documentElement, 'progress_measure', $this->progressMeasure);
        $runtimeXML = $dom->saveXML();
        
        $data_array = array('successStatus' => $this->successStatus,
                            'scaledScore' => $this->scaledScore,
                            'completionStatus' => $this->completionStatus,
                            'progressMeasure' => $this->progressMeasure,
                            'isActive' => $this->isActive ? 'true' : 'false',
                            'runtimeXML' => rawurlencode($runtimeXML));
        return writeLog($this -> getContext(), $this -> getID(), null, $this -> getType(), $data_array);
    }

    public function saveGrade($grademodule) {
        $grade = new stdClass();
        $grade->completionStatus = $this->seqParam->getCompletionStatus();
        $grade->successStatus = $this->seqParam->getSuccessStatus(TRUE);
        $grade->scaledScore = $this->seqParam->getScaledScore(TRUE);
        $grademodule -> writeGrade($this -> getContext(), $this -> getID(), null, $this -> getType(), $grade);
    }

    public function getAPIAdapterProvider(){
        return createAPIAdapterProvider('Scorm'); //defined in init_www.php
    }

    function getRollUpSet(){
        return $this->isRollUp;
    }

    /**
     * ２つのtimeintervalを足したtimeinterval値を返す。
     *
     * @param string $timeinterval1
     * @param string $timeinterval2
     * @return string 合計のtimeinterval値。
     */
    private function sum_timeinterval($timeinterval1, $timeinterval2) {
        $time1 = $this->get_seconds_from_timeinterval($timeinterval1);
        $time2 = $this->get_seconds_from_timeinterval($timeinterval2);

        return $this->get_timeinterval_from_seconds($time1 + $time2);
    }

    /**
     * timeinterval値が表す秒数を返す。
     *
     * @param string $timeinterval
     * @return float 秒数。
     */
    private function get_seconds_from_timeinterval($timeinterval) {
        if (!preg_match('/^P(?:(\d*)Y)?(?:(\d*)M)?(?:(\d*)D)?(?:T(?:(\d*)H)?(?:(\d*)M)?(?:(\d*(?:.\d{1,2})?)S)?)?$/', $timeinterval, $matches)) {
            return 0.0;
        }
        else {
            $years = intval($matches[1]);
            $months = intval($matches[2]);
            $days = intval($matches[3]);
            $hours = intval($matches[4]);
            $minutes = intval($matches[5]);
            $seconds = floatval($matches[6]);

            $seconds += 60.0 * $minutes;
            $seconds += 60.0 * 60.0 * $hours;
            $seconds += 60.0 * 60.0 * 24.0 * $days;
            $seconds += 60.0 * 60.0 * 24.0 * 30.0 * $months;
            $seconds += 60.0 * 60.0 * 24.0 * 30.0 * 365.0 * $years;

            return $seconds;
        }
    }

    /**
     * 秒数をtimeinterval形式に変換して返す。
     *
     * @param float $seconds 秒数。
     * @return string timeinterval値。
     */
    private function get_timeinterval_from_seconds($seconds) {
        $ti_seconds = sprintf('%.2f', $seconds % 60 + ($seconds - floor($seconds)));
        $ti_minutes = sprintf('%d', floor(floor($seconds / 60.0) % 60.0));
        $ti_hours = sprintf('%d', floor($seconds / (60.0 * 60.0)));

        return 'PT' . $ti_hours . 'H' . $ti_minutes . 'M' . $ti_seconds . 'S';
    }

    protected function startAttempt() {
        $this->co_trace();
        $this -> isActive = TRUE;
        if(!$this -> isSus){
            $this -> aCounter++;
            $this -> seqParam -> addAttemptCount();
        }
        $this->sessionTime = NULL;
        $this->startTime = time();
        $this->isSus = FALSE;
    }

    // コマンドINIT
    // クライアントにコンテンツがロードされたときに発行される
    function exeInit($val,$rtm){
        $this->co_trace($this->strID . ' => ' . "MAINからのInitRTM処理START");
        if($this->isActive) {// すでにアクティブならエラー
        }
        $this->startAttempt();
        // さかのぼって
        // 自分自身で制御できるコマンドリストを作成し親になげる。
        $tmpAry = array_keys($this->cmdTableFromSelf);
        $len  = count($tmpAry);
        $postAry = array();

        for($i=0; $i < $len; $i++) { // 自分自身はViewをみて無条件に加算してよい
            $postAry[$tmpAry[$i]]['Type'] = $this->cmdTableFromSelf[$tmpAry[$i]]['Type'];
            $postAry[$tmpAry[$i]]['View'] = $this->cmdTableFromSelf[$tmpAry[$i]]['View'];
        }
        // 親のコマンドを調べる。
        $retArray = $this->getParent()->callFromChild($this->getID(), "INIT", $postAry, NULL);

        if(!$retArray['Result']){// 失敗
            $retArray['Result'] = FALSE;
            $retArray['Value'] = '';
        }else{
            $this->cmdTableFromAncestor = array_clone($retArray['Value']);
            $tmpAry = array_keys($this->cmdTableFromAncestor);
            $len  = count($tmpAry);
            $retStr = "";
            for($i=0; $i<$len; $i++){
                if($this->cmdTableFromAncestor[$tmpAry[$i]]['View']){
                    $retStr .= $tmpAry[$i] . ',';
                }
            }
            $retArray['NextID'] = '';
            $retArray['Continue'] = FALSE;
            $retArray['Command'] = '';
            $retArray['Value'] =  'rtm_button_param=' . $retStr;
        }
        return $retArray;
    }

    function exeIndexP($val){
        $this->co_trace();

        // 自分自身の状態を親になげる。
        $resultArray = array(
            'type'              => $this->getType(),
            'title'             => $this->getTitle(),
            'id'                => $this->getID(),
            'is_active'         => $this->isActive,
            'success_status'    => $this->seqParam->getSuccessStatus(TRUE),
            'completion_status' => $this->seqParam->getCompletionStatus(),
        );

        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;
        $retArray['Value'] = $resultArray;
        return $retArray;
    }

    function exeExitCondition($val,$rtm){
        $this->co_trace();
        // Leafの場合は常に通る。
        $rst = $this->seqParam->checkExitCondition();
        $retArray = array();
        if($rst){
            $act = $this->seqParam->checkPostCondition();
        }else{
            $retArray['Result'] = TRUE;
            $retArray['NextID'] = '';
            $retArray['Continue'] = FALSE;
            $retArray['Command'] = '';
        }
    }

    function checkPreCondition($cmd, $id, $val, $isDescending){
        $this->co_trace();
        $rst = $this->makeCheckResult('', '');
        // まずはコマンドのチェック
        if($cmd == 'CHOICE'){
            if(!$this->seqParam->getControlModeParam('choice')){
                $rst = $this->makeCheckResult('error', 'SB.2.9-4');
            }
        }else if(($cmd == 'CONTINUE')||($cmd == 'PREVIOUS')){
            if(!$this->seqParam->getControlModeParam('flow')){
                $rst = $this->makeCheckResult('error', $cmd === 'CONTINUE' ? 'SB.2.7-2' : 'SB.2.8-2');
            }
        }
        // 次に状態のチェック
        if($isDescending){
            $rst = $this->makeCheckResult($this->seqParam->checkPreCondition(), '');
        }
        return $rst;
    }


    function changeSuccessStatus($str){
        if($str == 'passed'){
            return 'satisfied';
        }else if($str == 'satisfied'){
            return 'passed';
        }else if($str == 'failed'){
            return 'not satisfied';
        }else if($str == 'not satisfied'){
            return 'failed';
        }else{
            return 'unknown';
        }
    }

    function getSuccessStatus(){
        return $this->seqParam->getSuccessStatus();
    }

    function setSuccessStatus($str){
        $this->successStatus = $str;
        $this->seqParam->setSuccessStatus($str);
    }

    function getScaledScore(){
        return $this->seqParam->getScaledScore();
    }

    function setScaledScore($str){
        if($str !== ''){
            $this->scaledScore = $str;
            $this->seqParam->setScaledScore($str);
        }
    }

    function getCompletionStatus(){
        $this->seqParam->getCompletionStatus();
    }

    function setCompletionStatus($str){
        $this->completionStatus = $str;
        $this->seqParam->setCompletionStatus($str);
    }

    function getProgressMeasure(){
        return $this->progressMeasure;
    }

    function setProgressMeasure($str){
        if($str !== ''){
            $this->progressMeasure = $str;
        }
    }
}
?>