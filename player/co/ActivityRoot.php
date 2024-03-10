<?php
    require_once dirname(__FILE__) . '/ActivityBase.php';

    abstract class ActivityRoot extends ActivityBase {
        protected $cmdTableFromChild; // 子からのメッセージテーブル

        function __construct(&$ctx, $num, $node, $res, &$objectives) {
            parent::__construct($ctx, $num, $node, $res, $objectives);
            $this->cmdTableFromChild = array();
        }

        public function getType() {
            return 'ROOT';
        }

        public function callFromChild($id, $cmd, $val, $activityId) {
            $this->co_trace();
            if (array_key_exists($cmd, $this->cmdTableFromChild)) {
                // コマンド定義あり
                $method = $this->cmdTableFromChild[$cmd]['Func'];
                return $this->$method($id, $val);
            } else {
                return array('Result' => FALSE);
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
    }
