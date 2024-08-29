<?php
require_once dirname(__FILE__) . '/SCORMLeaf.php';

class SCORMSco extends SCORMLeaf {
    protected $cmdTableFromMain;

    private $lessonLocation;
    private $suspendData;
    private $dataFromLMS;
    private $completionThreshold;
    private $timeLimitAction;
    private $hideLMSUI;
    private $progressMeasure;
    private $totalTime;
    private $sessionTime;
    private $startTime;
    private $runtimeXML;
    private $cmi_entry;
    private $cmi_credit;
    private $cmi_mode;

    private $pScore;

    // コンストラクタ
    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->addTable();

        $tmpSuccessStatus    = 'unknown';
        $tmpScaledScore      = '';
        $tmpCompletionStatus = 'unknown';
        $current = TRUE;

        $this->lessonLocation      = '';
        $this->suspendData         = '';
        $this->dataFromLMS         = '';
        $this->completionThreshold = '';
        $this->timeLimitAction     = 'continue,no message'; //REQ_79.3
        $this->totalTime           = 'PT0H0M0S';
        $this->sessionTime         = NULL;
        $this->runtimeXML          = '<cmi/>';
        $this->cmi_entry           = 'ab-initio';
        $this->cmi_credit          = 'credit';
        $this->cmi_mode            = 'normal';
        $this->hideLMSUI           = array();

        if ($res) {
            $key_value_pairs = readLog($this->getContext(), $this->getID(), NULL, $this->getType(),
                                       array('current', 'isSuspend', 'attemptCount', 'successStatus', 'scaledScore', 'completionStatus', 'progressMeasure', 'lessonLocation', 'suspendData', 'totalTime', 'runtimeXML'));
            if ((isset($key_value_pairs['isSuspend']) ? $key_value_pairs['isSuspend'] : '') == 'true') {
                $this->isSus = TRUE;
                $this->cmi_entry = 'resume';
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
            $this->progressMeasure = isset($key_value_pairs['progressMesure']) ? $key_value_pairs['progressMesure'] : $this->progressMeasure;
            $this->lessonLocation = isset($key_value_pairs['lessonLocation']) ? $key_value_pairs['lessonLocation'] : $this->lessonLocation;
            $this->suspendData = isset($key_value_pairs['suspendData']) ? $key_value_pairs['suspendData'] : $this->suspendData;
            $this->totalTime = isset($key_value_pairs['totalTime']) ? $key_value_pairs['totalTime'] : $this->totalTime;
            $this->runtimeXML = isset($key_value_pairs['runtimeXML']) ? rawurldecode($key_value_pairs['runtimeXML']) : $this->runtimeXML;
        }
        $sNode = selectSingleNode($this->dataNode, 'imsss:sequencing');
        $this->seqParam = new SimpleSequencing($this->getID(), $sNode, $tmpSuccessStatus, $tmpScaledScore, $tmpCompletionStatus, $this->aCounter, $current, $objectives);
        $this->rollup->addWriteTargetObjectiveIdArray($this->seqParam->getWriteObjectiveIdArray());

        $ctNode = selectSingleNode($this->dataNode, "adlcp:completionThreshold");
        if ($ctNode != NULL) {
            $this->completionThreshold = $ctNode->nodeValue;
        }
        //adlcp:dataFromLMS
        $dfNode = selectSingleNode($this->dataNode, "adlcp:dataFromLMS");
        if ($dfNode != NULL) {
            $this->dataFromLMS = $dfNode->nodeValue;
        }

        $tlNode = selectSingleNode($this->dataNode, "adlcp:timeLimitAction");
        if ($tlNode != NULL) {
            $this->timeLimitAction = $tlNode->nodeValue;
        }

        $presenNode = selectSingleNode($this->dataNode, "adlnav:presentation");
        if ($presenNode != NULL) {
            $niNode = selectSingleNode($presenNode, "adlnav:navigationInterface");
            if ($niNode != NULL) {
                $hlNodes = selectNodes($niNode, "adlnav:hideLMSUI");
                $count = count($hlNodes);
                for ($i=0; $i < $count; $i++) {
                    array_push($this->hideLMSUI, str_replace('suspendall', 'suspend', strtolower($hlNodes[$i]->nodeValue)));
                }
            }
        }
    }

