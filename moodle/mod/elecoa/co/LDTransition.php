<?php
require_once dirname(__FILE__) . "/LDSimpleBlock.php";

class LDTransition extends LDSimpleBlock {
    protected $statusObjectiveRef;
    protected $initValue;
    protected $eventList;
    protected $switchDefault;
    protected $caseList;
    protected $showAsCurrentChild;

    // <statusObjectiveRef value="" />
    // <transition init="">
    //   <status name="" activity="">
    //     <event name="" newstatus="" />
    //     <event name="" newstatus="" />
    //      ：
    //   </status>
    //   <status name="" activity="">
    //     <event name="" newstatus="" />
    //     <event name="" newstatus="" />
    //      ：
    //   </status>
    //    ：
    // </transition>
    function addData($data) {
        parent::addData($data);
        $statusObjectiveRef = selectSingleNode($data, 'statusObjectiveRef');
        $showAsCurrentChild = selectSingleNode($data, 'showAsCurrentChild');
        if (!is_null($showAsCurrentChild)) {
            $this->showAsCurrentChild = TRUE;
        } else {
            $this->showAsCurrentChild = FALSE;
        }
        if (!is_null($statusObjectiveRef)) {
            $this->statusObjectiveRef = $statusObjectiveRef->getAttribute('value');
        }
        $transition = selectSingleNode($data, 'transition');
        if (!is_null($transition)) {
            $this->initValue = $transition->getAttribute('init');
            foreach (selectNodes($transition, 'status') as $status) {
                $status_name = $status->getAttribute('name');
                $activity = $status->getAttribute('activity');
                if ($this->initValue === $status_name) {
                    $this->switchDefault = $activity;
                }
                $this->caseList[] = array('status' => $status_name, 'activity' => $activity);
                foreach (selectNodes($status, 'event') as $event) {
                    $event_name = $event->getAttribute('name');
                    $newstatus = $event->getAttribute('newstatus');
                    $this->eventList[] = array('name' => $event_name, 'status' => $status_name, 'newvalue' => $newstatus);
                }
            }
        }
    }

    function addCommands() {
        parent::addCommands();
        $this->cmdTableFromSelf['EVENT'] = array('Func' => 'exeEvent', 'Type' => 'cmd', 'View' => FALSE);
        $this->cmdTableFromChild['EVENT'] = array('Func' => 'exeEventC', 'Type' => 'seq', 'View' => FALSE);
    }

    function exeInit($id, $val) {
        $this->co_trace();
        $result = parent::exeInit($id, $val);
        $platform = Platform::getInstance();
        $objective = $platform->searchObjective($this->statusObjectiveRef);
        if (is_null($objective->getValue())) {
            $objective->setValue($this->initValue);
        }
        return $result;
    }

    function exeEvent($val, $rtm) {
        $this->co_trace();
        $eventName = $val;
        if ($eventName == 'APPENDED') {
            // 状態の初期化
            $platform = Platform::getInstance();
            $objective = $platform->searchObjective($this->statusObjectiveRef);
            //$objective->setValue($this->initValue);
        }
        return array('Result' => TRUE, 'Continue' => TRUE, 'NextID' => '');
    }

