<?php

class SimpleSequencing {
    protected $ssID;
    protected $ObjectiveProgressStatus;
    protected $ObjectiveSatisfiedStatus;
    protected $ObjectiveMeasureStatus;
    protected $ObjectiveNormalizedMeasure;
    protected $AttemptProgressStatus;
    //protected $AttemptCompletionAmount;
    protected $AttemptCompletionStatus;

    protected $isSetObj;
    protected $isNew;
    protected $AttemptCount;
    protected $durationSecond;

    // コントロール変数
    protected $controlMode;

    // ルール変数
    protected $preConditionRule = array();
    protected $postConditionRule = array();
    protected $exitConditionRule = array();

    // Limit変数
    protected $limitConditions = array();

    // ロールアップルール
    protected $rollupRules = array();

    protected $primaryObjective = array();
    protected $localObjective = array();
    protected $localObjCount;
    protected $WriteObjList = array();

    protected $deliveryControls = array();
    protected $constrainedChoiceConsiderations = array();
    protected $rollupConsiderations = array();

    protected $ignoreTrace;

    protected $pendingStatus = '';

    function __construct($aID, $node, $ss_status, $ms_status, $cm_status, $cnt, $current, &$Objs) {
        $this->ssID = $aID;
        // コントロールモードのセット
        $this->controlMode = array();
        $tmpNode = selectSingleNode($node,"imsss:controlMode");
        $this->controlMode['choice'] = $tmpNode->getAttribute('choice') == 'true';
        $this->controlMode['choiceExit'] = $tmpNode->getAttribute('choiceExit') == 'true';
        $this->controlMode['flow'] = $tmpNode->getAttribute('flow') == 'true';
        $this->controlMode['forwardOnly'] = $tmpNode->getAttribute('forwardOnly') == 'true';
        $this->controlMode['useCurrentAttemptObjectiveInfo'] = $tmpNode->getAttribute('useCurrentAttemptObjectiveInfo') == 'true';
        $this->controlMode['useCurrentAttemptProgressInfo'] = $tmpNode->getAttribute('useCurrentAttemptProgressInfo') == 'true';

        // ルールのセット
        $tmpNode = selectSingleNode($node,"imsss:sequencingRules");
        if ($tmpNode != null) {
            $tmpList = selectNodes($tmpNode,"imsss:preConditionRule");
            if ($tmpList != null) {
                $len = count($tmpList);
                for ($i=0; $i < $len; $i++) {
                    $actNode = selectSingleNode($tmpList[$i],"imsss:ruleAction");
                    $this->preConditionRule[$i]['action'] = $actNode->getAttribute('action');

                    // 条件
                    $ruleNode = selectSingleNode($tmpList[$i],"imsss:ruleConditions");
                    $tmpStr = $ruleNode->getAttribute('conditionCombination');
                    if ($tmpStr == 'any') {
                        $this->preConditionRule[$i]['conditionCombination'] = 'any';
                    } else {
                        $this->preConditionRule[$i]['conditionCombination'] = 'all';
                    }
                    $ruleList = selectNodes($ruleNode,"imsss:ruleCondition");

                    $rlen = count($ruleList);
                    $tmpAry = array();
                    for ($j=0; $j < $rlen; $j++) {
                        $tmpAry[$j][0] = $ruleList[$j]->getAttribute('referencedObjective');
                        $tmpAry[$j][1] = $ruleList[$j]->getAttribute('measureThreshold');
                        $tmpStr = $ruleList[$j]->getAttribute('operator');
                        if ($tmpStr == 'not') {
                            $tmpAry[$j][2] = 'not';
                        } else {
                            $tmpAry[$j][2] = 'noOp';
                        }
                        $tmpAry[$j][3] = $ruleList[$j]->getAttribute('condition');
                    }
                    $this->preConditionRule[$i]['rules'] = $tmpAry;
                }
            }

            $tmpList = selectNodes($tmpNode,"imsss:exitConditionRule");
            if ($tmpList != null) {
                $len = count($tmpList);
                for ($i=0; $i < $len; $i++) {
                    $actNode = selectSingleNode($tmpList[$i], "imsss:ruleAction");
                    $this->exitConditionRule[$i]['action'] = $actNode->getAttribute('action');

                    // 条件
                    $ruleNode = selectSingleNode($tmpList[$i],"imsss:ruleConditions");
                    $tmpStr = $ruleNode->getAttribute('conditionCombination');
                    if ($tmpStr == 'any') {
                        $this->exitConditionRule[$i]['conditionCombination'] = 'any';
                    } else {
                        $this->exitConditionRule[$i]['conditionCombination'] = 'all';
                    }
                    $ruleList = selectNodes($ruleNode,"imsss:ruleCondition");

                    $rlen = count($ruleList);
                    $tmpAry = array();
                    for ($j=0; $j < $rlen; $j++) {
                        $tmpAry[$j][0] = $ruleList[$j]->getAttribute('referencedObjective');
                        $tmpAry[$j][1] = $ruleList[$j]->getAttribute('measureThreshold');
                        $tmpStr = $ruleList[$j]->getAttribute('operator');
                        if ($tmpStr == 'not') {
                            $tmpAry[$j][2] = 'not';
                        } else {
                            $tmpAry[$j][2] = 'noOp';
                        }
                        $tmpAry[$j][3] = $ruleList[$j]->getAttribute('condition');
                    }
                    $this->exitConditionRule[$i]['rules'] = $tmpAry;
                }
            }

            $tmpList = selectNodes($tmpNode, "imsss:postConditionRule");
            if ($tmpList != null) {
                $len = count($tmpList);
                for ($i=0; $i < $len; $i++) {
                    $actNode = selectSingleNode($tmpList[$i], "imsss:ruleAction");
                    $this->postConditionRule[$i]['action'] = $actNode->getAttribute('action');

                    // 条件
                    $ruleNode = selectSingleNode($tmpList[$i], "imsss:ruleConditions");
                    $tmpStr = $ruleNode->getAttribute('conditionCombination');
                    if ($tmpStr == 'any') {
                        $this->postConditionRule[$i]['conditionCombination'] = 'any';
                    } else {
                        $this->postConditionRule[$i]['conditionCombination'] = 'all';
                    }
                    $ruleList = selectNodes($ruleNode, "imsss:ruleCondition");
                    $rlen = count($ruleList);
                    $tmpAry = array();
                    for ($j=0; $j < $rlen; $j++) {
                        $tmpAry[$j][0] = $ruleList[$j]->getAttribute('referencedObjective');
                        $tmpAry[$j][1] = $ruleList[$j]->getAttribute('measureThreshold');
                        $tmpStr = $ruleList[$j]->getAttribute('operator');
                        if ($tmpStr == 'not') {
                            $tmpAry[$j][2] = 'not';
                        } else {
                            $tmpAry[$j][2] = 'noOp';
                        }
                        $tmpAry[$j][3] = $ruleList[$j]->getAttribute('condition');
                    }
                    $this->postConditionRule[$i]['rules'] = $tmpAry;
                }
            }
        }

        //
        $tmpNode = selectSingleNode($node, "imsss:limitConditions");// あるかわからない
        if ($tmpNode != null) {
            $this->limitConditions['attemptLimit'] = $tmpNode->getAttribute('attemptLimit');
            $this->limitConditions['attemptAbsoluteDurationLimit'] = $tmpNode->getAttribute('attemptAbsoluteDurationLimit');
        } else {
            $this->limitConditions['attemptLimit'] = '';
            $this->limitConditions['attemptAbsoluteDurationLimit'] = '';
        }

        // ロールアップをここに
        $tmpNode = selectSingleNode($node,"imsss:rollupRules");// ない場合もある->必ずある
        $this->rollupRules['rollupObjectiveSatisfied'] = $tmpNode->getAttribute('rollupObjectiveSatisfied') == 'true';
        $this->rollupRules['rollupProgressCompletion'] = $tmpNode->getAttribute('rollupProgressCompletion') == 'true';
        $this->rollupRules['objectiveMeasureWeight'] = floatval($tmpNode->getAttribute('objectiveMeasureWeight'));
        $this->rollupRules['rollupRule'] = array();
        $objFlg = FALSE;
        $actFlg = FALSE;
        foreach (selectNodes($tmpNode, 'imsss:rollupRule') as $n) {
            $tmpRule = array();
            $tmpRule['childActivitySet'] = $n->getAttribute('childActivitySet');
            $tmpRule['minimumCount'] = $n->getAttribute('minimumCount');
            $tmpRule['minimumPercent'] = $n->getAttribute('minimumPercent');

            $rNode = selectSingleNode($n, "imsss:rollupConditions");
            $tmpRule['conditionCombination'] = $rNode->getAttribute('conditionCombination');

            foreach (selectNodes($rNode, 'imsss:rollupCondition') as $nn) {
                $tmpCond = array();
                $tmpCond['operator'] = $nn->getAttribute('operator');
                $tmpCond['condition'] = $nn->getAttribute('condition');
                $tmpRule['rollupConditions'][] = $tmpCond;
            }
            $aNode = selectSingleNode($n, "imsss:rollupAction");
            $tmpRule['action'] = $aNode->getAttribute('action');
            if (($tmpRule['action'] == 'satisfied') || ($tmpRule['action'] == 'notSatisfied')) {
                $objFlg = TRUE;
            }
            if (($tmpRule['action'] == 'completed') || ($tmpRule['action'] == 'incomplete')) {
                $actFlg = TRUE;
            }
            $this->rollupRules['rollupRule'][] = $tmpRule;
        }
        // デフォルトルールの追加
        if (!$objFlg) {
            $tmpRuleNS = array();
            $tmpRuleNS['childActivitySet'] = 'all';
            $tmpRuleNS['minimumCount'] = '0';
            $tmpRuleNS['minimumPercent'] = '0.0';
            $tmpRuleNS['conditionCombination'] = 'any';
            $tmpRuleNS['action'] = 'notSatisfied';

            $tmpCondNS = array();
            $tmpCondNS['operator'] = 'noOp';
            $tmpCondNS['condition'] = 'objectiveStatusKnown';
            $tmpRuleNS['rollupConditions'][] = $tmpCondNS;
            $this->rollupRules['rollupRule'][] = $tmpRuleNS;

            $tmpRuleS = array();
            $tmpRuleS['childActivitySet'] = 'all';
            $tmpRuleS['minimumCount'] = '0';
            $tmpRuleS['minimumPercent'] = '0.0';
            $tmpRuleS['conditionCombination'] = 'any';
            $tmpRuleS['action'] = 'satisfied';

            $tmpCondS = array();
            $tmpCondS['operator'] = 'noOp';
            $tmpCondS['condition'] = 'satisfied';
            $tmpRuleS['rollupConditions'][] = $tmpCondS;
            $this->rollupRules['rollupRule'][] = $tmpRuleS;
        }

        if (!$actFlg) {
            $tmpRuleNS = array();
            $tmpRuleNS['childActivitySet'] = 'all';
            $tmpRuleNS['minimumCount'] = '0';
            $tmpRuleNS['minimumPercent'] = '0.0';
            $tmpRuleNS['conditionCombination'] = 'any';
            $tmpRuleNS['action'] = 'incomplete';

            $tmpCondNS = array();
            $tmpCondNS['operator'] = 'noOp';
            $tmpCondNS['condition'] = 'activityProgressKnown';
            $tmpRuleNS['rollupConditions'][] = $tmpCondNS;
            $this->rollupRules['rollupRule'][] = $tmpRuleNS;

            $tmpRuleS = array();
            $tmpRuleS['childActivitySet'] = 'all';
            $tmpRuleS['minimumCount'] = '0';
            $tmpRuleS['minimumPercent'] = '0.0';
            $tmpRuleS['conditionCombination'] = 'any';
            $tmpRuleS['action'] = 'completed';

            $tmpCondS = array();
            $tmpCondS['operator'] = 'noOp';
            $tmpCondS['condition'] = 'completed';
            $tmpRuleS['rollupConditions'][] = $tmpCondS;
            $this->rollupRules['rollupRule'][] = $tmpRuleS;
        }

        // オブジェクティブのセット
        $tmpNode = selectSingleNode($node, "imsss:objectives");// 必ずある
        $priNode = selectSingleNode($tmpNode, "imsss:primaryObjective");// 必ずある
        $this->primaryObjective['satisfiedByMeasure'] = FALSE;
        if ($priNode->getAttribute('satisfiedByMeasure') == 'true') {
            $this->primaryObjective['satisfiedByMeasure'] = TRUE;
        }
        $this->primaryObjective['objectiveID'] = $priNode->getAttribute('objectiveID');// 空の場合もある。
        $minNode = selectSingleNode($priNode, "imsss:minNormalizedMeasure");// 必ずある

        $this->primaryObjective['minNormalizedMeasure'] = $minNode->nodeValue;
        $this->primaryObjective['mapInfo'] = array();

        foreach (selectNodes($priNode, "imsss:mapInfo") as $nn) {
            $tmpMap = array();
            $tmpMap['targetObjectiveID'] = $nn->getAttribute('targetObjectiveID');

            if ($nn->getAttribute('readSatisfiedStatus') == 'true') {
                $tmpMap['readSatisfiedStatus'] = TRUE;
                //$Objs[$tmpMap['targetObjectiveID']]->addReadActivity($aID,'readSatisfiedStatus');
                $Objs[$tmpMap['targetObjectiveID']]->addReadActivityId($aID);
            } else {
                $tmpMap['readSatisfiedStatus'] = FALSE;
            }
            if ($nn->getAttribute('readNormalizedMeasure') == 'true') {
                $tmpMap['readNormalizedMeasure'] = TRUE;
                //$Objs[$tmpMap['targetObjectiveID']]->addReadActivity($aID,'readNormalizedMeasure');
                $Objs[$tmpMap['targetObjectiveID']]->addReadActivityId($aID);
            } else {
                $tmpMap['readNormalizedMeasure'] = FALSE;
            }
//WriteObjList
            if ($nn->getAttribute('writeSatisfiedStatus') == 'true') {
                $tmpMap['writeSatisfiedStatus'] = TRUE;
                $this->WriteObjList[] = $tmpMap['targetObjectiveID'];
            } else {
                $tmpMap['writeSatisfiedStatus'] = FALSE;
            }

            if ($nn->getAttribute('writeNormalizedMeasure') == 'true') {
                $tmpMap['writeNormalizedMeasure'] = TRUE;
                $this->WriteObjList[] = $tmpMap['targetObjectiveID'];
            } else {
                $tmpMap['writeNormalizedMeasure'] = FALSE;
            }
            $this->primaryObjective['mapInfo'][] = $tmpMap;
        }

        $objCnt = 0;
        foreach (selectNodes($tmpNode, "imsss:objective") as $n) {

            // タグがあればIDはある。プライマリーはないときもある
            $this->localObjective[$objCnt]['satisfiedByMeasure'] = $n->getAttribute('satisfiedByMeasure') == 'true';
            $this->localObjective[$objCnt]['objectiveID'] = $n->getAttribute('objectiveID');

            // ステータスの初期化
            $this->localObjective[$objCnt]['ObjectiveProgressStatus'] = FALSE;
            $this->localObjective[$objCnt]['ObjectiveSatisfiedStatus'] = FALSE;
            $this->localObjective[$objCnt]['ObjectiveMeasureStatus'] = FALSE;
            $this->localObjective[$objCnt]['ObjectiveNormalizedMeasure'] = 0;
            $this->localObjective[$objCnt]['AttemptProgressStatus'] = FALSE;
            $this->localObjective[$objCnt]['AttemptCompletionStatus'] = FALSE;
//          $this->localObjective[$objCnt]['AttemptProgressMeasure'] = '';
//  protected $AttemptCompletionAmount;

            $minNode = selectSingleNode($n, "imsss:minNormalizedMeasure");// 必ずある
            $this->localObjective[$objCnt]['minNormalizedMeasure'] = $minNode->nodeValue;
            $this->localObjective[$objCnt]['mapInfo'] = array();

            foreach (selectNodes($n, "imsss:mapInfo") as $nn) {
                $tmpMap = array();
                $tmpMap['targetObjectiveID'] = $nn->getAttribute('targetObjectiveID');

                if ($nn->getAttribute('readSatisfiedStatus') == 'true') {
                    $tmpMap['readSatisfiedStatus'] = TRUE;
                    //$Objs[$tmpMap['targetObjectiveID']]->addReadActivity($aID,'readSatisfiedStatus');
                    $Objs[$tmpMap['targetObjectiveID']]->addReadActivityId($aID);
                } else {
                    $tmpMap['readSatisfiedStatus'] = FALSE;
                }

                if ($nn->getAttribute('readNormalizedMeasure') == 'true') {
                    $tmpMap['readNormalizedMeasure'] = TRUE;
                    //$Objs[$tmpMap['targetObjectiveID']]->addReadActivity($aID,'readNormalizedMeasure');
                    $Objs[$tmpMap['targetObjectiveID']]->addReadActivityId($aID);
                } else {
                    $tmpMap['readNormalizedMeasure'] = FALSE;
                }
                if ($nn->getAttribute('writeSatisfiedStatus') == 'true') {
                    $tmpMap['writeSatisfiedStatus'] = TRUE;
                    $this->WriteObjList[] = $tmpMap['targetObjectiveID'];
                } else {
                    $tmpMap['writeSatisfiedStatus'] = FALSE;
                }

                if ($nn->getAttribute('writeNormalizedMeasure') == 'true') {
                    $tmpMap['writeNormalizedMeasure'] = TRUE;
                    $this->WriteObjList[] = $tmpMap['targetObjectiveID'];
                } else {
                    $tmpMap['writeNormalizedMeasure'] = FALSE;
                }
                $this->localObjective[$objCnt]['mapInfo'][] = $tmpMap;
            }
            $objCnt++;
        }
        $this->localObjCount = $objCnt;
        $this->WriteObjList = array_unique($this->WriteObjList);

        $tmpNode = selectSingleNode($node, "imsss:deliveryControls");
        $this->deliveryControls['tracked'] = $tmpNode->getAttribute('tracked') == 'true';
        $this->deliveryControls['completionSetByContent'] = $tmpNode->getAttribute('completionSetByContent') == 'true';
        $this->deliveryControls['objectiveSetByContent'] = $tmpNode->getAttribute('objectiveSetByContent') == 'true';

        $tmpNode = selectSingleNode($node, "adlseq:constrainedChoiceConsiderations");
        $this->constrainedChoiceConsiderations['preventActivation'] = $tmpNode->getAttribute('preventActivation') == 'true';
        $this->constrainedChoiceConsiderations['constrainChoice'] = $tmpNode->getAttribute('constrainChoice') == 'true';

        $tmpNode = selectSingleNode($node, "adlseq:rollupConsiderations");
        $this->rollupConsiderations['requiredForSatisfied'] = $tmpNode->getAttribute('requiredForSatisfied');
        $this->rollupConsiderations['requiredForNotSatisfied'] = $tmpNode->getAttribute('requiredForNotSatisfied');
        $this->rollupConsiderations['requiredForCompleted'] = $tmpNode->getAttribute('requiredForCompleted');
        $this->rollupConsiderations['requiredForIncomplete'] = $tmpNode->getAttribute('requiredForIncomplete');
        $this->rollupConsiderations['measureSatisfactionIfActive'] = $tmpNode->getAttribute('measureSatisfactionIfActive');

        $dummyArry = array();
        $this->setSuccessStatus($ss_status, $dummyArry, FALSE);// 初期値はObjectまで伝播せず
        $this->setScaledScore($ms_status, $dummyArry, FALSE);
        $this->setCompletionStatus($cm_status);
        $this->AttemptCount = $cnt;
        $this->durationSecond = 0;
        $this->isNew = $current;

        $this->ignoreTrace = FALSE;
    }

