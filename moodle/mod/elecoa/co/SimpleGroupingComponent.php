<?php
class SimpleGroupingComponent {

    protected $ruleCondition;
    protected $targetValues; // (('owner' => True/False, 'userId' => 'xxxx', 'objectiveId' => 'xxxx', 'objectiveValue' => 'xxxx'), (...), ...)
    protected $groupingLogic;
    protected $referencedObjectiveForGrouping;

    function __construct($groupingNode, $ctx) {
        $ruleConditionsNode = selectSingleNode($groupingNode, 'ruleConditions');
        $ruleConditionNode = selectSingleNode($ruleConditionsNode, 'ruleCondition');
        $this->ruleCondition = new SimpleObjectiveRuleCondition($ruleConditionNode);
        $groupingLogicNode = selectSingleNode($groupingNode, 'groupingLogic');
        $groupingLogic = $groupingLogicNode->getAttribute('type');
        $this->groupingLogic = new $groupingLogic($groupingLogicNode, $ctx);
        $this->referencedObjectiveForGrouping = $groupingLogicNode->getAttribute('referencedObjective');
    }

    // 判断に使用するためのユーザー学習目標を取得する
    public function loadUsersObjectives($ctx) {
        $platform = Platform::getInstance();
        $referencedObjectiveId = $this->ruleCondition->getReferencedObjectiveId();
        $this->targetValues = $platform->getParticipantObjectives($ctx->getCid(), $ctx->getUid(), $referencedObjectiveId);
        $this->ruleCondition->setParticipantObjectives($this->targetValues);
    }

    // グループ分け実施可否
    public function isGroupingConditionMet() {
        //$objective = $platform->searchObjective('gObj-value1');
        //$enableGrouping = (floatval($objective->getValue()['ScaledScore']) > 0.8);
        return $this->ruleCondition->isConditionMet();
    }

    // グループ分け実施
    public function performGrouping() {
        $platform = Platform::getInstance();
        $objective = $platform->searchObjective($this->referencedObjectiveForGrouping);
        $value = $objective->getValue();
        if (empty($value)) {
            $groupName = $this->groupingLogic->performGrouping($this->targetValues);
            $objective->setValue($groupName);
        }
    }
}
