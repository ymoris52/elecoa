<?php
class SimpleObjectiveRuleCondition {

    protected $rule;
    protected $targetValues; // (('owner' => True/False, 'userId' => 'xxxx', 'objectiveId' => 'xxxx', 'objectiveValue' => 'xxxx'), (...), ...)

    function __construct($ruleConditionNode) {
        $this->rule = array('condition' => $ruleConditionNode->getAttribute('condition'),
                            'target' => $ruleConditionNode->getAttribute('target'),
                            'referencedObjective' => $ruleConditionNode->getAttribute('referencedObjective'));
    }

    public function getReferencedObjectiveId() {
        return $this->rule['referencedObjective'];
    }

    public function setParticipantObjectives($values) {
        $this->targetValues = $values;
    }

    // ルールを満たしているかどうか
    public function isConditionMet() {
        $platform = Platform::getInstance();
        if ($this->rule['condition'] === 'filled') {
            if (isset($this->targetValues)) {
                foreach ($this->targetValues as $item) {
                    if (is_null($item['objectiveValue'])) {
                        return FALSE;
                    }
                }
                return TRUE;
            }
        }
        return FALSE;
    }
}