    public function getWriteObjectiveIdArray() {
        $this->trace();
        return $this->WriteObjList;
    }

    public function getPassingScore() {
        $this->trace();
        if ($this->primaryObjective['satisfiedByMeasure']) {
            return $this->primaryObjective['minNormalizedMeasure'];
        } else {
            return '';
        }
    }

    public function setCurrentStatus($flag){
        $this->trace();
        $this->isNew = $flag;
    }

    public function getCurrentStatus(){
        $this->trace();
        return $this->isNew ;
    }

    public function setDurationSecond($value) {
        $this->trace();
        $this->durationSecond = $value;
    }

    public function addAttemptCount(){
        $this->trace();
        $this->AttemptCount++;
        $this->ObjectiveProgressStatus = FALSE;
        $this->ObjectiveSatisfiedStatus = FALSE;
        $this->ObjectiveMeasureStatus = FALSE;
        $this->ObjectiveNormalizedMeasure = 0;
        $this->isSetObj = FALSE;
        $this->AttemptProgressStatus = FALSE;
        $this->AttemptCompletionStatus = FALSE;
        $len = $this->getLocalObjectiveCount();
        for ($i=0; $i < $len; $i++) {
            $this->localObjective[$i]['ObjectiveProgressStatus'] = FALSE;
            $this->localObjective[$i]['ObjectiveSatisfiedStatus'] = FALSE;
            $this->localObjective[$i]['ObjectiveMeasureStatus'] = FALSE;
            $this->localObjective[$i]['ObjectiveNormalizedMeasure'] = 0;
            $this->localObjective[$i]['AttemptProgressStatus'] = FALSE;
            $this->localObjective[$i]['AttemptCompletionStatus'] = FALSE;
        }
    }

//id,score,success_status,completion_status,progress_measure,description
    public function getPrimaryObjectiveID() {
        $this->trace();
        return $this->primaryObjective['objectiveID'];
    }

