<?php
require_once dirname(__FILE__) . "/LDSimpleBlock.php";

class LDQBlock extends LDSimpleBlock {
    protected $questionManifestXml;

    function addCommands() {
        parent::addCommands();
        $this->cmdTableFromChild['CREATEQUESTION'] = array('Func' => 'exeCreateQuestionC', 'Type' => 'seq', 'View' => FALSE);
    }

    function addDOMData($node) {
        parent::addDOMData($node);
        $questionManifest = selectSingleDOMNode($node, 'questionManifest');
        $this->questionManifestXml = $questionManifest->C14N();
    }

    function exeCreateQuestionC($id, $val) {
        //問題ノードの追加
        $questionId = 'Q-' . $val;
        $ctx = $this->getContext();
        $uid = $ctx->getUid();
        $manifest = $this->questionManifestXml;
        $manifest = str_replace('{RAWQID}', $val, $manifest);
        $manifest = str_replace('{QID}', $questionId, $manifest);
        $manifest = str_replace('{QOWNER}', $uid, $manifest);
        $manifest = str_replace('{QOWNERNAME}', elecoa_session_get_username(), $manifest);
        $manifest = str_replace('{QCREATED_AT}', date('Y-m-d H:i:s'), $manifest);
        $platform = Platform::getInstance();
        $obj = $platform->appendByManifest($this, $manifest);
        return $obj->callFromParent('CHOICE', $questionId);
    }
}
