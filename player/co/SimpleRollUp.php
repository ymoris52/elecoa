<?php

    class SimpleRollUp {

        protected $isInSet;
        protected $writeTargetObjectiveIdArray;
        protected $rollUpOriginActivityIdArray;
        protected $rollUpOriginObjectiveIdArray;

        function __construct() {
            $this->writeTargetObjectiveIdArray = array();
            $this->rollUpOriginActivityIdArray = array();
            $this->rollUpOriginObjectiveIdArray = array();
            $this->isInSet = FALSE;
        }

        public function isInSet() {
            return $this->isInSet;
        }

        public function putInSet() {
            $this->isInSet = TRUE;
        }

        public function getOutOfSet() {
            $this->isInSet = FALSE;
        }

        public function exePreRollUp(ActivityBase $activity) {
            $platform = Platform::getInstance();
            foreach ($this->writeTargetObjectiveIdArray as $objectiveId) {
                $objective = $platform->searchObjective($objectiveId);
                if (!is_null($objective)) {
                    $objective->callFromActivity($activity->getID(), 'PREROLLUP', NULL);
                }
            }
        }

        public function isRollUpReady(ActivityBase $thisActivity) {
            $platform = Platform::getInstance();
            foreach ($this->rollUpOriginActivityIdArray as $activityId) {
                $activity = $platform->searchAct($activityId);
                $result = $activity->callFromParent('INROLLUPSET', NULL);
                if (isset($result['Value']) && $result['Value']) {
                    return FALSE;
                }
            }
            foreach ($this->rollUpOriginObjectiveIdArray as $objectiveId) {
                $objective = $platform->searchObjective($objectiveId);
                $result = $objective->callFromActivity($thisActivity->getID(), 'INROLLUPSET', NULL);
                if (isset($result['Value']) && $result['Value']) {
                    return FALSE;
                }
            }
            return TRUE;
        }

        public function exeRollUp(ActivityBase $activity) {
            $platform = Platform::getInstance();
            foreach ($this->writeTargetObjectiveIdArray as $objectiveId) {
                $objective = $platform->searchObjective($objectiveId);
                if (!is_null($objective)) {
                    $objective->callFromActivity($activity->getID(), 'ROLLUP', NULL);
                }
            }
        }

        public function addWriteTargetObjectiveIdArray($objectiveIdArray) {
            $orgArray = $this->writeTargetObjectiveIdArray;
            $this->writeTargetObjectiveIdArray = array_unique(array_merge($orgArray, $objectiveIdArray));
        }

        public function addRollUpOriginActivityIdArray($activityIdArray) {
            $orgArray = $this->rollUpOriginActivityIdArray;
            $this->rollUpOriginActivityIdArray = array_unique(array_merge($orgArray, $activityIdArray));
        }

        public function addRollUpOriginObjectiveIdArray($objectiveIdArray) {
            $orgArray = $this->rollUpOriginObjectiveIdArray;
            $this->rollUpOriginObjectiveIdArray = array_unique(array_merge($orgArray, $objectiveIdArray));
        }
    }