    function exeIndexP($val) {
        $this->co_trace();
        $result = parent::exeIndexP($val);
        if (!$result['Result']) {
            return array('Result' => FALSE);
        }
        $resultArray = array(
            'type'      => $this->getType(),
            'title'     => $this->getTitle(),
            'id'        => $this->getID(),
            'is_active' => $this->isActive,
            'children'  => array()
        );
        $resultValue = null;
        $platform = Platform::getInstance();
        $objective = $platform->searchObjective($this->statusObjectiveRef);
        $currentStatus = $objective->getValue();
        $currentActivity = $this->switchDefault;
        foreach ($this->caseList as $case) {
            if ($case['status'] === $currentStatus) {
                $currentActivity = $case['activity'];
                break;
            }
        }
        $len = count($this->children);
        for ($i = 0; $i < $len; $i++) {
            $activity = $this->getChild($i);
            if ($activity->getID() === $currentActivity) {
                $tmpArray = $activity->callFromParent('INDEX', $val);
                if (!$tmpArray['Result']) {
                    continue;
                } else {
                    $resultValue = $tmpArray['Value'];
                }
                if (!is_null($resultValue)) {
                    if (!$this->showAsCurrentChild) {
                        $resultArray['children'][] = $resultValue;
                    }
                }
            } else {
                if (!$this->showAsCurrentChild) {
                    $resultArray['children'][] = array('type'      => $activity->getType(),
                                                       'title'     => $activity->getTitle(),
                                                       'id'        => $activity->getID(),
                                                       'is_active' => $activity->isActive,
                                                       'children'  => array());
                }
            }
        }
        if (is_null($resultValue)) {
            $retArray = array('Result' => FALSE);
        } else {
            $retArray = array('Result' => TRUE, 'Continue' => TRUE);
            if ($this->showAsCurrentChild) {
                $retArray['Value'] = $resultValue;
            } else {
                $retArray['Value'] = $resultArray;
            }
        }
        return $retArray;
    }

    function exeRetry($id, $val) {
        $this->co_trace();
        return $this->exeChoice($id, $val);
    }

