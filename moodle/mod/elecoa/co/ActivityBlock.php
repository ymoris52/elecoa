<?php
    require_once dirname(__FILE__) . '/ActivityBase.php';

    abstract class ActivityBlock extends ActivityBase {
        protected $cmdTableFromChild;  // 子からのメッセージテーブル
        protected $cmdTableFromParent; // 親からのメッセージテーブル
        protected $cmdTableFromSelf;   // 自分で処理できるメッセージテーブル

        function __construct(&$ctx, $num, $node, $res, &$objectives) {
            parent::__construct($ctx, $num, $node, $res, $objectives);
            $this->cmdTableFromChild = array();
            $this->cmdTableFromParent = array();
            $this->cmdTableFromSelf = array();
        }

        public function getType() {
            return 'BLOCK';
        }

        public function callFromChild($id, $cmd, $val, $activityId) {
            $this->co_trace();
            if ($activityId === NULL and array_key_exists($cmd, $this->cmdTableFromChild)) {
                // コマンド定義あり
                $method = $this->cmdTableFromChild[$cmd]['Func'];
                $retArray = $this->$method($id, $val);
                if (isset($retArray['Continue']) and $retArray['Continue']) {
                    $value = isset($retArray['Value']) ? $retArray['Value'] : $val;
                    if ($this->isActive) {
                        $this->endAttempt($cmd);
                    }
                    return $this->getParent()->callFromChild($this->getID(), $cmd, $value, $activityId);
                } else {
                    return $retArray;
                }
            } else {
                if ($this->isActive) {
                    $this->endAttempt($cmd);
                }
                if ($this->getID() === $activityId) { // FIX: T-01b
                    return $this->getParent()->callFromChild($this->getID(), $cmd, $val, NULL);
                } else {
                    return $this->getParent()->callFromChild($this->getID(), $cmd, $val, $activityId);
                }
            }
        }

        public function callFromObjective($objectiveId, $cmd, $val) {
            $this->co_trace();
            if (array_key_exists($cmd, $this->cmdTableFromObjective)) {
                $method = $this->cmdTableFromObjective[$cmd]['Func'];
                return $this->$method($objectiveId, $val);
            } else {
                return array('Result' => FALSE);
            }
        }

        public function callFromParent($cmd, $val) {
            $this->co_trace();
            $result = NULL;

            if (array_key_exists($cmd, $this->cmdTableFromParent)) {
                $method = $this->cmdTableFromParent[$cmd]['Func'];
                $result = $this->$method($val);
            } else {
                $result = array('Result' => TRUE, 'Continue' => TRUE);
            }

            return $result;
        }

        // プラットフォームからのメッセージ関数
        public function callCommand($cmd, $val, $rtm) {
            $this->co_trace();
            $retArray = NULL;

            if (array_key_exists($cmd, $this->cmdTableFromSelf)) {
                // 自分で処理できる
                if ($this->cmdTableFromSelf[$cmd]['Type'] === 'seq') {
                    // シーケンスは処理しない
                    $retArray = array(
                        'Result' => FALSE,
                        'Continue' => FALSE
                    );
                } else {
                    // シーケンス以外
                    $method = $this->cmdTableFromSelf[$cmd]['Func'];
                    $retArray = $this->$method($val, $rtm);
                }
            } else {
                // 処理できないコマンド
                $retArray = array(
                    'Result' => FALSE,
                    'Continue' => FALSE
                );
            }

            return $retArray;
        }
    }