    public function getLocalObjectiveCount() {
        $this->trace();
        return $this->localObjCount;
    }

    private function searchObjNum($str){
        $len = $this->getLocalObjectiveCount();
        for ($i=0; $i < $len; $i++) {
            if ($this->localObjective[$i]['objectiveID'] == $str) {
                return $i;
            }
        }
        return -1;
    }

    public function getLocalObjectiveData($num, $isReal, $for_rtm = FALSE){
        $this->trace();
        $retStr = $this->localObjective[$num]['objectiveID'] . ",";
        $success_status = $this->getLocalSuccessStatus($num, $isReal);
        if ($for_rtm) {
            $success_status = $this->conv_for_RTM($success_status);
        }
        $retStr .= $success_status . ",";
        $retStr .= $this->getLocalScaledScore($num, $isReal) . ",";
        $retStr .= $this->getLocalCompletionStatus($num) . ",";
        $retStr .= ",\n";//objectの進捗率はないしつかわない
        return $retStr;
    }

    private static function conv_for_RTM($success_status) {
        if ($success_status == 'satisfied') {
            return 'passed';
        } else if ($success_status == 'not satisfied') {
            return 'failed';
        } else {
            return 'unknown';
        }
    }

    private function appendLocalObjective($objectiveID) {
        $len = $this->getLocalObjectiveCount();
        $this->localObjective[$len]['objectiveID'] = $objectiveID;
        $this->localObjective[$len]['ObjectiveProgressStatus']    = FALSE;
        $this->localObjective[$len]['ObjectiveSatisfiedStatus']   = FALSE;
        $this->localObjective[$len]['ObjectiveMeasureStatus']     = FALSE;
        $this->localObjective[$len]['ObjectiveNormalizedMeasure'] = 0;
        $this->localObjective[$len]['AttemptProgressStatus']      = FALSE;
        $this->localObjective[$len]['AttemptCompletionStatus']    = FALSE;
        $this->localObjCount = $this->localObjCount + 1;
    }

    function setLocalObjectiveData($objAry, $tracked) {
        $this->trace();
        if ((count($objAry) < 4) || ($objAry[0] == '')) {
            return FALSE;
        }
        //if (!$tracked) {
        //    return TRUE;
        //}
        //$isReal = TRUE;
        $isReal = $tracked;
        if ($this->getPrimaryObjectiveID() == $objAry[0]) {       // PRIMARYチェックをいれる。
            if ($objAry[1] != "unknown") { $this->setSuccessStatus($objAry[1], $isReal); }
            if ($objAry[2] != "") { $this->setScaledScore($objAry[2], $isReal); }
            if ($objAry[3] != "unknown") { $this->setCompletionStatus($objAry[3]); }
        } else {
            $times = array(1,2);
            foreach ($times as $dummy) {
                $tgNum = $this->searchObjNum($objAry[0]);
                if ($tgNum != -1) {
                    if ($objAry[1] != "unknown") { $this->setLocalSuccessStatus($tgNum, $objAry[1], $isReal); }
                    if ($objAry[2] != "") { $this->setLocalScaledScore($tgNum, $objAry[2], $isReal); }
                    if ($objAry[3] != "unknown") { $this->setLocalCompletionStatus($tgNum, $objAry[3]); }
                    break;
                } else {
                    // append objective and retry
                    $this->appendLocalObjective($objAry[0]);
                }
            }
        }
    }

