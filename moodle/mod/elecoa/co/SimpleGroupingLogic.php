<?php
class SimpleGroupingLogic {

    protected $pattern;
    protected $referencedObjectiveId;
    protected $numberOfGroup;
    protected $numberOfGroupMember;
    protected $uid;
    protected $cid;

    function __construct($groupingLogicNode, $context) {
        $this->referencedObjectiveId = $groupingLogicNode->getAttribute('referencedObjective');
        $logicRuleNode = selectSingleNode($groupingLogicNode, 'logicRule');
        $this->pattern = $logicRuleNode->getAttribute('pattern');
        $numberOfGroupValue = $logicRuleNode->getAttribute('numberOfGroup');
        $this->numberOfGroup = is_numeric($numberOfGroupValue) ? $numberOfGroupValue + 0 : 0;
        $numberOfGroupMemberValue = $logicRuleNode->getAttribute('numberOfGroupMember');
        $this->numberOfGroupMember = is_numeric($numberOfGroupMemberValue) ? $numberOfGroupMemberValue + 0 : 0;
        $this->uid = $context->getUid();
        $this->cid = $context->getCid();
    }

    private function performGrouping_homogeneous($targetValues) {
        $objectiveValues = $targetValues;
        usort($objectiveValues, function($a1, $a2) {
            if ($a1['objectiveValue'] === $a2['objectiveValue']) {
                return strcmp($a1['userId'], $a2['userId']);
            } else {
                return strcmp($a1['objectiveValue'], $a2['objectiveValue']);
            }
        });
        $userCount = count($objectiveValues);
        //グループ数固定
        $numberOfGroup = $this->numberOfGroup;
        $max = ceil($userCount / $numberOfGroup);
        $idx = 1;
        for ($i = 1; $i <= $userCount; $i++) {
            if ($objectiveValues[$i - 1]['owner']) {
                $idx = ceil($i / $max);
                break;
            }
        }
        return 'Group' . $idx;
    }

    //指定したグルーピングでの現在のグループ数
    private function getCurrentNumberOfGroup($groupingName) {
        $platform = Platform::getInstance();
        $gg = $platform->getParticipantObjectives($this->cid, $this->uid, $groupingName);
        $filtered = array_filter($gg, function($a) {
            return !is_null($a['objectiveValue']);
        });
        $ov = array();
        foreach ($gg as $a) {
            $ov[] = $a['objectiveValue'];
        }

        return count(array_unique($ov));
    }

    //指定したグルーピングにおける、指定したインデックスのグループの現在のメンバー数
    private function getMemberCount($groupingName, $index) {
        $platform = Platform::getInstance();
        $gg = $platform->getParticipantObjectives($this->cid, $this->uid, $groupingName);
        $filtered = array_filter($gg, function($a) use ($index) {
            return ($a['objectiveValue'] === json_encode('Group' . $index));
        });

        return count($filtered);
    }

    //グループ数固定のランダム振り分け
    private function performGrouping_random($targetValues) {
        $numberOfGroup = $this->numberOfGroup;
        return 'Group' . rand(1, $numberOfGroup);
    }

    //グループ定員固定の順次振り分け
    private function performGrouping_fill($targetValues) {
        $groupingName = $this->referencedObjectiveId;
        $max = $this->numberOfGroupMember;
        $currentNumberOfGroup = $this->getCurrentNumberOfGroup($groupingName);
        for ($i = 1; $i <= $currentNumberOfGroup; $i++) {
            $memberCount = $this->getMemberCount($groupingName, $i);
            if ($memberCount < $max) {
                return 'Group' . $i;
            }
        }
        $newIndex = $currentNumberOfGroup;
        return 'Group' . ($newIndex + 1);
    }

    // グループ分け実施
    public function performGrouping($targetValues) {
        if ($this->pattern === 'homogeneous') {
            return $this->performGrouping_homogeneous($targetValues);
        }
        if ($this->pattern === 'random') {
            return $this->performGrouping_random($targetValues);
        }
        if ($this->pattern === 'fill') {
            return $this->performGrouping_fill($targetValues);
        }
        return 'Group1';
    }
}
