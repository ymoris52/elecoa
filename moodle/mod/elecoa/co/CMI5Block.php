<?php
require_once dirname(__FILE__) . "/SimpleBlock.php";

class CMI5Block extends SimpleBlock {
    protected $uid;
    protected $cid;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        parent::__construct($ctx, $num, $node, $res, $objectives);
        $this->uid = $ctx->getUid();
        $this->cid = $ctx->getCid();
        $this->attempt = $ctx->getAttemptCount();
        $this->addData($this->dataNode);
        $this->addCommands();
        $ctx = makeContext($this->uid, $this->cid, 1);
        $values = readLog($ctx, $this->getID(), NULL, 'CMI5', array('status'));
        if (isset($values['status'])) {
            $this->sufficientlyCompleted = TRUE;
        } else {
            $this->sufficientlyCompleted = FALSE;
        }
        $this->changedToCompleted = FALSE;
    }

    function addCommands() {
        $this->cmdTableFromChild['EXIT'] = array('Func' => 'exeExit');
    }

    function addData($data) {
    }

    function exeExit($id, $val) {
        $sessionId = $val['sessionId'];
        if ($this->changedToCompleted) {
	        $cmi5ext = getCMI5Extension();
            $registration = $cmi5ext->getRegistration($this->cid, $this->uid);
	        $userName = $cmi5ext->getUserName($this->uid);
	        $cmi5ActivityIdBase = $cmi5ext->getSiteBaseUrl() . 'activities/' . $this->cid . '/';
            $title = $this->getTitle();
            $cmi5ext->postSatisfied($this->uid, $userName, $registration, $cmi5ActivityIdBase . $this->getID(), 'block', $title, $sessionId);
        }
        $this->changedToCompleted = FALSE;
        return array('Result' => TRUE, 'Continue' => TRUE, 'Value' => array('sessionId' => $sessionId));
    }

    protected function exeIndexP($val) {
        $result = parent::exeIndexP($val);
        $result['Value']['sufficientlyCompleted'] = $this->sufficientlyCompleted;
        return $result;
    }

    protected function exeGetValueP($params) {
        $value = NULL;
        if ($params[0] === 'cmi5.SufficientlyCompleted') {
            $value = $this->sufficientlyCompleted;
        }
        return array('Value' => $value);
    }

    function exeRollUpMain() {
        if (!$this->sufficientlyCompleted) {
            $len = count($this->children);
            $allCompleted = TRUE;
	        for ($i=0; $i < $len; $i++) {
	            $activity = $this->getChild($i);
	            $result = $activity->callFromParent('GETVALUE', array('cmi5.SufficientlyCompleted'));
	            if (!$result['Value']) {
	                $allCompleted = FALSE;
	            }
	        }
	        if ($allCompleted) {
                $ctx = makeContext($this->uid, $this->cid, 1);
                writeLog($ctx, $this->getID(), 0, 'CMI5', array('status' => 'SufficientlyCompleted'));
	            $this->changedToCompleted = TRUE;
                $this->sufficientlyCompleted = TRUE;
	        }
        }
    }
}