    private function searchObjMap($mapAry, $str) {
        $len = count($mapAry);
        for ($i = 0; $i < $len; $i++) {
            if ($mapAry[$i][$str]) {
                return $mapAry[$i]['targetObjectiveID'];
            }
        }
        return '';
    }

    private function searchObjWriteMap($mapAry, $str) {
        $retAry = array();
        $len = count($mapAry);
        for ($i=0; $i < $len; $i++) {
            if ($mapAry[$i][$str]) {
                $retAry[] = $mapAry[$i]['targetObjectiveID'];
            }
        }
        return $retAry;
    }

    // $isReal:学習目標に伝播させるかどうかのフラグ
    public function getSuccessStatus($isReal) {
        $this->trace();
        $platform = Platform::getInstance();
        if ($isReal) { //かつマップがあれば
            $mapAry = $this->primaryObjective['mapInfo'];
            if ($this->primaryObjective['satisfiedByMeasure']) {
                $gObjID = $this->searchObjMap($mapAry, 'readNormalizedMeasure');
                if ($gObjID != '') {
                    //$sScore = $platform->getObjective($gObjID)->getScaledScore();
                    $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                    $sScore = $platform->getObjective($gObjID)->getValue()['ScaledScore'];
                    if ($sScore !== '') {
                        $dScore = floatval($sScore);
                        $bBase = floatval($this->primaryObjective['minNormalizedMeasure']);
                        if ($dScore >= $bBase) {
                            return 'satisfied';
                        } else {
                            return 'not satisfied';
                        }
                    }
                }
            } else {
                $gObjID = $this->searchObjMap($mapAry, 'readSatisfiedStatus');
                if ($gObjID != '') {
                    //$retStr = $platform->getObjective($gObjID)->getSuccessStatus();
                    $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                    $value = $platform->getObjective($gObjID)->getValue();
                    if (isset($value['SuccessStatus'])) {
                        $retStr = $value['SuccessStatus'];
                        if ($retStr != 'unknown') {
                            return $retStr;
                        }
                    }
                }
            }
        }

        if ($this->ObjectiveProgressStatus) {
            if ($this->ObjectiveSatisfiedStatus) {
                return 'satisfied';
            } else {
                return 'not satisfied';
            }
        }
        return 'unknown';
    }

    private function getLocalSuccessStatus($num, $isReal){
        $platform = Platform::getInstance();

        $retStr = 'unknown';
        if (($retStr == 'unknown') && $isReal) { //かつマップがあれば
            $mapAry = $this->localObjective[$num]['mapInfo'];
            if ($this->localObjective[$num]['satisfiedByMeasure']) {
                $gObjID = $this->searchObjMap($mapAry, 'readNormalizedMeasure');
                if ($gObjID != '') {
                    $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                    $sScore = $platform->getObjective($gObjID)->getValue()['ScaledScore'];
                    if ($sScore !== '') {
                        $dScore = floatval($sScore);
                        $bBase = floatval($this->localObjective[$num]['minNormalizedMeasure']);
                        if ($dScore >= $bBase) {
                            $retStr = 'satisfied';
                        } else {
                            $retStr = 'not satisfied';
                        }
                    }
                }
            } else {
                if ($retStr == 'unknown') {
                    $gObjID = $this->searchObjMap($mapAry, 'readSatisfiedStatus');
                    if ($gObjID != '') {
                        $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                        $retStr = $platform->getObjective($gObjID)->getValue()['SuccessStatus'];
                    }
                }
            }
        }

        if ($retStr == 'unknown') {
            if ($this->localObjective[$num]['ObjectiveProgressStatus']) {
                if ($this->localObjective[$num]['ObjectiveSatisfiedStatus']) {
                    $retStr = 'satisfied';
                } else {
                    $retStr = 'not satisfied';
                }
            }
        }
        return $retStr;
    }

    private function getSuccessStatusForRR($isCurrent) {
        $platform = Platform::getInstance();

        $retStr = 'unknown';
        if ($this->isNew && $isCurrent) { // まずはカレントを見る
        } else {
            if (!$this->ObjectiveProgressStatus) {
            } else {
                if ($this->ObjectiveSatisfiedStatus) {
                    $retStr = 'satisfied';
                } else {
                    $retStr = 'not satisfied';
                }
            }
        }

        if ($retStr == 'unknown') { //かつマップがあれば
            if ($this->primaryObjective['satisfiedByMeasure']) {
                $mapAry = $this->primaryObjective['mapInfo'];
                $gObjID = $this->searchObjMap($mapAry, 'readNormalizedMeasure');
                if ($gObjID != '') {
                    $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                    $sScore = $platform->getObjective($gObjID)->getValue()['ScaledScore'];
                    if ($sScore !== '') {
                        $dScore = floatval($sScore);
                        $bBase = floatval($this->primaryObjective['minNormalizedMeasure']);
                        if ($dScore >= $bBase) {
                            $retStr = 'satisfied';
                        } else {
                            $retStr = 'not satisfied';
                        }
                    }
                }
            }
        }

        if ($retStr == 'unknown') {
            $mapAry = $this->primaryObjective['mapInfo'];
            $gObjID = $this->searchObjMap($mapAry, 'readSatisfiedStatus');
            if ($gObjID != '') {
                $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                $value = $platform->getObjective($gObjID)->getValue();
                if (isset($value['SuccessStatus'])) {
                    $retStr = $value['SuccessStatus'];
                }
            }
        }
        return $retStr;
    }

    public function setSuccessStatusFromRTM($str, $isReal) {
        $this->trace();
        $this->isSetObj = TRUE;
        $this->setSuccessStatus($str, $isReal);
    }

    public function setSuccessStatus($str, $isReal) {
        $this->log('setSuccessStatus: ' . $this->ssID . ':' . ($this->ObjectiveProgressStatus ? 'true':'false') . ', ' . ($this->ObjectiveSatisfiedStatus ? 'true':'false'), false, true);
        $this->trace();
        $retStr = $str;

        if ($retStr == 'satisfied') {
            $this->ObjectiveProgressStatus = TRUE;
            $this->ObjectiveSatisfiedStatus = TRUE;
        } else if ($retStr == 'not satisfied' || $retStr == 'notSatisfied') {
            $this->ObjectiveProgressStatus = TRUE;
            $this->ObjectiveSatisfiedStatus = FALSE;
        } else {
            $this->ObjectiveProgressStatus = FALSE;
            $this->ObjectiveSatisfiedStatus = FALSE;
        }

        if ($isReal and $retStr !== 'unknown') {
            $platform = Platform::getInstance();
            $mapAry = $this->primaryObjective['mapInfo'];
            $tmpAry = $this->searchObjWriteMap($mapAry, 'writeSatisfiedStatus');
            foreach ($tmpAry as $oID) {
                $this->log('setValue:' . $this->ssID . ', ' . $oID, FALSE, TRUE);
                $retStr = $platform->getObjective($oID)->setValue(array('SuccessStatus' => $retStr));
            }
        }
        $this->log('setSuccessStatus: ' . $this->ssID . ':' . ($this->ObjectiveProgressStatus ? 'true':'false') . ', ' . ($this->ObjectiveSatisfiedStatus ? 'true':'false'), false, true);
    }

    private function setLocalSuccessStatus($num, $str, $isReal) {
        if ($str == 'satisfied') {
            $this->localObjective[$num]['ObjectiveProgressStatus'] = TRUE;
            $this->localObjective[$num]['ObjectiveSatisfiedStatus'] = TRUE;
        } else if ($str == 'not satisfied' || $str == 'notSatisfied'){
            $this->localObjective[$num]['ObjectiveProgressStatus'] = TRUE;
            $this->localObjective[$num]['ObjectiveSatisfiedStatus'] = FALSE;
        } else {
            $this->localObjective[$num]['ObjectiveProgressStatus'] = FALSE;
            $this->localObjective[$num]['ObjectiveSatisfiedStatus'] = FALSE;
        }

        if ($isReal) {
            $platform = Platform::getInstance();
            $mapAry = $this->localObjective[$num]['mapInfo'];
            $tmpAry = $this->searchObjWriteMap($mapAry, 'writeSatisfiedStatus');
            foreach ($tmpAry as $oID) {
                $this->log('setValue:' . $this->ssID . ', ' . $oID, FALSE, TRUE);
                $retStr = $platform->getObjective($oID)->setValue(array('SuccessStatus' => $str));
            }
        }
    }

