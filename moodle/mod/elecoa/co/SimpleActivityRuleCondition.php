<?php
class SimpleActivityRuleCondition {

    protected $rule;
    protected $targetValues; // (('owner' => True/False, 'userId' => 'xxxx', 'activityId' => 'xxxx', 'childCount' => 'xxxx'), (...), ...)

    function __construct($ruleConditionNode) {
        $this->rule = array('condition' => $ruleConditionNode->getAttribute('condition'),
                            'target' => $ruleConditionNode->getAttribute('target'),
                            'condition' => $ruleConditionNode->getAttribute('condition'),
                            'threshold' => $ruleConditionNode->getAttribute('threshold'));
    }

    public function getTarget() {
        return $this->rule['target'];
    }

    public function setAppendedChildActivityCounts($values) {
        $this->targetValues = $values;
    }

    // ルールを満たしているかどうか
    public function isConditionMet() {
        if ($this->rule['condition'] === 'appendedChildCountGreaterThan') {
            if (isset($this->targetValues)) {
                $total = 0;
                foreach ($this->targetValues as $item) {
                    $total += $item['childCount'];
                }
                $threshold = intval($this->rule['threshold']);
//echo "total=$total\n";
//echo "threshold=$threshold\n";
                return ($total > $threshold);
            }
        }
        return FALSE;
    }
}
