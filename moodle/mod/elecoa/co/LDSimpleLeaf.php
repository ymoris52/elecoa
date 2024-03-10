<?php
require_once dirname(__FILE__) . "/SimpleLeaf.php";

class LDSimpleLeaf extends SimpleLeaf {
    private $uid;
    private $cid;
    private $available;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->uid = $ctx->getUid();
        $this->cid = $ctx->getCid();
        $this->available = TRUE;
        $this->addData($this->dataNode);
        $this->addCommands();
    }

    private function isOwner($node_owner) {
        return ($node_owner === $this->uid);
    }

    function addCommands() {
    }

    function getAPIAdapterProvider(){
        return createAPIAdapterProvider('LD2');
    }

    function addData($data) {
        $permission = selectSingleNode($data, 'permission');
        if (!is_null($permission)) {
            $this->available = FALSE;
            $node_owner = $permission->getAttribute('owner');
            $owner = selectSingleNode($permission, 'owner');
            if (!is_null($owner) and $this->isOwner($node_owner)) {
                $this->available = TRUE;
                return;
            }
            foreach (selectNodes($permission, 'group') as $group) {
                $grouping = $group->getAttribute('grouping');
                if (colib::checkIsSameGroupingGroup($node_owner, $this->uid, $this->cid, $grouping)) {
                    $this->available = TRUE;
                    return;
                }
            }
            foreach (selectNodes($permission, 'user') as $user) {
                if ($this->uid === $user->getAttribute('id')) {
                    $this->available = TRUE;
                    return;
                }
            }
            $other = selectSingleNode($permission, 'other');
            if (!is_null($other) and !$this->isOwner($node_owner)) {
                $this->available = TRUE;
                return;
            }
        }
    }

    function exeIndexP($val) {
        $this->co_trace();
        if ($this->available) {
            return parent::exeIndexP($val);
        } else {
            return array('Result' => FALSE);
        }
    }

    function exeContinueP($val) {
        $this->co_trace();
        if ($this->available) {
            return parent::exeContinueP($val);
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE);
        }
    }

    function exePreviousP($val) {
        $this->co_trace();
        if ($this->available) {
            return parent::exePreviousP($val);
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE);
        }
    }

    function exeChoiceP($val) {
        $this->co_trace();
        if ($this->available) {
            return parent::exeChoiceP($val);
        } else {
            return array('Result' => TRUE, 'Continue' => TRUE);
        }
    }

    function exeInit($val, $rtm) {
        $result = parent::exeInit($val, $rtm);
        if ($result['Result']) {
            $result['Value'] = preg_replace('/rtm_button_param=.*/', 'rtm_button_param=SUSPEND', $result['Value']);
            $data = $this->getLDInitData();
            if (is_null($data)) {
                $data = array();
            }
            $result['Value'] .= "\ninit_result_js=" . json_encode($data);
        }
        return $result;
    }

    protected function getLDInitData() {
        return NULL;
    }

    function terminate() {
        $this->co_trace();
        $data_array = array('isSuspend' => 'true');
        return writeLog($this->getContext(), $this->getID(), NULL, $this->getType(), $data_array);
    }
}