    private function addTable() {
        $this->cmdTableFromSelf['INITRTM'] = array();
        $this->cmdTableFromSelf['INITRTM']['Func'] = 'exeInitRTM';
        $this->cmdTableFromSelf['INITRTM']['Type'] = 'cmd';
        $this->cmdTableFromSelf['INITRTM']['View'] = FALSE;

        $this->cmdTableFromSelf['FINRTM'] = array();
        $this->cmdTableFromSelf['FINRTM']['Func'] = 'exeFinRTM';
        $this->cmdTableFromSelf['FINRTM']['Type'] = 'cmd';
        $this->cmdTableFromSelf['FINRTM']['View'] = FALSE;

        $this->cmdTableFromSelf['COMMIT'] = array();
        $this->cmdTableFromSelf['COMMIT']['Func'] = 'exeCommit';
        $this->cmdTableFromSelf['COMMIT']['Type'] = 'cmd';
        $this->cmdTableFromSelf['COMMIT']['View'] = FALSE;

        // 親からくる命令
        $this->cmdTableFromParent['INITC'] = array();
        $this->cmdTableFromParent['INITC']['Func'] = 'exeInitCurrent';
        $this->cmdTableFromParent['INITC']['Type'] = 'cmd';
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
        if (empty($this->runtimeXML) || !$dom->loadXML($this->runtimeXML)) {
            $dom->loadXML('<cmi/>');
        }
        $this->set_node_value($dom->documentElement, 'success_status', $this->changeSuccessStatusToRTM($success_status));
        $this->set_node_value($dom->documentElement, 'scaled_score', $scaled_score);
        $this->set_node_value($dom->documentElement, 'completion_status', $completion_status);
        $this->set_node_value($dom->documentElement, 'progress_measure', $this->progressMeasure);
        $this->set_node_value($dom->documentElement, 'location', $this->lessonLocation);
        $this->set_node_value($dom->documentElement, 'suspend_data', $this->suspendData);
        $this->set_node_value($dom->documentElement, 'total_time', $this->totalTime);
        $this->runtimeXML = $dom->saveXML();

        $data_array = array('isSuspend' => $this->isSus ? 'true' : 'false',
                            'current' => $this->seqParam->getCurrentStatus() ? 'true' : 'false',
                            'attemptCount' => $this->aCounter,
                            'successStatus' => $success_status,
                            'scaledScore' => $scaled_score,
                            'completionStatus' => $completion_status,
                            'progressMeasure' => $this->progressMeasure,
                            'lessonLocation' => $this->lessonLocation,
                            'suspendData' => $this->suspendData,
                            'totalTime' => $this->totalTime,
                            'runtimeXML' => rawurlencode($this->runtimeXML));

        return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array);
    }

    public function saveGrade($grademodule) {
        $grade = new stdClass();
        $grade->completionStatus = $this->seqParam->getCompletionStatus();
        $grade->successStatus = $this->seqParam->getSuccessStatus(TRUE);
        $grade->scaledScore = $this->seqParam->getScaledScore(TRUE);
        $grademodule->writeGrade($this->getContext(), $this->getID(), NULL, $this->getType(), $grade);
    }

    public function getAPIAdapterProvider() {
        return createAPIAdapterProvider('Scorm'); //defined in init_www.php
    }

    protected function exeRollUpMain() {
        $this->co_trace();
        // 成績一覧用のデータ保存
        $grademodule = $this->getGradeModule();
        if ($grademodule) {
            $gradedata = new stdClass();
            $gradedata->completionStatus = $this->seqParam->getCompletionStatus();
            $gradedata->successStatus = $this->seqParam->getSuccessStatus(TRUE);
            $gradedata->scaledScore = $this->seqParam->getScaledScore(TRUE);
            if (!isset($this->sessionTime)) {
                $session_time = time() - $this->startTime;
                $this->sessionTime = $session_time;
                $session_time_timeinterval = $this->get_timeinterval_from_seconds($session_time);
                $this->totalTime = $this->sum_timeinterval($this->totalTime, $session_time_timeinterval);
            } else {
                $session_time = $this->sessionTime;
            }
            $gradedata->sessionTime = $this->sessionTime;
            $gradedata->totalTime = $this->get_seconds_from_timeinterval($this->totalTime);
            $grademodule->writeGrade($this->getContext(), $this->getID(), $this->aCounter, $this->getType(), $gradedata);
        }
    }

    protected function exeExitCond($val, $rtm) {
        $this->co_trace();
        $retStr = strtoupper($this->seqParam->checkPostCondition());
        $valueForParent = array();
        if ($retStr == "exit") {
            $valueForParent['command'] = 'EXITPARENT';
            $valueForParent['value'] = NULL;
            $valueForParent['activityId'] = $this->getID();
        } else if ($retStr != "") {
            $valueForParent['command'] = $retStr;
            $valueForParent['value'] = NULL;
            $valueForParent['activityId'] = $this->getID();
        } else {
            // FIX:T-01b
            $valueForParent['command'] = $val['command'];
            $valueForParent['value'] = $val['value'];
            $valueForParent['activityId'] = NULL;
        }
        return array('Result' => TRUE, 'Continue' => TRUE, 'Value' => $valueForParent);
    }

    protected function exeReady($val, $rtm) {
        $this->co_trace();
        if (!$this->seqParam->getControlModeParam('choiceExit')) {
            return array('Result' => FALSE, 'Value' => '', 'Error' => 'NB.2.1-8');
        }
        return parent::exeReady($val, $rtm);
    }

    // 終了処理
    protected function endAttempt($cmd) {
        $this->co_trace();
        if ($this->isActive and !$this->isSus) {
            // まずは自分自身の終了
            $this->isActive = FALSE;
            // ステータスの確定
            $this->seqParam->setStatusFin();
        } else {
            if ($this->isActive) $this->isActive = FALSE;
        }
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

    protected function exeInitRTM($val, $rtm) {
        $this->co_trace();
        $this->seqParam->setCurrentStatus(TRUE);
        $needsResume = $this->isSus;
        if ($needsResume) {
            $this->cmi_entry = 'resume';
        } else {
            $this->cmi_entry = 'ab-initio';
            $this->totalTime = 'PT0H0M0S';
            $this->seqParam->setDurationSecond(0);
        }
        $this->startAttempt();
        // さかのぼって
        // 自分自身で制御できるコマンドリストを作成し親になげる。
        $tmpAry = array_keys($this->cmdTableFromSelf);
        $len = count($tmpAry);
        $postAry = array();

        for ($i=0; $i < $len; $i++) {// 自分自身は無条件に加算してよい
            $postAry[$tmpAry[$i]]['Type'] = $this->cmdTableFromSelf[$tmpAry[$i]]['Type'];
            $postAry[$tmpAry[$i]]['View'] = $this->cmdTableFromSelf[$tmpAry[$i]]['View'];
        }
        // 親のコマンドを調べる。
        $retArray = $this->getParent()->callFromChild($this->getID(), "INITS", $postAry, NULL);

        if ($retArray['Result']) {
            $this->cmdTableFromAncestor = array_clone($retArray['Value']);
            $tmpAry = array_keys($this->cmdTableFromAncestor);
            $len = count($tmpAry);
            $retStr = '';

            for ($i=0; $i < $len; $i++) {
                if ($this->cmdTableFromAncestor[$tmpAry[$i]]['View']) {
                    if (!in_array(strtolower($tmpAry[$i]), $this->hideLMSUI)) {
                        $retStr .= $tmpAry[$i] . ',';
                    }
                }
            }
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = FALSE;
            $retArray['Value'] = 'rtm_button_param=' .    $retStr . "\n";
            $tmpSuccessStatus = $this->changeSuccessStatusToRTM($this->seqParam->getSuccessStatus(TRUE));
            $retArray['Value'] .= 'rtm_success_Status=' . $tmpSuccessStatus . "\n";
            $tmpScaledScore = $this->seqParam->getScaledScore(TRUE);
            $retArray['Value'] .= 'rtm_scaledScore=' .    $tmpScaledScore . "\n";
            $this->pScore = $this->seqParam->getPassingScore();
            $retArray['Value'] .= 'rtm_passingScore=' .   $this->pScore . "\n";
            $tmpCompletionStatus = $this->seqParam->getCompletionStatus();
            $retArray['Value'] .= 'rtm_completionStatus=' . $tmpCompletionStatus . "\n";
            $tmpProgressMeasure = $this->progressMeasure;
            $retArray['Value'] .= 'rtm_progressMeasure=' . $tmpProgressMeasure . "\n";
            $retArray['Value'] .= 'rtm_completionThreshold=' . $this->completionThreshold . "\n";
            $retArray['Value'] .= 'rtm_dataFromLMS=' .     $this->dataFromLMS . "\n";
            $retArray['Value'] .= 'rtm_timeLimitAction=' . $this->timeLimitAction . "\n";
            $retArray['Value'] .= 'rtm_lessonLocation=' .  $this->lessonLocation . "\n";
            $retArray['Value'] .= 'rtm_suspendData=' .     $this->suspendData . "\n";
            $retArray['Value'] .= 'rtm_totalTime=' .       $this->totalTime . "\n";
            $retArray['Value'] .= 'rtm_attemptAbsoluteDurationLimit=' . $this->seqParam->getAttemptAbsoluteDurationLimit() . "\n";
            $retArray['Value'] .= 'rtm_entry=' . $this->cmi_entry . "\n";
            $retArray['Value'] .= 'rtm_credit=' . $this->cmi_credit . "\n";
            $retArray['Value'] .= 'rtm_mode=' . $this->cmi_mode . "\n";
            $retArray['Value'] .= 'rtm_xml=' . rawurlencode($needsResume ? $this->runtimeXML : '<cmi/>') . "\n";
            $objCnt = 0;
            $tmpStr = $this->seqParam->getPrimaryObjectiveID();

            if ($tmpStr != '') {//id,score,success_status,completion_status,progress_measure,description
                $retArray['Value'] .= 'rtm_objData.0=' . $tmpStr . ',' . $tmpSuccessStatus . ',' . $tmpScaledScore . ',';
                $retArray['Value'] .= $tmpCompletionStatus . ',' . $tmpProgressMeasure . ",\n";
                $objCnt++;
            }
            $tmpLen = $this->seqParam->getLocalObjectiveCount();
            for ($i=0; $i < $tmpLen; $i++) {
                $retArray['Value'] .= 'rtm_objData.' . $objCnt . '=';
                $retArray['Value'] .= $this->seqParam->getLocalObjectiveData($i, TRUE, TRUE);
                $objCnt++;
            }
        } else {// 失敗
            $retArray['Result'] = FALSE;
            $retArray['Continue'] = FALSE;
        }
        // UseCurrentの変更
        $this->seqParam->setCurrentStatus(FALSE);
        return $retArray;
    }

    protected function exeInitCurrent($val) {
        $this->co_trace();
        $this->seqParam->setCurrentStatus(TRUE);
        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = TRUE;
        return $retArray;
    }

    // in : 引数
    // out: 正常フラグ, 続行フラグ, 結果値
    protected function exeIndexP($val) {
        $this->co_trace();
        // 自分自身の状態を親になげる。
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
        );

        $continue = TRUE;
        if ($this->isActive) {
            $continue = $this->seqParam->getControlModeParam('choiceExit');
        }
        if ($this->seqParam->checkAttemptLimitExceeded()) {
            $resultArray['hidden_from_choice'] = TRUE;
        }

        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['Continue'] = $continue;
        $retArray['Value'] = $resultArray;
        return $retArray;
    }

    protected function exeCommit($val, $rtm) {
        $this->co_trace();
        return $this->write_apply_rtm($val, $rtm, FALSE);
    }

    protected function exeFinRTM($val, $rtm) {
        $this->co_trace();
        return $this->write_apply_rtm($val, $rtm, TRUE);
    }

    /**
     * RTMを書き込み、オブジェクトに反映する。
     *
     * @param string $val
     * @param string $rtm
     * @param boolean $is_fin 終了時の処理を行う場合はTRUEを渡す
     */
    private function write_apply_rtm($val, $rtm, $is_fin) {
        $rtm = $val;
        //まずはRTMデータの保存
        $data_array = array('RTM' => $rtm);
        if (!$this->ignoreTrace) {
            if (writeLog($this->getContext(), $this->getID(), $this->aCounter, $this->getType(), $data_array) === FALSE) {
                return FALSE;
            }
        }
        $tracked = $this->seqParam->getDeliveryControlsParam('tracked');
        $tmpSuccessStatus = 'unknown';
        $tmpCompletionStatus = 'unknown';

        $tmpArray = explode("\r\n", $rtm);
        $sScore = '';

        foreach ($tmpArray as $line) {
            $line = trim($line);
            if ($line != '') {// 空行は無視
                if (substr($line,0,1) == '') {
                    // 今回は無視でいいはず
                } else {
                    $tmpLine = explode("=", $line);
                    if ($tmpLine[0] == 'exit') {
                        if ($tmpLine[1] == 'suspend') {
                            $this->isSus = TRUE;
                        }
                    } else if ($tmpLine[0] == 'lessonLocation') {
                        $this->lessonLocation = $tmpLine[1];
                    } else if ($tmpLine[0] == 'successStatus' and $tracked) {
                        $tmpSuccessStatus = $this->changeSuccessStatusToSS($tmpLine[1]);
                        //$this->seqParam->setSuccessStatus($this->changeSuccessStatusToSS($tmpLine[1]),TRUE);
                    } else if ($tmpLine[0] == 'completionStatus' and $tracked) {
                        $tmpCompletionStatus = str_replace('&nbsp;', ' ', $tmpLine[1]);
                        $this->seqParam->setCompletionStatusFromRTM($tmpCompletionStatus);
                    } else if ($tmpLine[0] == 'scoreAll' and $tracked) {
                        $tmpScoreAll = urldecode($tmpLine[1]);//urldecode
                        $tmpScoreAry = explode("=", $tmpScoreAll);
                        $tmpScoreRow = explode(",", $tmpScoreAry[0]);
                        $sScore = $tmpScoreRow[0];
                        $this->seqParam->setScaledScore($tmpScoreRow[0],TRUE);
                    } else if ($tmpLine[0] == 'progressMeasure' and $tracked) {
                        $this->progressMeasure = $tmpLine[1];
                    } else if ($tmpLine[0] == 'suspendData') {
                        $this->suspendData = $tmpLine[1];
                    } else if ($tmpLine[0] == 'ObjData') {
                        $tmpObjStr = urldecode($tmpLine[1]);//urldecode
                        $tmpObjAry = explode(",", $tmpObjStr);
                        $tmpObjAry[1] = $this->changeSuccessStatusToSS($tmpObjAry[1]);
                        $this->seqParam->setLocalObjectiveData($tmpObjAry, $tracked);
                    } else if (($tmpLine[0] === 'sessionTime') && $is_fin) {  // 終了の時だけ session_time を合算する
                        $session_time = urldecode($tmpLine[1]);
                        $this->sessionTime = $this->get_seconds_from_timeinterval($session_time);
                        //Fix: OB-14a
                        $this->totalTime = $this->sum_timeinterval($this->totalTime, $session_time);
                        $this->seqParam->setDurationSecond($this->get_seconds_from_timeinterval($this->totalTime));
                    } else if ($tmpLine[0] === 'runtimeXML') {
                        $this->runtimeXML = rawurldecode($tmpLine[1]);
                    } else {
                        // スルー
                    }
                }
            }
        }
        if ($tracked) {
            if ($this->pScore !== '') {
                $dbPScore = floatval($this->pScore);
                if ($sScore === '') {
                    $this->seqParam->setSuccessStatusFromRTM('unknown', TRUE);
                } else {
                    $this->seqParam->setSuccessStatus($tmpSuccessStatus, TRUE);
                }
            } else {
                $this->seqParam->setSuccessStatus($tmpSuccessStatus, TRUE);
            }
        }
        $retArray = array();
        $retArray['Result'] = TRUE;
        $retArray['NextID'] = '';
        $retArray['Continue'] = FALSE;
        $retArray['Command'] = '';
        return $retArray;
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
        } else {
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

    protected function checkPreCondition($cmd, $id, $val, $isDescending) {
        $this->co_trace();
        $rst = $this->makeCheckResult('', '');
        // まずはコマンドのチェック
        if ($cmd == 'CHOICE') {
            if (!$this->seqParam->getControlModeParam('choice')) {
                return $this->makeCheckResult('error', 'SB.2.9-4');
            }
            $controlModeChoice = $this->getParent()->callFromChild($this->getID(), 'GETVALUE', array('scorm.ControlMode', 'choice'), NULL);
            if (!is_null($controlModeChoice['Value']) and !$controlModeChoice['Value']) {
                return $this->makeCheckResult('error', 'NB.2.1-10');
            }
            if ($this->seqParam->checkAttemptLimitExceeded()) {
                return $this->makeCheckResult('error', 'SB.2.2-2');
            }
        } else if (($cmd == 'CONTINUE') || ($cmd == 'PREVIOUS')) {
            //if (!$this->seqParam->getControlModeParam('flow')) {
            //    $rst = $this->makeCheckResult('error',  $cmd === 'CONTINUE' ? 'SB.2.7-2' : 'SB.2.8-2');
            //    return $rst;
            //}
        }
        // 次に状態のチェック
        $rst = $this->makeCheckResult($this->seqParam->checkPreCondition(), '');
        if ($rst['Result'] == 'stopForwardTraversal') {
            $rst = $this->makeCheckResult('', '');
        }
        if ($rst['Result'] == 'hiddenFromChoice' and $cmd == 'CHOICE') {
            $rst = $this->makeCheckResult('error', 'SB.2.9-3');
        }
        return $rst;
    }

    // ステータスの文字列変更処理
    private function changeSuccessStatusToSS($str) {
        if ($str == 'passed') {
            return 'satisfied';
        } else if ($str == 'failed') {
            return 'not satisfied';
        } else {
            return 'unknown';
        }
    }

    private function changeSuccessStatusToRTM($str) {
        if ($str == 'satisfied') {
            return 'passed';
        } else if ($str == 'not satisfied') {
            return 'failed';
        } else {
            return 'unknown';
        }
    }
}