    public function getScaledScore($isReal) {
        $this->trace();
        $retStr = '';
        if ($this->ObjectiveMeasureStatus) {
            $retStr = $this->ObjectiveNormalizedMeasure;
        }
        if (($retStr === '') && $isReal) { //かつマップがあれば
            $mapAry = $this->primaryObjective['mapInfo'];
            $gObjID = $this->searchObjMap($mapAry, 'readNormalizedMeasure');
            if ($gObjID != '') {
                $platform = Platform::getInstance();
                $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                $value = $platform->getObjective($gObjID)->getValue();
                if (isset($value['ScaledScore'])) {
                    $retStr = $value['ScaledScore'];
                }
            }
        }
        return $retStr;
    }

    private function getLocalScaledScore($num, $isReal) {
        $retStr = '';
        if ($isReal) {
            $platform = Platform::getInstance();
            $mapAry = $this->localObjective[$num]['mapInfo'];
            $gObjID = $this->searchObjMap($mapAry, 'readNormalizedMeasure');
            if ($gObjID != '') {
                $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                $retStr = $platform->getObjective($gObjID)->getValue()['ScaledScore'];
            }
        }
        if ($retStr === '') {
            if ($this->localObjective[$num]['ObjectiveMeasureStatus']) {
                $retStr = $this->localObjective[$num]['ObjectiveNormalizedMeasure'];
            }
        }
        return $retStr;
    }

    public function getScaledScoreForRR($isCurrent) {
        $this->trace();
        $platform = Platform::getInstance();

        $retStr = '';
        if ($this->isNew && $isCurrent) {
        } else {
            if ($this->ObjectiveMeasureStatus) {
                $retStr = $this->ObjectiveNormalizedMeasure;
            } else {
$this->trace('  return empty.');
                return '';
            }
        }
        if ($retStr === '') { //かつマップがあれば
            $mapAry = $this->primaryObjective['mapInfo'];
            $gObjID = $this->searchObjMap($mapAry, 'readNormalizedMeasure');
            if ($gObjID != '') {
                $this->log('getValue:' . $this->ssID . ', ' . $gObjID, FALSE, TRUE);
                $value = $platform->getObjective($gObjID)->getValue();
                if (isset($value['ScaledScore'])) {
                    $retStr = $value['ScaledScore'];
                }
            }
        }
$this->trace('  return=' . $retStr);
        return $retStr;
    }

    public function setScaledScore($str, $isReal) {
        $this->trace();
        if ($str === '') {
            $this->ObjectiveMeasureStatus = FALSE;
            // FIX: OB-03b
            //$this->ObjectiveNormalizedMeasure = 0;
            $this->ObjectiveNormalizedMeasure = '';
        } else {
            $this->ObjectiveMeasureStatus = TRUE;
            $this->ObjectiveNormalizedMeasure = $str;

            // FIX: OB-03b
            //if ($isReal) {
            //    $platform = Platform::getInstance();
            //    $mapAry = $this->primaryObjective['mapInfo'];
            //    $tmpAry = $this->searchObjWriteMap($mapAry, 'writeNormalizedMeasure');
            //    foreach ($tmpAry as $oID) {
            //        //$retStr = $platform->getObjective($oID)->setScaledScore($str);
            //        $retStr = $platform->getObjective($oID)->setValue(array('ScaledScore' => $str));
            //    }
            //}
            //// さらに判定
            //if ($this->primaryObjective['satisfiedByMeasure']) {
            //    $dScore = floatval($str);
            //    $bBase = floatval($this->primaryObjective['minNormalizedMeasure']);
            //    if ($dScore >= $bBase) {
            //        $retStr = 'satisfied';
            //    } else {
            //        $retStr = 'not satisfied';
            //    }
            //    $this->setSuccessStatus($retStr, $isReal);
            //}
        }

        //FIX: OB-03b
        if ($isReal) {
            $platform = Platform::getInstance();
            $mapAry = $this->primaryObjective['mapInfo'];
            $tmpAry = $this->searchObjWriteMap($mapAry, 'writeNormalizedMeasure');
            foreach ($tmpAry as $oID) {
                $this->log('setValue:' . $this->ssID . ', ' . $oID, FALSE, TRUE);
                $retStr = $platform->getObjective($oID)->setValue(array('ScaledScore' => $str));
            }
        }
        if ($str !== '' and $this->primaryObjective['satisfiedByMeasure']) {
            $dScore = floatval($str);
            $bBase = floatval($this->primaryObjective['minNormalizedMeasure']);
            if ($dScore >= $bBase) {
                $retStr = 'satisfied';
            } else {
                $retStr = 'not satisfied';
            }
            $this->setSuccessStatus($retStr, $isReal);
        }
    }

    private function setLocalScaledScore($num, $str, $isReal) {
        if ($str === '') {
            $this->localObjective[$num]['ObjectiveMeasureStatus'] = FALSE;
            $this->localObjective[$num]['ObjectiveNormalizedMeasure'] = 0;
        } else {
            $this->localObjective[$num]['ObjectiveMeasureStatus'] = TRUE;
            $this->localObjective[$num]['ObjectiveNormalizedMeasure'] = $str;

            if ($isReal) {
                $platform = Platform::getInstance();
                $mapAry = $this->localObjective[$num]['mapInfo'];
                $tmpAry = $this->searchObjWriteMap($mapAry, 'writeNormalizedMeasure');
                foreach ($tmpAry as $oID) {
                    //$retStr = $platform->getObjective($oID)->setScaledScore($str);
                    $this->log('setValue:' . $this->ssID . ', ' . $oID, FALSE, TRUE);
                    $retStr = $platform->getObjective($oID)->setValue(array('ScaledScore' => $str));
                }
            }

            if ($this->localObjective[$num]['satisfiedByMeasure']) {
                $dScore = floatval($str);
                $bBase = floatval($this->localObjective[$num]['minNormalizedMeasure']);
                if ($dScore >= $bBase) {
                    $retStr = 'satisfied';
                } else {
                    $retStr = 'not satisfied';
                }
                $this->setLocalSuccessStatus($num, $retStr, $isReal);
            }

        }
    }

    public function getCompletionStatus() {
        $this->trace();
        if (!$this->AttemptProgressStatus) {
            return 'unknown';
        } else {
            if ($this->AttemptCompletionStatus) {
                return 'completed';
            } else {
                return 'incomplete';
            }
        }
    }

    private function getLocalCompletionStatus($num) {
        if (!$this->localObjective[$num]['AttemptProgressStatus']) {
            return 'unknown';
        } else {
            if ($this->localObjective[$num]['AttemptCompletionStatus']) {
                return 'completed';
            } else {
                return 'incomplete';
            }
        }
    }

    private function getCompletionStatusForRR($isCurrent) {
        if ($this->isNew && $isCurrent) {
            return 'unknown';
        } else {
            if (!$this->AttemptProgressStatus) {
                return 'unknown';
            } else {
                if ($this->AttemptCompletionStatus) {
                    return 'completed';
                } else {
                    return 'incomplete';
                }
            }
        }
    }

    public function setCompletionStatusFromRTM($status) {
        $this->trace();
        if ($status == 'not attempted') {
            $this->isSetObj = TRUE;
        }
        $this->setCompletionStatus($status);
    }

    public function setCompletionStatus($str) {
        $this->trace();
        $this->log('setCompletionStatus: ' . $this->ssID . ':' . ($this->AttemptProgressStatus ? 'true':'false') . ', ' . ($this->AttemptCompletionStatus ? 'true':'false'), false, true);
        if ($str == 'completed') {
            $this->AttemptProgressStatus = TRUE;
            $this->AttemptCompletionStatus =  TRUE;
        } else if($str == 'incomplete') {
            $this->AttemptProgressStatus = TRUE;
            $this->AttemptCompletionStatus =  FALSE;
        } else if ($str == 'not attempted') { //REQ_59.4.4
            $this->AttemptProgressStatus = TRUE;
            $this->AttemptCompletionStatus =  FALSE;
        } else {
            $this->AttemptProgressStatus = FALSE;
            $this->AttemptCompletionStatus =  FALSE;
        }
        $this->log('setCompletionStatus: ' . $this->ssID . ':' . ($this->AttemptProgressStatus ? 'true':'false') . ', ' . ($this->AttemptCompletionStatus ? 'true':'false'), false, true);
    }

