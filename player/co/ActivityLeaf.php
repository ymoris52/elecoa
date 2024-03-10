<?php
    require_once dirname(__FILE__) . '/ActivityBase.php';

    abstract class ActivityLeaf extends ActivityBase {
        protected $strUrl;               // URL
        protected $strResourceIdentifier;
        protected $cmdTableFromSelf;     // 自分で処理できるメッセージテーブル
        protected $cmdTableFromAncestor; // 祖先が処理できるメッセージテーブル
        protected $cmdTableFromParent;   // 親からのメッセージテーブル

        function __construct(&$ctx, $num, $node, $res, &$objectives) {
            parent::__construct($ctx, $num, $node, $res, $objectives);
            $this->cmdTableFromSelf = array();
            $this->cmdTableFromParent = array();
        }

        public function getType() {
            return 'LEAF';
        }

        public final function getURL() {
            return $this->strUrl;
        }

        public final function getResourceIdentifier() {
            return $this->strResourceIdentifier;
        }

        public function getAPIAdapterProvider(){
            return createAPIAdapterProvider('Base'); //defined in init_www.php
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

        public function callFromObjective($objectiveId, $cmd, $val) {
            $this->co_trace();
            if (array_key_exists($cmd, $this->cmdTableFromObjective)) {
                $method = $this->cmdTableFromObjective[$cmd]['Func'];
                $result = $this->$method($objectiveId, $val);
                if (isset($result['Result']) and $result['Result']) {
                    return $this->getParent()->callFromChild($this->getID(), $cmd, $val, NULL);
                } else {
                    return $result;
                }
            } else {
                return array('Result' => FALSE);
            }
        }

        public function callCommand($cmd, $val, $activityId) {
            $this->co_trace();
            if (($this->getID() === $activityId or $activityId === NULL) and array_key_exists($cmd, $this->cmdTableFromSelf)) {
                $method = $this->cmdTableFromSelf[$cmd]['Func'];
                $result = $this->$method($val, NULL);
                if (isset($result['Continue']) and $result['Continue']) {
                    $valueForParent = isset($result['Value']) ? $result['Value'] : $val;
                    $retval = $this->getParent()->callFromChild($this->getID(), $cmd, $valueForParent, $activityId);
                    return $retval;
                } else {
                    return $result;
                }
            } else {
                return $this->getParent()->callFromChild($this->getID(), $cmd, $val, $activityId);
            }
        }
    }
