<?php

function makeContext($uid, $cid, $attempt) {
    $ctx = new ElecoaContext();
    $ctx->setUid($uid);
    $ctx->setCid($cid);
    $ctx->setAttemptCount($attempt);
    return $ctx;
}

class ElecoaContext {

    private $uid;
    private $cid;
    private $attempt;

    public function getUid() {
        return $this->uid;
    }

    public function getCid() {
        return $this->cid;
    }

    public function getAttemptCount() {
        return $this->attempt;
    }

    public function setUid($uid) {
        $this->uid = $uid;
    }

    public function setCid($cid) {
        $this->cid = $cid;
    }

    public function setAttemptCount($count) {
        $this->attempt = $count;
    }

    public function __toString() {
        return '[' . $this->uid . ',' . $this->cid . ',' . $this->attempt . ']';
    }
}
