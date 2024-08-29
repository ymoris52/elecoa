<?php
require_once dirname(__FILE__) . "/LDSimpleBlock.php";

class LDDynamicNodeBlock extends LDSimpleBlock {
    protected $manifestXmls = array();

    function addCommands() {
        parent::addCommands();
        $this->cmdTableFromChild['CREATENODE'] = array('Func' => 'exeCreateNodeC');
    }

    function addDOMData($node) {
        parent::addDOMData($node);
        $manifestsNode = selectSingleDOMNode($node, 'manifests');
        if ($manifestsNode != null) {
            $manifestNodes = selectDOMNodes($manifestsNode, 'manifest');
            if ($manifestNodes != null) {
                $len = count($manifestNodes);
                for ($i=0; $i < $len; $i++) {
                    $manifestId = $manifestNodes[$i]->getAttribute('identifier');
                    $this->manifestXmls[$manifestId] = $manifestNodes[$i]->C14N();
                }
            }
        }
    }

    protected function exeCreateNodeC($id, $val) {
        // ノードの追加
        $parameters = json_decode($val, true);
        $manifestId = $parameters['manifestId'];
        $resourceId = $parameters['resourceId'];
        $ctx = $this->getContext();
        $uid = $ctx->getUid();
        $manifest = $this->manifestXmls[$manifestId];
        $manifest = str_replace('{RESOURCEID}', $resourceId, $manifest);
        $manifest = str_replace('{NODE_OWNER}', $uid, $manifest);
        $manifest = str_replace('{NODE_OWNERNAME}', elecoa_session_get_username(), $manifest);
        $manifest = str_replace('{NODE_CREATED_AT}', date('Y-m-d H:i:s'), $manifest);
        $platform = Platform::getInstance();
        $obj = $platform->appendByManifest($this, $manifest);
        $result = $this->getParent()->callFromChild($this->getID(), 'CHOICE', $obj->getID(), NULL);
        if ($result['Result']) {
            return $result;
        } else {
            return $this->getParent()->callFromChild($this->getID(), 'CHOICE', $id, NULL);
        }
    }
}