    private function setLocalCompletionStatus($num, $str) {
        if ($str == 'completed') {
            $this->localObjective[$num]['AttemptProgressStatus'] = TRUE;
            $this->localObjective[$num]['AttemptCompletionStatus'] =  TRUE;
        } else if ($str == 'incomplete') {
            $this->localObjective[$num]['AttemptProgressStatus'] = TRUE;
            $this->localObjective[$num]['AttemptCompletionStatus'] =  FALSE;
        } else {
            $this->localObjective[$num]['AttemptProgressStatus'] = FALSE;
            $this->localObjective[$num]['AttemptCompletionStatus'] =  FALSE;
        }
    }

    public function setStatusFin() {
        $this->trace();
        $platform = Platform::getInstance();

        if ($this->isSetObj) {
            return;
        }
        if ($this->deliveryControls['tracked']) {
            if (!$this->deliveryControls['completionSetByContent']) {
                if (!$this->AttemptProgressStatus) {
                    $this->AttemptCompletionStatus = TRUE;
                }
            }
            $this->AttemptProgressStatus = TRUE;
            if (!$this->deliveryControls['objectiveSetByContent']) {
                // まずはprimary
                $sTmp = $this->getSuccessStatus(TRUE);
                if ($sTmp == 'unknown') {
                    $this->setSuccessStatus("satisfied", TRUE);
                }
            }
        }
    }

    public function getControlModeParam($str) {
        $this->trace();
        if (array_key_exists($str, $this->controlMode)) {
            return $this->controlMode[$str];
        } else {
            return FALSE;
        }
    }

    public function getDeliveryControlsParam($str) {
        $this->trace();
        if (array_key_exists($str, $this->deliveryControls)) {
            return $this->deliveryControls[$str];
        } else {
            return FALSE;
        }
    }

    public function getConstrainedChoiceConsiderationsParam($str) {
        $this->trace();
        if (array_key_exists($str, $this->constrainedChoiceConsiderations)) {
            return $this->constrainedChoiceConsiderations[$str];
        } else {
            return FALSE;
        }
    }

    public function getRollupObjectiveMeasureWeight() {
        $this->trace();
        return $this->rollupRules['objectiveMeasureWeight'];
    }

    private function getRollupRulesParam($str) {
        return $this->rollupRules[$str];
    }

    private function getRollupConsiderations($str) {
        return $this->rollupConsiderations[$str];
    }

    public function getAttemptAbsoluteDurationLimit() {
        $this->trace();
        return $this->limitConditions['attemptAbsoluteDurationLimit'];
    }

    public function exeObjectiveRollupUsingMeasure($isActive) {
        $this->trace();
        $isUseParam = FALSE;
        if ($this->primaryObjective['satisfiedByMeasure']) {
            $isUseParam = TRUE;
            if (!$this->ObjectiveMeasureStatus) {
                $this->setSuccessStatus("unknown", TRUE);
            } else {
                if (!$isActive || ($this->rollupConsiderations['measureSatisfactionIfActive'] == 'true')) {
                    $this->exePrimaryObjectiveStatus(FALSE);
                } else {
                    $this->setSuccessStatus("unknown", TRUE);
                    $this->exePrimaryObjectiveStatus(TRUE);
                }
            }
        }
        return $isUseParam;
    }

    public function fixPendingStatus() {
        if ($this->pendingStatus != '') {
            $this->setSuccessStatus($this->pendingStatus, TRUE);
            $this->pendingStatus = '';
        }
    }

    private function exePrimaryObjectiveStatus($isPending) {
        $dScore = floatval($this->getScaledScore(TRUE));
        $dBase = floatval($this->primaryObjective['minNormalizedMeasure']);

        $status = ($dBase <= $dScore) ? 'satisfied' : 'not satisfied';
        if ($isPending) {
            $this->pendingStatus = $status;
        } else {
            $this->setSuccessStatus($status, TRUE);
        }
    }

    public function getRollupRules($str) {
        $this->trace();
        $retAry = array();
        $tmpAry = $this->rollupRules['rollupRule'];
        $len = count($tmpAry);
        for ($i=0; $i < $len; $i++) {
            if ($tmpAry[$i]['action'] == $str) {
                $retAry[] = $tmpAry[$i];
            }
        }
        return $retAry;
    }

    // SKIPの前提条件だけを調べる
    private function checkPreConditionSkip() {
        $this->trace();
        if ($this->deliveryControls['tracked']) {
            $len = count($this->preConditionRule);
            for ($i=0; $i < $len; $i++) {
                $targetAction = $this->preConditionRule[$i]['action'];

                if ($targetAction == 'skip') {
                    $targetConv = $this->preConditionRule[$i]['conditionCombination'];
                    $targeetArray = $this->preConditionRule[$i]['rules'];
                    $rlen = count($targeetArray);
                    if ($targetConv == 'all') {
                        $rst = TRUE;
                    } else {
                        $rst = FALSE;
                    }

                    for ($j=0; $j < $rlen; $j++) {// 最初に見つかった条件で処理は終了予定
                        $retFlg = $this->checkCondition($targeetArray[$j]);
                        if (($retFlg != 1) && ($targetConv == 'all')) {
                            $rst = FALSE;
                            break;
                        }
                        if (($retFlg == 1) && ($targetConv == 'any')) {
                            $rst = TRUE;
                            break;
                        }
                    }
                    if ($rst) {
                        return TRUE;
                    }
                }
            }
        }
        return FALSE;
    }

    public function checkPreCondition() {
        $this->trace();
        $retStr = '';
        if ($this->deliveryControls['tracked']) {
            $len = count($this->preConditionRule);
            for ($i = 0; $i < $len; $i++) {
                $targetAction = $this->preConditionRule[$i]['action'];
                $targetConv = $this->preConditionRule[$i]['conditionCombination'];
                $targeetArray = $this->preConditionRule[$i]['rules'];
                $rlen = count($targeetArray);
$this->trace($rlen);
                if ($targetConv == 'all') {
                    $rst = TRUE;
                } else {
                    $rst = FALSE;
                }

                for ($j = 0; $j < $rlen; $j++) {// 最初に見つかった条件で処理は終了予定
$this->trace($targeetArray[$j][0]);
$this->trace($targeetArray[$j][1]);
$this->trace($targeetArray[$j][2]);
$this->trace($targeetArray[$j][3]);
                    $retFlg = $this->checkCondition($targeetArray[$j]);
                    if (($retFlg != 1) && ($targetConv == 'all')) {
                        $rst = FALSE;
                        break;
                    }
                    if (($retFlg == 1) && ($targetConv == 'any')) {
                        $rst = TRUE;
                        break;
                    }
                }
                if ($rst) {
                    $retStr = $targetAction;
                    break;
                }
            }
        }
        return $retStr;
    }

    public function checkExitCondition() {
        $this->trace();
        $retStr = '';
        $len = count($this->exitConditionRule);
        for ($i=0; $i < $len; $i++) {
            $targetAction = $this->exitConditionRule[$i]['action'];
            $targetConv = $this->exitConditionRule[$i]['conditionCombination'];
            $targeetArray = $this->exitConditionRule[$i]['rules'];
            $rlen = count($targeetArray);
            if ($targetConv == 'all') {
                $rst = TRUE;
            } else {
                $rst = FALSE;
            }
            for ($j=0; $j < $rlen; $j++) {// 最初に見つかった条件で処理は終了予定
                $retFlg = $this->checkCondition($targeetArray[$j]);
                if (($retFlg != 1) && ($targetConv == 'all')) {
                    $rst = FALSE;
                    break;
                }
                if (($retFlg == 1) && ($targetConv == 'any')) {
                    $rst = TRUE;
                    break;
                }
            }
            if ($rst) {
                $retStr = $targetAction;
                break;
            }
        }
        return $retStr;
    }

