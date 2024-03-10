<?php

    class SimpleObjectiveRollUp {

        protected $isInSet;
        protected $readOriginActivityIdArray;
        protected $rollUpOriginActivityIdArray;

        function __construct() {
            $this->readOriginActivityIdArray = array();
            $this->rollUpOriginActivityIdArray = array();
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

        public function exePreRollUp(ObjectiveBase $objective) {
            $platform = Platform::getInstance();
            foreach ($this->readOriginActivityIdArray as $activityId) {
                $activity = $platform->searchAct($activityId);
                if (!is_null($activity)) {
                    $activity->callFromObjective($objective->getID(), 'PREROLLUP', NULL);
                }
            }
        }

        public function isRollUpReady(ObjectiveBase $objective) {
            $platform = Platform::getInstance();
            foreach ($this->rollUpOriginActivityIdArray as $activityId) {
                $activity = $platform->searchAct($activityId);
                $result = $activity->callFromObjective($objective->getID(), 'INROLLUPSET', NULL);
                if (isset($result['Value']) && $result['Value']) {
                    return FALSE;
                }
            }
            return TRUE;
        }

        public function exeRollUp(ObjectiveBase $objective) {
            $platform = Platform::getInstance();
            foreach ($this->readOriginActivityIdArray as $activityId) {
                $activity = $platform->searchAct($activityId);
                if (!is_null($activity)) {
                    $activity->callFromObjective($objective->getID(), 'ROLLUP', NULL);
                }
            }
        }

        public function addReadOriginActivityIdArray($activityIdArray) {
            $orgArray = $this->readOriginActivityIdArray;
            $this->readOriginActivityIdArray = array_unique(array_merge($orgArray, $activityIdArray));
        }

        public function addRollUpOriginActivityIdArray($activityIdArray) {
            $orgArray = $this->rollUpOriginActivityIdArray;
            $this->rollUpOriginActivityIdArray = array_unique(array_merge($orgArray, $activityIdArray));
        }
    }
