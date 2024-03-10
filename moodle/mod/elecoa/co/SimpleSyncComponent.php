<?php
class SimpleSyncComponent {

    protected $activityRuleConditions = array();
    protected $objectiveRuleConditions = array();

    function __construct($syncNode) {
        $ruleConditionsNode = selectSingleNode($syncNode, 'ruleConditions');
        $ruleConditionNodes = selectNodes($ruleConditionsNode, 'ruleCondition');
        if ($ruleConditionNodes != null) {
            $len = count($ruleConditionNodes);
            for ($i=0; $i < $len; $i++) {
                if ($ruleConditionNodes[$i]->getAttribute('type') === 'activity') {
                    $this->activityRuleConditions[] = new SimpleActivityRuleCondition($ruleConditionNodes[$i]);
                } else {
                    $this->objectiveRuleConditions[] = new SimpleObjectiveRuleCondition($ruleConditionNodes[$i]);
                }
            }
        }
    }

    public function makeComponentReady($ctx) {
        $platform = Platform::getInstance();
        foreach ($this->activityRuleConditions as $ruleCondition) {
            $activityId = $ruleCondition->getTarget();
            $targetValues = $platform->getAppendedChildActivityCounts($ctx->getCid(), $ctx->getUid(), $activityId);
            $ruleCondition->setAppendedChildActivityCounts($targetValues);
        }
        foreach ($this->objectiveRuleConditions as $ruleCondition) {
            $referencedObjectiveId = $ruleCondition->getReferencedObjectiveId();
            $targetValues = $platform->getParticipantObjectives($ctx->getCid(), $ctx->getUid(), $referencedObjectiveId);
            $ruleCondition->setParticipantObjectives($targetValues);
        }
    }

    // 移動可否
    public function isForwardingConditionMet() {
        if (count($this->activityRuleConditions) > 0) {
            return $this->activityRuleConditions[0]->isConditionMet();
        }
        if (count($this->objectiveRuleConditions) > 0) {
            return $this->objectiveRuleConditions[0]->isConditionMet();
        }
        return FALSE;
    }
}
