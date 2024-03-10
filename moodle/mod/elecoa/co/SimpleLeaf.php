<?php
    require_once dirname(__FILE__) . '/ActivityLeaf.php';
    require_once dirname(__FILE__) . '/SimpleRollUp.php';

    class SimpleLeaf extends ActivityLeaf {

        protected $rollup;

        function __construct(&$ctx, $num, $node, $res, &$objectives) {
            parent::__construct($ctx, $num, $node, $res, $objectives);
            $this->setData($node);
            $this->setTable();
            $this->rollup = new SimpleRollUp();
        }

        private function setData($node) {
            $this->strUrl = $node->getAttribute('href');
            $this->strResourceIdentifier = $node->getAttribute('resourceIdentifier');
        }

        private function setTable() {
            $this->cmdTableFromSelf = array(
                'READY'         => array('Func' => 'exeReady',        'Type' => 'seq', 'View' => FALSE),
                'INIT'          => array('Func' => 'exeInit',         'Type' => 'cmd', 'View' => FALSE),
                'INITRTM'       => array('Func' => 'exeInit',         'Type' => 'cmd', 'View' => FALSE),
                'EXITCOND'      => array('Func' => 'exeExitCond',     'Type' => 'cmd', 'View' => FALSE),
                'PREROLLUP'     => array('Func' => 'exePreRollUp',    'Type' => 'cmd', 'View' => FALSE),
                'ROLLUP'        => array('Func' => 'exeRollUp',       'Type' => 'cmd', 'View' => FALSE),
                'SUSPEND'       => array('Func' => 'exeSuspend',      'Type' => 'seq', 'View' => TRUE),
                'EXITALL'       => array('Func' => 'exeExitAll',      'Type' => 'seq', 'View' => TRUE),
                'RETRY'         => array('Func' => 'exeRetry',        'Type' => 'seq', 'View' => FALSE),
                'RETRYALL'      => array('Func' => 'exeRetryAll',     'Type' => 'seq', 'View' => FALSE),
            );
            $this->cmdTableFromParent = array(
                'INDEX'         => array('Func' => 'exeIndexP',       'Type' => 'cmd'),
                'CONTINUE'      => array('Func' => 'exeContinueP',    'Type' => 'seq'),
                'PREVIOUS'      => array('Func' => 'exePreviousP',    'Type' => 'seq'),
                'CHOICE'        => array('Func' => 'exeChoiceP',      'Type' => 'seq'),
                'INROLLUPSET'   => array('Func' => 'exeInRollUpSetP'),
                'GETVALUE'      => array('Func' => 'exeGetValueP'),
            );
            $this->cmdTableFromObjective = array(
                'PREROLLUP'     => array('Func' => 'exePreRollUpO'),
                'ROLLUP'        => array('Func' => 'exeRollUpO'),
                'INROLLUPSET'   => array('Func' => 'exeInRollUpSetO'),
            );
        }

        public function getAPIAdapterProvider() {
            return createAPIAdapterProvider('Simple'); //defined in init_www.php
        }

        public function terminate() {
            $this->co_trace();
            $data_array = array('isSuspend' => $this->isSus ? 'true' : 'false');
            return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array);
        }

        protected function addWriteObjectiveId($objectiveId) {
            $this->co_trace();
            $this->rollup->addWriteTargetObjectiveIdArray(array($objectiveId));
        }

        protected function startAttempt() {
            $this->co_trace();
            $this->isActive = TRUE;
            $this->isSus = FALSE;
            $this->aCounter++;
        }

        protected function endAttempt($cmd) {
            $this->co_trace();
            $this->isActive = FALSE;
            return array('Result' => TRUE);
        }

        // コンテンツの初期化時に一度だけ呼ばれる
        protected function exeReady($val, $rtm) {
            $this->co_trace();
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['NextID'] = $this->getID();
            $retArray['Continue'] = FALSE;
            return $retArray;
        }

        protected function exeInit($val, $rtm) {
            $this->co_trace();
            if ($this->isActive) {
                return array('Result' => FALSE, 'Continue' => FALSE);
            }
            $this->startAttempt();
            $postAry = array();
            foreach (array_keys($this->cmdTableFromSelf) as $k) {
                $postAry[$k]['Type'] = $this->cmdTableFromSelf[$k]['Type'];
                $postAry[$k]['View'] = $this->cmdTableFromSelf[$k]['View'];
            }
            $retArray = $this->getParent()->callFromChild($this->getID(), 'INIT', $postAry, NULL);
            if (!$retArray['Result']) {
                // 失敗
                return array('Result' => FALSE, 'Continue' => FALSE);
            }
            $this->cmdTableFromAncestor = array_clone($retArray['Value']);
            $retStr = '';
            foreach (array_keys($this->cmdTableFromAncestor) as $k) {
                if ($this->cmdTableFromAncestor[$k]['View']) {
                    $retStr .= $k . ',';
                }
            }
            $retArray['Value'] = 'rtm_button_param=' . $retStr;
            return $retArray;
        }

        protected function exeInRollUpSetP($val) {
            $this->co_trace();
            return array('Result' => TRUE, 'Value' => $this->rollup->isInSet());
        }

        protected function exePreRollUp($val, $rtm) {
            $this->co_trace();
            if ($this->rollup->isInSet()) {
                return array('Result' => TRUE, 'Continue' => FALSE);
            } else {
                $this->rollup->putInSet();
                $this->rollup->exePreRollUp($this);
                return array('Result' => TRUE, 'Continue' => TRUE);
            }
        }

        protected function exeInRollUpSetO($objectiveId, $val) {
            $this->co_trace();
            return array('Result' => TRUE, 'Value' => $this->rollup->isInSet());
        }

        protected function exePreRollUpO($objectiveId, $val) {
            $this->co_trace();
            if (!$this->rollup->isInSet()) {
                $this->rollup->putInSet();
                $this->rollup->addRollUpOriginObjectiveIdArray(array($objectiveId));
            }
            return array('Result' => TRUE);
        }

        protected function exeRollUpO($objectiveId, $val) {
            $this->co_trace();
            if ($this->rollup->isRollUpReady($this)) {
                $this->exeRollUpFromObjectiveMain();
                $this->rollup->getOutOfSet();
                $this->rollup->exeRollUp($this);
            }
            return array('Result' => TRUE);
        }

        protected function exeRollUp($val, $rtm) {
            $this->co_trace();
            $this->endAttempt(NULL);
            if ($this->rollup->isInSet()) {
                $this->exeRollUpMain();
                $this->rollup->getOutOfSet();
                $this->rollup->exeRollUp($this);
                return array('Result' => TRUE, 'Continue' => TRUE);
            } else {
                return array('Result' => TRUE, 'Continue' => FALSE);
            }
        }

        protected function exeRollUpMain() {
            $this->co_trace();
        }

        protected function exeRollUpFromObjectiveMain() {
            $this->co_trace();
        }

        protected function exeExitCond($val, $rtm) {
            $this->co_trace();
            return array('Result' => TRUE, 'Continue' => TRUE);
        }

        protected function exeSuspend($val, $rtm) {
            $this->co_trace();
            $this->isSus = TRUE;
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Command'] = 'SUSPEND';
            return $retArray;
        }

        protected function exeExitAll($val, $rtm) {
            $this->co_trace();
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Command'] = 'EXITALL';
            return $retArray;
        }

        protected function exeRetry($val, $rtm) {
            $this->co_trace();
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = FALSE;
            $retArray['NextID'] = $this->getID();
            $retArray['Command'] = 'RETRY';
            return $retArray;
        }

        protected function exeRetryAll($val, $rtm) {
            $this->co_trace();
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Command'] = 'RETRYALL';
            return $retArray;
        }

        // in : 引数
        // out: 正常フラグ, 続行フラグ, 結果値
        protected function exeIndexP($val) {
            $this->co_trace();
            $resultArray = array(
                'type'      => $this->getType(), 
                'title'     => $this->getTitle(), 
                'id'        => $this->getID(), 
                'is_active' => $this->isActive
            );
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Value'] = $resultArray;
            return $retArray;
        }

        // in : 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exeContinueP($val) {
            $this->co_trace();
            $retArray = array();
            $preCheck = $this->checkPreCondition('CONTINUE', NULL, $val, TRUE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = FALSE;
                $retArray['NextID'] = $this->getID();
            } else if ($preCheck['Result'] === 'skip') {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
            } else {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
            }
            return $retArray;
        }

        // in : 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exePreviousP($val) {
            $this->co_trace();
            $retArray = array();
            $preCheck = $this->checkPreCondition('PREVIOUS', NULL, $val, TRUE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = FALSE;
                $retArray['NextID'] = $this->getID();
            } else if ($preCheck['Result'] === 'skip') {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
            } else {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
            }
            return $retArray;
        }

        // in : 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exeChoiceP($val) {
            $this->co_trace();
            $retArray = array();
            if ($this->getID() === $val) {
                $preCheck = $this->checkPreCondition('CHOICE', NULL, $val, TRUE);
                if ($this->isCheckResultEmpty($preCheck)) {
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = FALSE;
                    $retArray['NextID'] = $this->getID();
                } else {
                    $retArray['Result'] = FALSE;
                    $retArray['Continue'] = FALSE;
                    $retArray['Error'] = $preCheck['Description'];
                }
            } else {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
            }
            return $retArray;
        }

        protected function exeGetValueP($val) {
            $this->co_trace();
            return array('Value' => NULL);
        }

        protected function checkPreCondition($cmd, $id, $val, $isDescending) {
            $this->co_trace();
            return $this->makeCheckResult('', '');
        }
    }
