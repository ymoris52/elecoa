<?php
require_once dirname(__FILE__) . "/LDSimpleBlock.php";

class LDGate extends LDSimpleBlock {

    protected $grouping;
    protected $sync;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $groupingNode = selectSingleNode($this->dataNode, 'grouping');
        $syncNode = selectSingleNode($this->dataNode, 'sync');
        if (!is_null($groupingNode)) {
            $this->grouping = new SimpleGroupingComponent($groupingNode, $ctx);
        } else {
            $this->grouping = NULL;
        }
        $this->sync = new SimpleSyncComponent($syncNode);
    }

    protected function exeRollUpMain() {
        $this->co_trace();
        $ctx = $this->getContext();
        if (!is_null($this->grouping)) {
            $this->grouping->loadUsersObjectives($ctx);
        }
        $this->sync->makeComponentReady($ctx);
        $enableGrouping = (!is_null($this->grouping)) ? $this->grouping->isGroupingConditionMet() : FALSE;
        if ($enableGrouping) {
            $this->grouping->performGrouping();
        }
    }

    protected function exeExitCond($id, $val) {
        $this->co_trace();
        $enableGrouping = (!is_null($this->grouping)) ? $this->grouping->isGroupingConditionMet() : $this->sync->isForwardingConditionMet();
        $enableForwarding = $this->sync->isForwardingConditionMet();

        $value = $val;
        if ($enableGrouping) {
            if ($enableForwarding) {
                $value = array('command' => 'CONTINUE', 'value' => NULL, 'activityId' => $this->getID());
            }
        } else {
            if (isset($val['command']) and ($val['command'] === 'CHOICE')) {
                if ($this->checkSelectedActivityDirection($val['value']) !== '') {
                    $value = array('command' => 'RETRY', 'value' => NULL, 'activityId' => $this->getID());
                }
            }
        }
        return array('Result' => TRUE, 'Value' => $value, 'Continue' => TRUE);
    }

    protected function checkPreCondition($cmd, $id, $val, $isDescending) {
        $this->co_trace();
        $ctx = $this->getContext();
        $this->sync->makeComponentReady($ctx);
        $enableForwarding = $this->sync->isForwardingConditionMet();
        if ($enableForwarding) {
	        if (!is_null($this->grouping)) {
	            $this->grouping->loadUsersObjectives($ctx);
	        }
	        $enableGrouping = (!is_null($this->grouping)) ? $this->grouping->isGroupingConditionMet() : FALSE;
	        if ($enableGrouping) {
	            $this->grouping->performGrouping();
	        }
            if ($cmd === 'CHOICE' or $cmd === 'CONTINUE') {
                if ($this->checkSelectedActivityDirection($val) !== 'Forward') {
                    return $this->makeCheckResult('skip', 'skip');
                }
            }
        }

        if (is_null($this->grouping)) {
            if (!$enableForwarding) {
                if ($cmd === 'CHOICE') {
                    if ($this->getID() !== $val) {
                        if ($this->checkSelectedActivityDirection($val) === 'Forward') {
                            return $this->makeCheckResult('error', 'wait');
                        }
                    }
                }
            }
        }
        return $this->makeCheckResult('', '');
    }

//    private function hasChild($id) {
//        $len = count($this->children);
//        for ($i = 0; $i < $len; $i++) {
//            if ($this->getChild($i)->getID() === $id) {
//                return TRUE;
//            }
//        }
//        return FALSE;
//    }

    private function checkSelectedActivityDirection($id_selected) {
        $getAncestorChildPosition = function ($parent, $id) use (&$getAncestorChildPosition) {
            $idx = $parent->getChildPosition($id);
            if ($idx > -1) {
                return $idx;
            } else {
                $platform = Platform::getInstance();
                $activity = $platform->searchAct($id);
                if ($activity === NULL) {
                    return -1;
                }
                $p = $activity->getParent();
                if ($p === NULL) {
                    return -1;
                }
                return $getAncestorChildPosition($parent, $p->getID());
            }
        };

        $idx_selected = $getAncestorChildPosition($this->getParent(), $id_selected);
        $idx_this  = $this->getParent()->getChildPosition($this->getID());
        if ($idx_selected > -1 and $idx_this > -1) {
            if ($idx_selected > $idx_this) {
                return 'Forward';
            }
            if ($idx_selected < $idx_this) {
                return 'Backward';
            }
            if ($idx_selected == $idx_this) {
                return '';
            }
        } else {
            return 'Unknown';
        }
    }
}
