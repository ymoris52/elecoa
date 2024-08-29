<?php
    require_once dirname(__FILE__) . '/ActivityBlock.php';
    require_once dirname(__FILE__) . '/SimpleRollUp.php';

    class SimpleBlock extends ActivityBlock {

        protected $rollup;

        function __construct(&$ctx, $num, $node, $res, &$objectives) {
            parent::__construct($ctx, $num, $node, $res, $objectives);
            $this->setData($node);
            $this->setTable();
            $this->rollup = new SimpleRollUp();
        }

        private function setData($node) {
        }

        private function setTable() {
            $this->cmdTableFromChild = array(
                'INIT'         => array('Func' => 'exeInit',        'Type' => 'cmd', 'View' => FALSE),
                'PREROLLUP'    => array('Func' => 'exePreRollUp',   'Type' => 'cmd', 'View' => FALSE),
                'ROLLUP'       => array('Func' => 'exeRollUp',      'Type' => 'cmd', 'View' => FALSE),
                'EXITCOND'     => array('Func' => 'exeExitCond',    'Type' => 'cmd', 'View' => FALSE),
                'CONTINUE'     => array('Func' => 'exeContinue',    'Type' => 'seq', 'View' => TRUE ),
                'PREVIOUS'     => array('Func' => 'exePrevious',    'Type' => 'seq', 'View' => TRUE ),
                'CHOICE'       => array('Func' => 'exeChoice',      'Type' => 'seq', 'View' => FALSE),
                'SUSPEND'      => array('Func' => 'exeSuspend',     'Type' => 'seq', 'View' => TRUE ),
                'EXITALL'      => array('Func' => 'exeExitAll',     'Type' => 'seq', 'View' => TRUE ),
                'RETRY'        => array('Func' => 'exeRetry',       'Type' => 'seq', 'View' => FALSE),
                'RETRYALL'     => array('Func' => 'exeRetryAll',    'Type' => 'seq', 'View' => FALSE),
                'GETVALUE'     => array('Func' => 'exeGetValue'),
            );
            $this->cmdTableFromParent = array(
                'INDEX'        => array('Func' => 'exeIndexP',       'Type' => 'cmd'),
                'CONTINUE'     => array('Func' => 'exeContinueP',    'Type' => 'seq'),
                'PREVIOUS'     => array('Func' => 'exePreviousP',    'Type' => 'seq'),
                'CHOICE'       => array('Func' => 'exeChoiceP',      'Type' => 'seq'),
                'INROLLUPSET'  => array('Func' => 'exeInRollUpSetP'),
                'GETVALUE'     => array('Func' => 'exeGetValueP'),
            );
            $this->cmdTableFromObjective = array(
                'PREROLLUP'    => array('Func' => 'exePreRollUpO'),
                'ROLLUP'       => array('Func' => 'exeRollUpO'),
                'INROLLUPSET'  => array('Func' => 'exeInRollUpSetO'),
            );
        }

        protected function startAttempt() {
            $this->co_trace();
            $this->isActive = TRUE;
            if (!$this->isSus) {
                $this->aCounter++;
            }
        }

        protected function endAttempt($cmd) {
            $this->co_trace();
            $commands = array('EXITCOND', 'ROLLUP', 'PREROLLUP', 'INDEX', 'INIT', 'INITRTM', 'FINRTM', 'READY', 'GETVALUE', 'SETVALUE');
            if (!in_array($cmd, $commands)) {
                $this->isActive = FALSE;
                return TRUE;
            } else {
                return FALSE;
            }
        }

        public function terminate() {
            $this->co_trace();
            return TRUE;
        }

        // INIT
        // in : 呼び出しID, 引数
        // out: 正常フラグ, 続行フラグ, 結果値
        protected function exeInit($id, $val) {
            $this->co_trace();
            $retArray = array();
            if (!($this->isActive)) {
                $this->startAttempt();
            }
            // コマンドリストの作成
            $postAry = array_clone($val);
            //foreach (array_keys($this->cmdTableFromChild) as $k) {
            //    if (!array_key_exists($k, $postAry)) {
            //        $postAry[$k]['Type'] = $this->cmdTableFromChild[$k]['Type'];
            //        $postAry[$k]['View'] = $this->cmdTableFromChild[$k]['View'];
            //    }
            //}
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Value'] = array_clone($postAry);
            return $retArray;
        }

        protected function exePreRollUp($id, $val) {
            $this->co_trace();
            if ($this->rollup->isInSet()) {
                return array('Result' => TRUE, 'Continue' => FALSE);
            } else {
                $this->rollup->putInSet();
                $this->rollup->addRollUpOriginActivityIdArray(array($id));
                $this->rollup->exePreRollUp($this);
                return array('Result' => TRUE, 'Continue' => TRUE);
            }
        }

        protected function exeInRollUpSetP($val) {
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

        protected function exeInRollUpSetO($objectiveId, $val) {
            $this->co_trace();
            return array('Result' => TRUE, 'Value' => $this->rollup->isInSet());
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

        protected function exeRollUp($id, $val) {
            $this->co_trace();
            if ($this->rollup->isInSet()) {
                if ($this->rollup->isRollUpReady($this)) {
                    $this->exeRollUpMain();
                    $this->rollup->getOutOfSet();
                    $this->rollup->exeRollUp($this);
                    return array('Result' => TRUE, 'Continue' => TRUE);
                } else {
                    return array('Result' => TRUE, 'Continue' => FALSE);
                }
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

        protected function exeExitCond($id, $val) {
            $this->co_trace();
            return array('Result' => TRUE, 'Continue' => TRUE, 'Value' => $val);
        }

        protected function exeGetValue($id, $val) {
            $this->co_trace();
            return array('Value' => NULL);
        }

        protected function exeGetValueP($val) {
            $this->co_trace();
            return array('Value' => NULL);
        }

        // in : 呼び出しID, 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        public function exeStart() {
            $this->co_trace();
            $retArray = array();
            $preCheck = $this->checkPreCondition('CONTINUE', NULL, NULL, FALSE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $len = count($this->children);
                $flg = FALSE;
                for ($i = 0; $i < $len; $i++) {
                    $retArray = $this->getChild($i)->callFromParent('CONTINUE', '');
                    if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                        $flg = TRUE;
                        break;
                    }
                    else if (!$retArray['Continue']) {
                        $flg = TRUE;
                        break;
                    }
                }
                if (!$flg) {
                    // 見つからなかった場合
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = TRUE;
                    $retArray['Command'] = 'CONTINUE';
                }
            }
            else {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
            }
            return $retArray;
        }

        // in : 呼び出し ID, 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exeContinue($id, $val) {
            $this->co_trace();
            $retArray = array();
            $preCheck = $this->checkPreCondition('CONTINUE', $id, $val, FALSE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $len = count($this->children);
                $flg = FALSE;
                for ($i = $this->getChildPosition($id) + 1; $i < $len; $i++) {
                    $retArray = $this->getChild($i)->callFromParent('CONTINUE', $val);
                    if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                        $flg = TRUE;
                        break;
                    }
                    else if (!$retArray['Continue']) {
                        $flg = TRUE;
                        break;
                    }
                }
                if (!$flg) {
                    // 見つからなかった場合
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = TRUE;
                    $retArray['Command'] = 'CONTINUE';
                }
            }
            else {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
            }
            return $retArray;
        }

        // in : 呼び出し ID, 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exePrevious($id, $val) {
            $this->co_trace();
            $retArray = array();
            $preCheck = $this->checkPreCondition('PREVIOUS', $id, $val, FALSE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $flg = FALSE;
                for ($i = $this->getChildPosition($id) - 1; $i >= 0; $i--) {
                    $retArray = $this->getChild($i)->callFromParent('PREVIOUS', $val);
                    if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                        $flg = TRUE;
                        break;
                    }
                    else if (!$retArray['Continue']) {
                        $flg = TRUE;
                        break;
                    }
                }
                if (!$flg) {
                    // 見つからなかった場合
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = TRUE;
                }
            }
            else {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
            }
            return $retArray;
        }

        // in : 呼び出し ID, 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exeChoice($id, $val) {
            $this->co_trace();
            $retArray = array();

            $len = count($this->children);
            $flg = FALSE;
            $cmd = 'CHOICE';

            $preCheck = $this->checkPreCondition('CHOICE', $id, $val, FALSE);
            if ($this->getID() === $val) { // このブロックの選択
                $cmd = 'CONTINUE'; // CONTINUE に変更
            }
            for ($i = 0; $i < $len; $i++) {
                $retArray = $this->getChild($i)->callFromParent($cmd, $val);
                if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                    $flg = TRUE;
                    break;
                }
                else if (!$retArray['Continue']) {
                    $flg = TRUE;
                    break;
                }
            }
            if (!$this->isCheckResultEmpty($preCheck)) {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
                return $retArray;
            }
            if (!$flg) {
                // 見つからなかった場合
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                $retArray['Command'] = 'CHOICE';
            }
            return $retArray;
        }

        protected function exeSuspend($id, $val) {
            $this->co_trace();
            $this->isSus = TRUE;
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Command'] = 'SUSPEND';
            return $retArray;
        }

        protected function exeExitAll($id, $val) {
            $this->co_trace();
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = TRUE;
            $retArray['Command'] = 'EXITALL';
            return $retArray;
        }

        protected function exeRetry($id, $val) {
            $this->co_trace();
            $callPos = $this->getChildPosition($id);
            $tmpAct = $this->getChild($callPos);
            $retArray = $tmpAct->callFromParent("CONTINUE", $val); // CONTINUEに変更
            return $retArray;
        }

        protected function exeRetryAll($id, $val) {
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
                'is_active' => $this->isActive,
                'children'  => array()
            );
            $len = count($this->children);
            for ($i = 0; $i < $len; $i++) {
                $tmpArray = $this->getChild($i)->callFromParent('INDEX', $val);
                if (!$tmpArray['Result']) {
                    // 目次で失敗はしない
                    continue;
                }
                // 子の情報を追加
                $resultArray['children'][] = $tmpArray['Value'];
            }
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
                $len = count($this -> children);
                $flg = FALSE;
                for ($i = 0; $i < $len; $i++) { // 各子供のチェック
                    $retArray = $this->getChild($i)->callFromParent('CONTINUE', $val);
                    if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                        $flg = TRUE;
                        unset($retArray['Command']);
                        break;
                    }
                    else if (!$retArray['Continue']) {
                        $flg = TRUE;
                        unset($retArray['Command']);
                        break;
                    }
                }
                if (!$flg) {
                    // 見つからなかった場合
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = TRUE;
                    $retArray['Command'] = 'CONTINUE';
                }
            }
            else if ($preCheck['Result'] === 'skip') {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
            }
            else {
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
                $flg = FALSE;
                for ($i = count($this->children) - 1; $i >= 0; $i--) {
                    $retArray = $this->getChild($i)->callFromParent('PREVIOUS', $val);
                    if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                        $flg = TRUE;
                        unset($retArray['Command']);
                        break;
                    }
                    else if (!$retArray['Continue']) {
                        $flg = TRUE;
                        unset($retArray['Command']);
                        break;
                    }
                }
                if (!$flg) {
                    // 見つからなかった場合
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = TRUE;
                    $retArray['Command'] = 'PREVIOUS';
                }
            }
            else if ($preCheck['Result'] === 'skip') {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
            }
            else {
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

            $len = count($this->children);
            $flg = FALSE;
            $cmd = 'CHOICE';

            $preCheck = $this->checkPreCondition('CHOICE', NULL, $val, TRUE);
            // FIX: CM-07c
            //if ($this->isCheckResultError($preCheck)) {
            //    $retArray['Result'] = FALSE;
            //    $retArray['Continue'] = FALSE;
            //    $retArray['Error'] = $preCheck['Description'];
            //    return $retArray;
            //}
            // FIX: CM-04c
            //if ($preCheck['Result'] === 'skip') {
            //    $retArray['Result'] = TRUE;
            //    $retArray['Continue'] = TRUE;
            //    return $retArray;
            //}
            if ($this->getID() === $val) {// このブロックの選択
                $cmd = 'CONTINUE'; // CONTINUE に変更
            }
            for ($i = 0; $i < $len; $i++) {
                $retArray = $this->getChild($i)->callFromParent($cmd, $val);
                if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                    $flg = TRUE;
                    break;
                }
                else if (!$retArray['Continue']) {
                    $flg = TRUE;
                    break;
                }
            }
            if ($flg and $this->isCheckResultError($preCheck)) {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
                return $retArray;
            }
            if (!$flg) {
                // 見つからなかった場合
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
                $retArray['Command'] = 'CHOICE';
            }
            return $retArray;
        }

        // 前提条件のチェック
        protected function checkPreCondition($cmd, $id, $val, $isDescending) {
            $this->co_trace();
            return $this->makeCheckResult('', '');
        }
    }
