<?php
require_once dirname(__FILE__) . "/SimpleLeaf.php";

class LDLauncher extends LDSimpleLeaf {
    protected $baseUrl;
    protected $returnCmd;
    protected $cancelCmd;
    protected $title;
    protected $secret;

    function addCommands() {
        parent::addCommands();
        $this->cmdTableFromSelf['GETVALUE'] = array('Func' => 'exeToken', 'Type' => 'cmd', 'View' => FALSE);
    }

    function addData($data) {
        parent::addData($data);
        $launcher = selectSingleNode($data, 'launcher');
        $this->baseUrl = $launcher->getAttribute('baseUrl');
        $this->returnCmd = $launcher->getAttribute('returnCmd');
        $this->cancelCmd = $launcher->getAttribute('cancelCmd');
        $this->secret = $launcher->getAttribute('secret');
        $this->title = $launcher->getAttribute('title');
    }

    private function create_token($user)
    {
        $secret = $this->secret;
        $key = $secret;
        $value = $user . ':' . rand() . ':' . time();
	    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("AES-128-CBC"));
	    $raw = openssl_encrypt($value, "AES-128-CBC", $key, $options=OPENSSL_RAW_DATA, $iv);
	    $hmac = hash_hmac('sha256', $raw, $key, $as_binary=true);
	    return trim(base64_encode($iv . $hmac . $raw));
    }

    function exeToken($val, $rtm) {
        $ctx = $this->getContext();
        $uid = $ctx->getUid();
        return array('Result' => TRUE, 'Continue' => FALSE, 'Value' => 'token=' . $this->create_token($uid));
    }

    function getLDInitData() {
        return array('launcher.title' => $this->title, 'launcher.baseUrl' => $this->baseUrl, 'launcher.returnCmd' => $this->returnCmd, 'launcher.cancelCmd' => $this->cancelCmd);
    }
}
