<?php
    require_once dirname(__FILE__) . '/ActivityRoot.php';
    require_once dirname(__FILE__) . '/SimpleRollUp.php';

    class SimpleRoot extends ActivityRoot {

        protected $rollup;

        function __construct(&$ctx, $num, $node, $res, &$objectives) {
            parent::__construct($ctx, $num, $node, $res, $objectives);
            $this->setData($node);
            $this->setTable();
            $this->rollup = new SimpleRollUp();
        }

        private function setData($node) {
            // nop
        }

        // 子からのメッセージテーブルをセット
        private function setTable() {
            $this->cmdTableFromChild = array(
                'INIT'        => array('Func' => 'exeInit',        'Type' => 'cmd', 'View' => FALSE),
                'INDEX'       => array('Func' => 'exeIndex',       'Type' => 'cmd', 'View' => FALSE),
                'PREROLLUP'   => array('Func' => 'exePreRollUp',   'Type' => 'cmd', 'View' => FALSE),
                'ROLLUP'      => array('Func' => 'exeRollUp',      'Type' => 'cmd', 'View' => FALSE),
                'CONTINUE'    => array('Func' => 'exeContinue',    'Type' => 'seq', 'View' => TRUE ),
                'PREVIOUS'    => array('Func' => 'exePrevious',    'Type' => 'seq', 'View' => TRUE ),
                'CHOICE'      => array('Func' => 'exeChoice',      'Type' => 'seq', 'View' => FALSE),
                'SUSPEND'     => array('Func' => 'exeSuspend',     'Type' => 'seq', 'View' => TRUE ),
                'EXITALL'     => array('Func' => 'exeExitAll',     'Type' => 'seq', 'View' => TRUE ),
                'EXITCOND'    => array('Func' => 'exeExitCond',    'Type' => 'seq', 'View' => FALSE),
                'RETRY'       => array('Func' => 'exeRetry',       'Type' => 'seq', 'View' => FALSE),
                'RETRYALL'    => array('Func' => 'exeRetryAll',    'Type' => 'seq', 'View' => FALSE),
                'GETVALUE'     => array('Func' => 'exeGetValue'),
            );
            $this->cmdTableFromObjective = array(
                'PREROLLUP'   => array('Func' => 'exePreRollUpO'),
                'ROLLUP'      => array('Func' => 'exeRollUpO'),
                'INROLLUPSET' => array('Func' => 'exeInRollUpSetO'),
            );
        }

        protected function startAttempt() {
            $this->co_trace();
            $this->isActive = TRUE;
        }

        protected function endAttempt($cmd) {
            $this->co_trace();
            $this->isActive = FALSE;
            return TRUE;
        }

        public function terminate() {
            $this->co_trace();
            return TRUE;
        }

        // INIT
        // クライアントにコンテンツがロードされたときに発行
        protected function exeInit($id, $val) {
            $this->co_trace();
            $retArray = array();
            if (!($this -> isActive)) {
                $this -> startAttempt();
            }
            // コマンドリストの作成
            $postAry = array_clone($val);
            //foreach (array_keys($this -> cmdTableFromChild) as $k) {
            //    if (!array_key_exists($k, $postAry)) {
            //        $postAry[$k]['Type'] = $this -> cmdTableFromChild[$k]['Type'];
            //        $postAry[$k]['View'] = $this -> cmdTableFromChild[$k]['View'];
            //    }
            //}
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = FALSE; // ROOT なので終了
            $retArray['Value'] = array_clone($postAry);
            return $retArray;
        }

        // in : 引数, アクティビティ配列, オブジェクティブ配列
        // out: 正常フラグ, 続行フラグ, 結果値
        protected function exeIndex($id, $val) {
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
                $retArray = $this->getChild($i)->callFromParent('INDEX', $val);
                if (!$retArray['Result']) {
                    // 目次で失敗はしない
                    continue;
                }
                // 子の情報を追加
                $resultArray['children'][] = $retArray['Value'];
            }
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = FALSE;
            $retArray['Value'] = $resultArray;
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

        // in : 呼び出し ID, 引数, アクティビティ配列, オブジェクティブ配列
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
                    $retArray = array();
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = FALSE;
                }
            }
            else {
                $retArray['Result'] = FALSE;
                $retArray['Continue'] = FALSE;
                $retArray['Error'] = $preCheck['Description'];
            }
            return $retArray;
        }

        // in : 呼び出し ID, 引数, アクティビティ配列, オブジェクティブ配列
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exeContinue($id, $val) {
            $this->co_trace();
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
                $retArray = array();
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = FALSE;
            }
            return $retArray;
        }

        // in : 呼び出し ID, 引数
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exePrevious($id, $val) {
            $this->co_trace();
            $preCheck = $this->checkPreCondition('PREVIOUS', $id, $val, FALSE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $len = count($this->children);
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
                    $retArray = array();
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = FALSE;
                }
            } else if ($preCheck['Result'] === 'skip') {
                $retArray['Result'] = TRUE;
                $retArray['Continue'] = TRUE;
            } else {
                $retArray = array('Result' => FALSE, 'Continue' => FALSE, 'Error' => $preCheck['Description']);
            }
            return $retArray;
        }

        // in : 呼び出し ID, 引数, アクティビティ配列, オブジェクティブ配列
        // out: 正常フラグ, 続行フラグ, 配信候補 ID
        protected function exeChoice($id, $val) {
            $this->co_trace();
            $preCheck = $this->checkPreCondition('CHOICE', $id, $val, FALSE);
            if ($this->isCheckResultEmpty($preCheck)) {
                $len = count($this->children);
                $flg = FALSE;
                $retArray = array();
                if ($this->strID === $val) {
                    // 自分が呼ばれた場合
                    for ($i = 0; $i < $len; $i++) {
                        // CONTINUEに変更
                        $retArray = $this->getChild($i)->callFromParent('CONTINUE', $val);
                        if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                            $flg = TRUE;
                            break;
                        } else if (!$retArray['Continue']) {
                            $flg = TRUE;
                            break;
                        }
                    }
                } else {
                    for ($i = 0; $i < $len; $i++) {
                        $tmpAct = $this->getChild($i);
                        //if ($tmpAct -> getID() === $id) {
                        //    continue;
                        //}
                        $retArray = $tmpAct->callFromParent('CHOICE', $val);
                        if ($retArray['Result'] and isset($retArray['NextID']) and ($retArray['NextID'] !== '')) {
                            $flg = TRUE;
                            break;
                        } else if (!$retArray['Continue']) {
                            $flg = TRUE;
                            break;
                        }
                    }
                }
                if (!$flg) {
                    // 見つからなかった場合
                    $retArray = array();
                    $retArray['Result'] = TRUE;
                    $retArray['Continue'] = FALSE;
                }
            } else {
                $retArray = array('Result' => FALSE, 'Continue' => FALSE, 'Error' => $preCheck['Description']);
            }
            return $retArray;
        }

        protected function exeSuspend($id, $val) {
            $this->co_trace();
            $retArray = array();
            $this->isSus = TRUE;
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = FALSE;
            $retArray['Command'] = 'SUSPEND';
            return $retArray;
        }

        protected function exeExitAll($id, $val) {
            $this->co_trace();
            $retArray = array();
            $retArray['Result'] = TRUE;
            $retArray['Continue'] = FALSE;
            $retArray['Command'] = 'EXITALL';
            return $retArray;
        }

        // 配下のリトライ
        protected function exeRetry($id, $val) {
            $this->co_trace();
            $callPos = $this->getChildPosition($id);
            $tmpAct = $this->getChild($callPos);
            $retArray = $tmpAct->callFromParent("CONTINUE", $val);// CONTINUEに変更
            return $retArray;
        }

        protected function exeRetryAll($id, $val) {
            $this->co_trace();
            return $this->exeStart();
        }

        protected function exeExitCond($id, $val) {
            $this->co_trace();
            return array('Result' => TRUE, 'Continue' => FALSE, 'Value' => $val);
        }

        protected function checkPreCondition($cmd, $id, $val, $isDescending) {
            $this->co_trace();
            return $this->makeCheckResult('', '');
        }
    }
