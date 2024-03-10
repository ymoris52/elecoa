<?php
require_once dirname(__FILE__) . "/LDSimpleBlock.php";

class LDThBlock extends LDSimpleBlock {
    protected $secret;
    protected $eventList;
    protected $threadManifestXml;

    function __construct(&$ctx, $num, $node, $res, &$objectives) {
        $this->eventList = array();
        parent::__construct($ctx, $num, $node, $res, $objectives);
    }

    function addData($data) {
        parent::addData($data);
        $events = selectSingleNode($data, 'events');
        if (!is_null($events)) {
            foreach (selectNodes($events, 'event') as $event) {
                $name = $event->getAttribute('name');
                $action = $event->getAttribute('action');
                $value = $event->getAttribute('value');
                $this->eventList[] = array('name' => $name, 'action' => $action, 'value' => $value);
            }
        }
        $threadManifest = selectSingleNode($data, 'threadManifest');
        if (!is_null($threadManifest)) {
            $this->threadManifestXml = $threadManifest->C14N();
        }
        $thread = selectSingleNode($data, 'thread');
        if (!is_null($thread)) {
            $this->secret = $thread->getAttribute('secret');
        }
    }

    function addCommands() {
        parent::addCommands();
        $this->cmdTableFromSelf['EVENT'] = array('Func' => 'exeEvent', 'Type' => 'cmd', 'View' => FALSE);
        $this->cmdTableFromSelf['CREATETHREAD'] = array('Func' => 'exeCreateThread', 'Type' => 'cmd', 'View' => FALSE);
        $this->cmdTableFromChild['CREATETHREAD'] = array('Func' => 'exeCreateThreadC', 'Type' => 'seq', 'View' => FALSE);
    }

    function exeEvent($val, $rtm) {
        $this->co_trace();
        $eventName = $val;
        foreach ($this->eventList as $event) {
            if ($event['name'] === $eventName) {
                $action = $event['action'];
                $value = $event['value'];
                return $this->callCommand($action, $value, '');
            }
        }
        return array('Result' => true, 'Continue' => true, 'NextID' => '');
    }

    private function create_token($user)
    {
        $secret = $this->secret;
        $key = base64_decode($secret);
        $value = $user . ':' . rand() . ':' . time();
	    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-128-CBC"));
	    $raw = openssl_encrypt($value, "AES-128-CBC", $key, $options=OPENSSL_RAW_DATA, $iv);
	    $hmac = hash_hmac('sha256', $raw, $key, $as_binary=true);
	    return trim(base64_encode($iv . $hmac . $raw));
    }

    function exeCreateThread($val, $rtm) {
        // 議論の新規作成
        $ctx = $this->getContext();
        $uid = $ctx->getUid();
        $url = $val;
        $data = array('noui' => 'true', 'token' => $this->create_token($uid), 'comment' => '');
        $options = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
        ));
        $contents = file_get_contents($url, false, stream_context_create($options));
        $tid = $contents;
        //議論ノードの追加
        $threadId = 'T-' . $tid;
        $manifest = $this->threadManifestXml;
        $manifest = str_replace('{RAWTID}', $tid, $manifest);
        $manifest = str_replace('{TID}', $threadId, $manifest);
        $manifest = str_replace('{TOWNER}', $uid, $manifest);
        $manifest = str_replace('{TOWNERNAME}', elecoa_session_get_username(), $manifest);
        $manifest = str_replace('{TCREATED_AT}', date('Y-m-d H:i:s'), $manifest);
        $platform = Platform::getInstance();
        $obj = $platform->appendByManifest($this, $manifest);
        return $obj->callFromParent('CHOICE', $threadId);
    }

    function exeCreateThreadC($id, $val) {
        //議論ノードの追加
        $threadId = 'T-' . $val;
        $ctx = $this->getContext();
        $uid = $ctx->getUid();
        $manifest = $this->threadManifestXml;
        $manifest = str_replace('{RAWTID}', $val, $manifest);
        $manifest = str_replace('{TID}', $threadId, $manifest);
        $manifest = str_replace('{TOWNER}', $uid, $manifest);
        $manifest = str_replace('{TOWNERNAME}', elecoa_session_get_username(), $manifest);
        $manifest = str_replace('{TCREATED_AT}', date('Y-m-d H:i:s'), $manifest);
        $platform = Platform::getInstance();
        $obj = $platform->appendByManifest($this, $manifest);
        return $obj->callFromParent('CHOICE', $threadId);
    }

}