    function exeContinueP($val) {
        $this->co_trace();
        $platform = Platform::getInstance();
        $objective = $platform->searchObjective($this->statusObjectiveRef);
        $currentStatus = $objective->getValue();
        $next = $this->switchDefault;
        foreach ($this->caseList as $case) {
            if ($case['status'] === $currentStatus) {
                $next = $case['activity'];
                break;
            }
        }
        $result = $this->choiceActivity($next);
        if (isset($result['NextID'])) {
            return $result;
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'CONTINUE');
        }
    }

    function exeContinue($id, $val) {
        $this->co_trace();
        return $this->exeChoice($id, $val);
    }

    function exePreviousP($val) {
        $this->co_trace();
        $platform = Platform::getInstance();
        $objective = $platform->searchObjective($this->statusObjectiveRef);
        $currentStatus = $objective->getValue();
        $next = $this->switchDefault;
        foreach ($this->caseList as $case) {
            if ($case['status'] === $currentStatus) {
                $next = $case['activity'];
                break;
            }
        }
        $result = $this->choiceActivity($next);
        if (isset($result['NextID'])) {
            return $result;
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'PREVIOUS');
        }
    }
    
    function exePrevious($id, $val) {
        $this->co_trace();
        return $this->exeChoice($id, $val);
    }

    private function isDescendant($id) {
        $findId = function ($indexValue, $id) use (&$findId) {
            if ($indexValue['id'] === $id) {
                return true;
            }
            if (isset($indexValue['children'])) {
                foreach ($indexValue['children'] as $childIndexValue) {
                    if ($findId($childIndexValue, $id)) {
                        return true;
                    }
                }
            }
            return false;
        };
        $len = count($this->children);
        for ($i = 0; $i < $len; $i++) {
            $activity = $this->getChild($i);
            if ($activity->getID() === $id) {
                return true;
            }
            $index = $activity->callFromParent('INDEX', NULL);
            if (!$index['Result']) {
                continue;
            } else {
                if ($findId($index['Value'], $id)){
                    return true;
                }
            }
        }
        return false;
    }

    function exeChoiceP($val) {
        $this->co_trace();
        if ($this->getID() === $val || $this->isDescendant($val)) {
            $platform = Platform::getInstance();
            $objective = $platform->searchObjective($this->statusObjectiveRef);
            $currentStatus = $objective->getValue();
            $next = $this->switchDefault;
            foreach ($this->caseList as $case) {
                if ($case['status'] === $currentStatus) {
                    $next = $case['activity'];
                    break;
                }
            }
            if ($this->getID() === $val) {
                return $this->callFromParent('CHOICE', $next);
            } else {
                $len = count($this->children);
                for ($i = 0; $i < $len; $i++) {
                    $activity = $this->getChild($i);
                    if ($activity->getID() === $next) {
                        $result = $activity->callFromParent('CHOICE', $val);
                        if (!isset($result['NextID'])) {
                            return $activity->callFromParent('CHOICE', $next);
                        } else {
                            return $result;
                        }
                    }
                }
            }
            return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'CHOICE');
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'CHOICE');
        }
    }

    function exeChoice($id, $val) {
        $this->co_trace();
        if ($this->getID() === $val || $this->isDescendant($val)) {
            $platform = Platform::getInstance();
            $objective = $platform->searchObjective($this->statusObjectiveRef);
            $currentStatus = $objective->getValue();
            $next = $this->switchDefault;
            foreach ($this->caseList as $case) {
                if ($case['status'] === $currentStatus) {
                    $next = $case['activity'];
                    break;
                }
            }
            if ($this->getID() === $val) {
                return $this->callFromParent('CHOICE', $next);
            } else {
                $len = count($this->children);
                for ($i = 0; $i < $len; $i++) {
                    $activity = $this->getChild($i);
                    if ($activity->getID() === $next) {
                        $result = $activity->callFromParent('CHOICE', $val);
                        if (!isset($result['NextID'])) {
                            return $activity->callFromParent('CHOICE', $next);
                        } else {
                            return $result;
                        }
                    }
                }
            }
            return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'CHOICE');
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'CHOICE');
        }
    }

    function exeEventC($id, $val) {
        $this->co_trace();
//        $eventName = $val;
//        $platform = Platform::getInstance();
//        $objective = $platform->searchObjective($this->statusObjectiveRef);
//        $currentStatus = $objective->getValue();
//        foreach ($this->eventList as $event) {
//            if ($event['name'] === $eventName and $event['status'] === $currentStatus) {
//                $newvalue = $event['newvalue'];
//                $objective->setValue($newvalue);
//                $currentStatus = $newvalue;
//                break;
//            }
//        }
//        $next = $this->switchDefault;
//        foreach ($this->caseList as $case) {
//            if ($case['status'] === $currentStatus) {
//                $next = $case['activity'];
//                break;
//            }
//        }
        return $this->exeChoice($id, $this->getID());
    }

    function exeExitCond($id, $val) {
        $this->co_trace();
        $platform = Platform::getInstance();
        $objective = $platform->searchObjective($this->statusObjectiveRef);
        $currentStatus = $objective->getValue();
        if (isset($val['command']) and isset($val['value'])) {
            if ($val['command'] === 'EVENT') {
                $eventName = $val['value'];
                foreach ($this->eventList as $event) {
                    if ($event['name'] === $eventName and $event['status'] === $currentStatus) {
                        $newvalue = $event['newvalue'];
                        $objective->setValue($newvalue);
                        $currentStatus = $newvalue;
                        break;
                    }
                }
            }
        }
        $currentActivityId = $this->switchDefault;
        foreach ($this->caseList as $case) {
            if ($case['status'] === $currentStatus) {
                $currentActivityId = $case['activity'];
                break;
            }
        }
        $isdescendant = false;
        if ($currentActivityId === $id) {
            $isdescendant = true;
        }

        if ($isdescendant) {
            return array('Result' => TRUE, 'Value' => $val, 'Continue' => TRUE);
        } else {
            $value = array('command' => 'RETRY', 'value' => $currentActivityId, 'activityId' => $this->getID());
            return array('Result' => TRUE, 'Value' => $value, 'Continue' => TRUE);
        }
    }

    protected function choiceActivity($activityId) {
        $len = count($this->children);
        for ($i = 0; $i < $len; $i++) {
            $activity = $this->getChild($i);
            if ($activity->getID() === $activityId) {
                if ($activity->getType() === 'BLOCK') {
                    return $activity->callFromParent('CONTINUE', '');
                } else {
                    return $activity->callFromParent('CHOICE', $activityId);
                }
            }
        }
        return array('Result' => TRUE, 'Continue' => TRUE, 'Command' => 'CHOICE');
    }
}
