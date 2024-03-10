<?php
require_once dirname(__FILE__) . "/SimpleObjective.php";

class LDSharedObjective extends SimpleObjective {
    protected $sgo;
    protected $value;
    protected $uid;
    protected $cid;

    function __construct(&$ctx, $id, $node, $res, $sgo) {
        parent::__construct($ctx, $id, $node, $res, $sgo);
        $this->sgo = $sgo;
        $this->cid = $ctx->getCid();
        $this->uid = $ctx->getUid();
    }

    public function terminate() {
    }

    public function getValue() {
        $ctx = $this->getContext();
        $key_value_pairs = readLog(makeContext(0, $ctx->getCid(), $ctx->getAttemptCount()), $this->getID(), NULL, $this->getType(), array('value'), TRUE);
        $this->value = isset($key_value_pairs['value']) ? $key_value_pairs['value'] : $this->value;
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
        $data_array = array('value' => $this->value);
        $ctx = $this->getContext();
        return writeLog(makeContext(0, $ctx->getCid(), $ctx->getAttemptCount()), $this->getID(), NULL, $this->getType(), $data_array, TRUE);
    }
}