    public function checkPostCondition() {
        $this->trace();
        $retStr = '';
        $len = count($this->postConditionRule);
        for ($i = 0; $i < $len; $i++) {
            $targetAction = $this->postConditionRule[$i]['action'];
            $targetConv = $this->postConditionRule[$i]['conditionCombination'];
            $targeetArray = $this->postConditionRule[$i]['rules'];
            $rlen = count($targeetArray);

            if ($targetConv == 'all') {
                $rst = TRUE;
            } else {
                $rst = FALSE;
            }

            for ($j = 0; $j < $rlen; $j++) { // 最初に見つかった条件で処理は終了予定
                $retFlg = $this->checkCondition($targeetArray[$j]);
                if (($retFlg != 1) && ($targetConv == 'all')) {
                    $rst = FALSE;
                    break;
                }
                if (($retFlg == 1) && ($targetConv == 'any')) {
                    $rst = TRUE;
                    break;
                }
            }
            if ($rst) {
                $retStr = $targetAction;
                break;
            }
        }
        return $retStr;
    }
/*
attemptLimitExceeded
*/
    private function checkCondition($tmpArray) {
        // IDが合ったら判定は学習目標で行う
        $isUnKnown = FALSE;
        if ($tmpArray[3] == 'satisfied') {
            $retFlg = FALSE;
            if (($tmpArray[0] == '') || ($this->getPrimaryObjectiveID() === $tmpArray[0])) {
                $tmpStatus = $this->getSuccessStatus(TRUE);
$this->trace('$tmpStatus=' . $tmpStatus);
                if ($tmpStatus == 'satisfied') {
                    $retFlg = TRUE;
                } else if ($tmpStatus == 'unknown') {
                    $isUnKnown = TRUE;
                }
            } else {
                $tgNum = $this->searchObjNum($tmpArray[0]);
                if ($tgNum != -1) {
                    $tmpStatus = $this->getLocalSuccessStatus($tgNum, TRUE);
$this->trace('LocalSuccessStatus');
$this->trace('$tmpStatus=' . $tmpStatus);
                    if ($tmpStatus == 'satisfied') {
                        $retFlg = TRUE;
                    } else if ($tmpStatus == 'unknown') {
                        $isUnKnown = TRUE;
                    }
                }
            }
        } else if ($tmpArray[3] == 'objectiveStatusKnown'){
            if (($tmpArray[0] == '') || ($this->getPrimaryObjectiveID() === $tmpArray[0])) {
                if ($this->getSuccessStatus(TRUE) == 'unknown') {
                    $retFlg = FALSE;
                } else {
                    $retFlg = TRUE;
                }
            } else {
                $tgNum = $this->searchObjNum($tmpArray[0]);
                if ($tgNum != -1) {
                    if ($this->getLocalSuccessStatus($tgNum,TRUE)=='unknown') {
                        $retFlg = FALSE;
                    } else {
                        $retFlg = TRUE;
                    }
                }
            }
        } else if ($tmpArray[3] == 'objectiveMeasureKnown') {
            if (($tmpArray[0] == '') || ($this->getPrimaryObjectiveID() === $tmpArray[0])) {
                $tmpScpre = $this->getScaledScore(TRUE);
                if ($tmpScpre === '') {
                    $retFlg = FALSE;
                } else {
                    $retFlg = TRUE;
                }
            } else {
                $tgNum = $this->searchObjNum($tmpArray[0]);
                if ($tgNum != -1) {
                    $tmpScpre = $this->getLocalScaledScore($tgNum, TRUE);
                    if ($tmpScpre === '') {
                        $retFlg = FALSE;
                    } else {
                        $retFlg = TRUE;
                    }
                }
            }
        } else if($tmpArray[3] == 'objectiveMeasureGreaterThan') {
            $retFlg = FALSE;
            if (($tmpArray[0] == '') || ($this->getPrimaryObjectiveID() === $tmpArray[0])) {
                $tmpScpre = $this->getScaledScore(TRUE);
                if ($tmpScpre !== '') {
                    if (floatval($tmpScpre) > floatval($tmpArray[1])) {   //$tmpArray[1];は必ず入っている。
                        $retFlg = TRUE;
                    }
                }
            } else {
                $tgNum = $this->searchObjNum($tmpArray[0]);
                if ($tgNum != -1) {
                    $tmpScpre = $this->getLocalScaledScore($tgNum, TRUE);
                    if ($tmpScpre !== '') {
                        if (floatval($tmpScpre) > floatval($tmpArray[1])) {   //$tmpArray[1];は必ず入っている。
                            $retFlg = TRUE;
                        }
                    }
                }
            }
        } else if ($tmpArray[3] == 'objectiveMeasureLessThan') {
            $retFlg = FALSE;
            if (($tmpArray[0] == '') || ($this->getPrimaryObjectiveID() === $tmpArray[0])) {
                $tmpScpre = $this->getScaledScore(TRUE);
                if ($tmpScpre !== '') {
                    if (floatval($tmpScpre) < floatval($tmpArray[1])) {   //$tmpArray[1];は必ず入っている。
                        $retFlg = TRUE;
                    }
                }
            } else {
                $tgNum = $this->searchObjNum($tmpArray[0]);
                if ($tgNum != -1) {
                    $tmpScpre = $this->getLocalScaledScore($tgNum, TRUE);
                    if ($tmpScpre !== '') {
                        if (floatval($tmpScpre) < floatval($tmpArray[1])) {   //$tmpArray[1];は必ず入っている。
                            $retFlg = true;
                        }
                    }
                }
            }
        } else if ($tmpArray[3] == 'completed') {
            $retFlg = FALSE;
            $tmpStatus = $this->getCompletionStatus();
            if ($this->getCompletionStatus() == 'completed') {
                $retFlg = TRUE;
            } else if ($tmpStatus == 'unknown') {
                $isUnKnown = TRUE;
            }
        } else if ($tmpArray[3] == 'activityProgressKnown') {
            if ($this->AttemptProgressStatus == TRUE) {
                $retFlg = TRUE;
            } else {
                $retFlg = FALSE;
            }
        } else if ($tmpArray[3] == 'attempted') {
            if ($this->AttemptCount != 0) {
                $retFlg = TRUE;
            } else {
                $retFlg = FALSE;
            }
        } else if($tmpArray[3] == 'attemptLimitExceeded') {
            if (intval($this->limitConditions['attemptLimit']) <= $this->AttemptCount) {
                $retFlg = TRUE;
            } else {
                $retFlg = FALSE;
            }
        } else if($tmpArray[3] == 'timeLimitExceeded') {
            if (intval($this->limitConditions['attemptAbsoluteDurationLimit']) < $this->durationSecond) {
                $retFlg = TRUE;
            } else {
                $retFlg = FALSE;
            }
        } else if($tmpArray[3] == 'outsideAvailableTimeRange') {
            if (intval($this->limitConditions['attemptAbsoluteDurationLimit']) < $this->durationSecond) {
                $retFlg = TRUE;
            } else {
                $retFlg = FALSE;
            }
        } else if($tmpArray[3] == 'always') {
            $retFlg = TRUE;
        } else {
            $retFlg = FALSE;
        }

        if ($tmpArray[2] == 'not') {
            $retFlg = !$retFlg;
        }
        if ($isUnKnown) {
            return 0;
        } else {
            if ($retFlg) {
                return 1;
            } else {
                return -1;
            }
        }
    }

