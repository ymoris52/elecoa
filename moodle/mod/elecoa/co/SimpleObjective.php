<?php
    require_once dirname(__FILE__) . '/ObjectiveBase.php';
    require_once dirname(__FILE__) . '/SimpleObjectiveRollUp.php';

    class SimpleObjective extends ObjectiveBase {

        protected $rollup;
        private $sgo;
        private $value;

        function __construct(&$ctx, $id, $node, $res, $sgo) {
            parent::__construct($ctx, $id, $node, $res, $sgo);
            $this->cmdTable = array(
                'PREROLLUP'   => array('Func' => 'exePreRollUp'),
                'ROLLUP'      => array('Func' => 'exeRollUp'),
                'INROLLUPSET' => array('Func' => 'exeInRollUpSet'),
            );
            $this->rollup = new SimpleObjectiveRollUp();

            $this->sgo = $sgo;
            $key_value_pairs = readLog($this->getContext(), $id, NULL, $this->getType(), array('value'), $sgo);
            $this->value = isset($key_value_pairs['value']) ? $key_value_pairs['value'] : NULL;
        }

        public function terminate() {
            $this->co_trace();
        }

        public function getValue() {
            $this->co_trace();
            return json_decode($this->value, TRUE);
        }

        public function setValue($value) {
            $this->co_trace();
            if (is_array($value) and !is_null($this->value)) {
                $orgArray = json_decode($this->value, TRUE);
                $newArray = array_merge($orgArray, $value);
                $this->value = json_encode($newArray);
            } else {
                $this->value = json_encode($value);
            }
            if (!$this->ignoreTrace) {
                $data_array = array('value' => $this->value);
                return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array, $this->sgo);
            } else {
                return TRUE;
            }
        }

        public function addReadActivityId($activityId) {
            $this->co_trace();
            $this->rollup->addReadOriginActivityIdArray(array($activityId));
        }

        protected function exeInRollUpSet($activityId, $val) {
            $this->co_trace();
            return array('Result' => TRUE, 'Value' => $this->rollup->isInSet());
        }

        protected function exePreRollUp($activityId, $val) {
            $this->co_trace();
            if (!$this->rollup->isInSet()) {
                $this->rollup->putInSet();
                $this->rollup->addRollUpOriginActivityIdArray(array($activityId));
                $this->rollup->exePreRollUp($this);
            }
        }

        protected function exeRollUp($activityId, $val) {
            $this->co_trace();
            if ($this->rollup->isInSet()) {
                //if ($this->rollup->isRollUpReady($this)) { // T-01b fix
                    $this->exeRollUpMain();
                    $this->rollup->getOutOfSet();
                    $this->rollup->exeRollUp($this);
                //}
            }
        }

        protected function exeRollUpMain() {
            $this->co_trace();
        }
    }
