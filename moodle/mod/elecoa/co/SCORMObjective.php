<?php
    require_once dirname(__FILE__) . '/SimpleObjective.php';

    class SCORMObjective extends SimpleObjective {
        private $ObjectiveProgressStatus;
        private $ObjectiveSatisfiedStatus;
        private $ObjectiveMeasureStatus;
        private $ObjectiveNormalizedMeasure;
        private $sgo;

        function __construct(&$ctx, $id, $node, $res, $sgo) {
            parent::__construct($ctx, $id, $node, $res, $sgo);
            $this->ObjectiveProgressStatus = FALSE;
            $this->ObjectiveSatisfiedStatus = FALSE;
            $this->ObjectiveMeasureStatus = FALSE;
            $this->ObjectiveNormalizedMeasure = 0;
            $this->sgo = $sgo;

            $key_value_pairs = readLog($this->getContext(), $id, NULL, $this->getType(), array('ObjectiveProgress', 'ObjectiveSatisfied', 'ObjectiveMeasure', 'NormalizedMeasure'), $sgo);
            $this->ObjectiveProgressStatus = isset($key_value_pairs['ObjectiveProgress']) ? ($key_value_pairs['ObjectiveProgress'] == 'TRUE' ? TRUE : FALSE) : FALSE;
            $this->ObjectiveSatisfiedStatus = isset($key_value_pairs['ObjectiveSatisfied']) ? ($key_value_pairs['ObjectiveSatisfied'] == 'TRUE' ? TRUE : FALSE) : FALSE;
            $this->ObjectiveMeasureStatus = isset($key_value_pairs['ObjectiveMeasure']) ? ($key_value_pairs['ObjectiveMeasure'] == 'TRUE' ? TRUE : FALSE) : FALSE;
            $this->ObjectiveNormalizedMeasure = floatval(isset($key_value_pairs['NormalizedMeasure']) ? $key_value_pairs['NormalizedMeasure'] : '0.0');
        }

        public function terminate() {
            $this->co_trace();
            $data_array = array('ObjectiveProgress' => $this->ObjectiveProgressStatus ? 'TRUE' : 'FALSE',
                                'ObjectiveSatisfied' => $this->ObjectiveSatisfiedStatus ? 'TRUE' : 'FALSE',
                                'ObjectiveMeasure' => $this->ObjectiveMeasureStatus ? 'TRUE' : 'FALSE',
                                'NormalizedMeasure' => $this->ObjectiveNormalizedMeasure);
            return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array, $this->sgo);
        }

        public function getValue() {
            return array('SuccessStatus' => $this->getSuccessStatus(), 'ScaledScore' => $this->getScaledScore());
        }

        private function getSuccessStatus() {
            return $this->ObjectiveProgressStatus ? ($this->ObjectiveSatisfiedStatus ? 'satisfied' : 'not satisfied') : 'unknown';
        }

        private function getScaledScore() {
            return $this->ObjectiveMeasureStatus ? $this->ObjectiveNormalizedMeasure : '';
        }

        public function setValue($keyvalue) {
            if (array_key_exists('SuccessStatus', $keyvalue)) {
                $this->setSuccessStatus($keyvalue['SuccessStatus']);
            }
            if (array_key_exists('ScaledScore', $keyvalue)) {
                $this->setScaledScore($keyvalue['ScaledScore']);
            }
        }

        private function setSuccessStatus($str) {
            if ($str === 'not satisfied') {
                $this->ObjectiveProgressStatus = TRUE;
                $this->ObjectiveSatisfiedStatus = FALSE;
            } else if ($str === 'satisfied') {
                $this->ObjectiveProgressStatus = TRUE;
                $this->ObjectiveSatisfiedStatus = TRUE;
            }
        }

        private function setScaledScore($num) {
            $this->ObjectiveMeasureStatus = TRUE;
            $this->ObjectiveNormalizedMeasure = $num;
        }
    }
