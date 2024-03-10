<?php
require_once dirname(__FILE__) . '/SimpleLeaf.php';

class SCORMLeaf extends SimpleLeaf {

    protected $seqParam;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
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
            $retVal = floatval($retVal) * $weight;
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