    public function checkAttemptLimitExceeded() {
        $this->trace();
        $attemptLimit = $this->limitConditions['attemptLimit'];
        if ($attemptLimit === '') {
            return FALSE;
        }
        if (intval($attemptLimit) <= $this->AttemptCount) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function checkStatusForRollUp($counter, $condAry, $condC, $isCurrent0, $isCurrentA) {
        $this->trace();
        $isResult = FALSE;
        $isUnknown = FALSE;
        foreach ($condAry as $cond) {
            if ($cond['condition'] == 'satisfied') {
                $tmpStr = $this->getSuccessStatusForRR($isCurrent0);
                if ($tmpStr == 'satisfied') {
                    $isResult = TRUE;
                } else if ($tmpStr == 'not satisfied') {
                    $isResult = FALSE;
                } else if ($tmpStr == 'unknown') {
                    $isUnknown = TRUE;
                }
            } else if ($cond['condition'] == 'objectiveStatusKnown') {
                if ($counter > 0) {
                    $tmpStr = $this->getSuccessStatusForRR($isCurrent0);
                    if ($tmpStr == 'unknown') {
                        $isResult = FALSE;
                    } else {
                        $isResult = TRUE;
                    }
                } else {
                    $isUnknown = TRUE;
                }
            } else if ($cond['condition'] == 'objectiveMeasureKnown') {
                if ($counter > 0) {
                    $tmpStr = $this->getScaledScoreForRR($isCurrent0);
                    if ($tmpStr === '') {
                        $isResult = FALSE;
                    } else {
                        $isResult = TRUE;
                    }
                } else {
                    $isUnknown = TRUE;
                }
            } else if ($cond['condition'] == 'completed') {
                $tmpStr = $this->getCompletionStatusForRR($isCurrentA);
                if ($tmpStr == 'completed') {
                    $isResult = TRUE;
                } else if ($tmpStr == 'incomplete') {
                    $isResult = FALSE;
                } else if ($tmpStr == 'unknown') {
                    $isUnknown = TRUE;
                }
            } else if ($cond['condition'] == 'activityProgressKnown') {
                $tmpStr = $this->getCompletionStatusForRR($isCurrentA);
                if ($tmpStr == 'unknown') {
                    $isResult = FALSE;
                } else {
                    $isResult = TRUE;
                }
            } else if ($cond['condition'] == 'attempted') {
                if ($counter > 0) {
                    $isResult = TRUE;
                } else {
                    $isResult = FALSE;
                }
            } else if ($cond['condition'] == 'attemptLimitExceeded') {
            } else if ($cond['condition'] == 'timeLimitExceeded') { // なし
            } else if ($cond['condition'] == 'outsideAvailableTimeRange') {// なし
            }
            if ($cond['operator'] == 'not') {
                if (!$isUnknown) {
                    $isResult = !$isResult;
                }
            }
            if ($condC == 'any') {
                if ($isResult) {
$this->trace('  return 1;');
                    return 1;
                }
            } else {
                if (!$isResult) {
$this->trace('  return -1;');
                    return -1;
                }
            }
        }
        if ($condC == 'all') {
            if (!$isUnknown) {
                if ($isResult) {
$this->trace('  return 1;');
                    return 1;
                }
            }
        } else {
            if (!$isUnknown) {
                if (!$isResult) {
$this->trace('  return -1;');
                    return -1;
                }
            }
        }
$this->trace('  return 0;');
        return 0;
    }

    public function checkChildForRollUp($acounter, $issus, $str) {
        $this->trace();
        $isRetVal = FALSE;
        if ($str == 'satisfied' || $str == 'notSatisfied') {
            if ($this->getRollupRulesParam('rollupObjectiveSatisfied')) {
                $isCnd = ($str == 'satisfied') ? TRUE : FALSE;
                $RS = $this->getRollupConsiderations('requiredForSatisfied');
                $RF = $this->getRollupConsiderations('requiredForNotSatisfied');
                $isRetVal = TRUE;
                if (($isCnd && $RS == 'ifNotSuspended') || (!$isCnd && $RF == 'ifNotSuspended')) {
                    if (($acounter == 0) || ($acounter != 0 && $issus)) {
                        $isRetVal = FALSE;
                    }
                } else {
                    if (($isCnd && $RS == 'ifAttempted') || (!$isCnd && $RF == 'ifAttempted')) {
                        if ($acounter == 0) {
                            $isRetVal = FALSE;
                        }
                    } else {
                        if (($isCnd && $RS == 'ifNotSkipped') || (!$isCnd && $RF == 'ifNotSkipped')) {
                            if ($this->checkPreConditionSkip()) {
                                $isRetVal = FALSE;
                            }
                        }
                    }
                }
            }
        } else if ($str == 'completed' || $str == 'incomplete') {
            if ($this->getRollupRulesParam('rollupProgressCompletion')) {
                $isCnd = ($str == 'completed') ? TRUE : FALSE;
                $RC = $this->getRollupConsiderations('requiredForCompleted');
                $RI = $this->getRollupConsiderations('requiredForIncomplete');
                $isRetVal = TRUE;
                if (($isCnd && $RC == 'ifNotSuspended') || (!$isCnd && $RI == 'ifNotSuspended')) {
                    if (($acounter == 0) || ($acounter != 0 && $issus)) {
                        $isRetVal = FALSE;
                    }
                } else {
                    if (($isCnd && $RC == 'ifAttempted') || (!$isCnd && $RI == 'ifAttempted')) {
                        if ($acounter == 0) {
                            $isRetVal = FALSE;
                        }
                    } else {
                        if (($isCnd && $RC == 'ifNotSkipped') || (!$isCnd && $RI == 'ifNotSkipped')) {
                            if ($this->checkPreConditionSkip()) {
                                $isRetVal = FALSE;
                            }
                        }
                    }
                }
            }
        }
        return $isRetVal;
    }

    public function ignoreTrace() {
        $this->ignoreTrace = TRUE;
    }

    private function getTraceString($trace) {
        $result = '  ';
        
        if (isset($trace['file'])) {
            //$result .= $trace['file'] . ' ';
        }
        if (isset($trace['line'])) {
            //$result .= '(' . $trace['line'] . ') ';
        }
        if (isset($trace['object'])) {
            $result .= get_class($trace['object']);
            $result .= '<' . $this->ssID . '>';
        }
        if (isset($trace['type'])) {
            $result .= $trace['type'];
        }
        if (isset($trace['class'])) {
            //$result .= $trace['class'] . '::';
        }
        if (isset($trace['function'])) {
            $result .= $trace['function'];
        }
        if (isset($trace['args'])) {
            $result .= '(';
            $is_first = TRUE;
            foreach ($trace['args'] as $arg) {
                if (!$is_first) {
                    $result .= ', ';
                }
                
                if (is_object($arg)) {
                    $result .= '[object]';
                }
                else if (is_array($arg)) {
                    $result .= '[';
                    $is_first_inarray = TRUE;
                    foreach ($arg as $item_key => $item_value) {
                        if (!$is_first_inarray) {
                            $result .= ', ';
                        }
                        $result .= (is_string($item_key) ? "'$item_key' => " : '') . (is_string($item_value) ? "'$item_value'" : (is_array($item_value) ? $this->getArrayString($item_value) : var_export($item_value, TRUE)));
                        $is_first_inarray = FALSE;
                    }
                    $result .= ']';
                }
                else if (is_string($arg)) {
                    $result .= "'$arg'";
                }
                else if (is_bool($arg)) {
                    $result .= $arg ? 'true' : 'false';
                }
                else if (is_null($arg)) {
                    $result .= 'null';
                }
                else {
                    $result .= $arg;
                }
                
                $is_first = FALSE;
            }
            $result .= ')';
        }
        
        return $result;
    }

    private function getArrayString($arg) {
        $result .= '[';
        $is_first_inarray = TRUE;
        foreach ($arg as $item_key => $item_value) {
            if (!$is_first_inarray) {
                $result .= ', ';
            }
            $result .= (is_string($item_key) ? "'$item_key' => " : '') . (is_string($item_value) ? "'$item_value'" : (is_array($item_value) ? $this->getArrayString($item_value) : var_export($item_value, TRUE)));
            $is_first_inarray = FALSE;
        }
        $result .= ']';
        return $result;
    }

    private function trace($str = '', $output_backtrace = FALSE, $force_output = FALSE) {
        if ($force_output && !$this->ignoreTrace) {
            $dirs = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
            array_pop($dirs);
            $trace_file = implode('/', $dirs) . '/syslog/trace';
            $bt = debug_backtrace();
            if ($str === '' && isset($bt[1])) {
                $str = $this->getTraceString($bt[1]);
            }
            if ($fh = fopen($trace_file, 'a+')) {
                fwrite($fh, date('Y/m/d H:i:s') . " $str\n");
                fclose($fh);
            }
        }
    }

    private function log($str = '', $output_backtrace = FALSE, $force_output = FALSE) {
        if (FALSE && !$this->ignoreTrace) {
            $dirs = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
            array_pop($dirs);
            $trace_file = implode('/', $dirs) . '/syslog/trace';
            $bt = debug_backtrace();
            if ($str === '' && isset($bt[1])) {
                $str = $this->getTraceString($bt[1]);
            }
            if ($fh = fopen($trace_file, 'a+')) {
                //fwrite($fh, date('Y/m/d H:i:s') . " $str\n");
                fwrite($fh, "$str\n");
                fclose($fh);
            }
        }
    }
}
