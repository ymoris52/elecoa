<?php
require_once dirname(__FILE__) . "/SimpleBlock.php";

class LDSimpleBlock extends SimpleBlock {
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
}
