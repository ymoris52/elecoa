<?php

require_once(dirname(__FILE__) . '/xmlLib.php');

class elecoa_manifest_converter
{
    private $ContentID;
    private $isSCORM;
    private $firstItem;

    private $CO;

    // $M_... : imsmanifest.xml (入力)
    private $M_doc;

    // $E_... : elecoa.xml (出力)
    private $E_doc;
    private $E_manifest;

    private $urlAry;    // URL
    private $typeAry;    // adlcp:scormType
    private $objAry;    // targetObjectiveID
    private $objAryRSS;
    private $objAryRNM;
    private $objAryWSS;
    private $objAryWNM;
    private $firstLeaf;

    public function elecoa_manfest_converter()
    {
    }

    public function convert_file($ContentID, $isSCORM, $firstItem, $M_file, $E_file)
    {
        // imsmanifest.xml の読み込み
        $this->M_doc = new DOMDocument();
        if (!($this->M_doc->load($M_file))) {
            return "Can't read " . $M_file;
        }

        $error = $this->convert($ContentID, $isSCORM, $firstItem);

        if (!$error) {
            // elecoa.xml を出力
            $this->E_doc -> save($E_file);
        }

        return $error;
    }

    public function convert_xml($ContentID, $isSCORM, $firstItem, $M_xml, &$E_xml)
    {
        // imsmanifest.xml の読み込み
        $this->M_doc = new DOMDocument();
        if (!($this->M_doc->loadXML($M_xml))) {
            return "Can't read XML";
        }

        $error = $this->convert($ContentID, $isSCORM, $firstItem);

        if (!$error) {
            // elecoa.xml を出力
            $E_xml = $this->E_doc -> saveXML();
        }

        return $error;
    }

    public function convert($ContentID, $isSCORM, $firstItem)
    {
        $this->ContentID = $ContentID;
        $this->isSCORM = $isSCORM;
        $this->firstItem = $firstItem;
        $this->CO = $isSCORM ? array('SCORMRoot', 'SCORMBlock', 'SCORMSco', 'SCORMAsset') : array('SimpleRoot', 'SimpleBlock', 'SimpleLeaf', 'SimpleLeaf');

        $this->E_doc = new DOMDocument('1.0', 'utf-8');
        $this->E_doc -> preserveWhiteSpace = FALSE;
        $this->E_doc -> formatOutput = TRUE;
        $this->E_doc -> xmlStandalone = FALSE;

        $this->E_manifest = $this->E_doc -> createElement('manifest');
        $this->E_manifest -> setAttribute('xmlns', 'http://elecoa.ouj.ac.jp/ns/elecoa/1.0');
        $this->E_doc -> appendChild($this->E_manifest);


        // グローバル変数
        $this->urlAry = array();  // URL
        $this->typeAry = array(); // adlcp:scormType
        $this->objAry = array();  // targetObjectiveID
        $this->objAryRSS = array();
        $this->objAryRNM = array();
        $this->objAryWSS = array();
        $this->objAryWNM = array();

        $this->firstLeaf = '';

        // <resources>
        if (is_null($M_resources = selectSingleNode($this->M_doc -> documentElement, 'resources'))) {
            return "Invalid imsmanifest.xml";
        }

        // Schema version get
        $schemaversion = '1.2';
        $metadataNode = selectSingleNode($this->M_doc->documentElement, 'metadata');
        if ($metadataNode) {
            $schemaversionNode = selectSingleNode($metadataNode, 'schemaversion');
            if ($schemaversionNode) {
                $schemaversion = trim($schemaversionNode->nodeValue);
            }
        }

        foreach (selectNodes($M_resources, 'resource') as $n) {
            $url = $n -> getAttribute('href');
            if ($url === '') {
                continue;
            }
            $id = $n -> getAttribute('identifier');
            $this->urlAry[$id] = $n -> getAttribute('xml:base') . $url;
            $this->typeAry[$id] = ($schemaversion === '1.2') ? $n->getAttribute('adlcp:scormtype') : $n->getAttribute('adlcp:scormType');
        }

        // <organizations>
        if (is_null($M_organizations = selectSingleNode($this->M_doc -> documentElement, 'organizations'))) {
            return "Invalid imsmanifest.xml";
        }

        $M_Org = selectSingleNode($M_organizations, 'organization');

        // <organization> が無い場合の対応 (SCORM2004では必須だが1.2では任意)
        if ( is_null($M_Org) ) {
            // <organizations>に<organization>を追加
            $E_organization = $this->M_doc -> createElement('organization');
            $E_organization -> setAttribute('identifier', $this->ContentID );
            $E_title = $this->M_doc -> createElement('title');
            $E_title -> nodeValue = $this->ContentID;
            $E_organization -> appendChild($E_title);
            foreach (selectNodes($M_resources, 'resource') as $n) {
                // すべての<resource>への参照を<organization>の<item>として追加
                $url = $n -> getAttribute('href');
                if ($url === '') {
                    continue;
                }
                $E_item = $this->M_doc -> createElement('item');
                $E_item -> setAttribute('identifier', $n->getAttribute('identifier') );
                $E_item -> setAttribute('isvisible', 'true');
                $E_item -> setAttribute('identifierref', $n->getAttribute('identifier'));
                $E_title = $this->M_doc -> createElement('title');
                $E_title -> nodeValue = $n->getAttribute('identifier');
                $E_item -> appendChild($E_title);
                $E_organization -> appendChild($E_item);
            }
            $M_organizations -> appendChild($E_organization);
            $M_organizations -> setAttribute('default', 'default_organization' );

            $M_Org = selectSingleNode($M_organizations, 'organization');
        }

        $this->procChkOrganization($M_Org); // 学習目標の洗い出し
        $this->objAry = array_unique($this->objAry);
        $this->procOrganization(selectSingleNode($M_organizations, 'organization'), $schemaversion); // 複数の構造を持つ場合の処理がぬけている

        // 学習目標
        if ($this->isSCORM) {
            $E_objectives = $this->E_doc -> createElement('objectives');
            foreach ($this->objAry as $o) {
                $E_o = $this->E_doc -> createElement('objective');
                $E_o -> setAttribute('id', $o);
                $E_o -> setAttribute('coType', 'SCORMObjective');
/*
                if(array_key_exists($o,$this->objAryRSS)){
                    foreach ($this->objAryRSS[$o] as $val) {
                        $E_r = $this->E_doc -> createElement('readSatisfiedStatusActivity');
                        $E_r->setAttribute('idref',$val);
                        $E_o -> appendChild($E_r);
                    }
                }
                if(array_key_exists($o,$this->objAryRNM)){
                    foreach ($this->objAryRNM[$o] as $val) {
                        $E_r = $this->E_doc -> createElement('readNormalizedMeasureActivity');
                        $E_r->setAttribute('idref',$val);
                        $E_o -> appendChild($E_r);
                    }
                }
                if(array_key_exists($o,$this->objAryWSS)){
                    foreach ($this->objAryWSS[$o] as $val) {
                        $E_r = $this->E_doc -> createElement('writeSatisfiedStatusActivity');
                        $E_r->setAttribute('idref',$val);
                        $E_o -> appendChild($E_r);
                    }
                }
                if(array_key_exists($o,$this->objAryWNM)){
                    foreach ($this->objAryWNM[$o] as $val) {
                        $E_r = $this->E_doc -> createElement('writeNormalizedMeasureActivity');
                        $E_r->setAttribute('idref',$val);
                        $E_o -> appendChild($E_r);
                    }
                }
*/
                $E_objectives -> appendChild($E_o);
            }
            $this->E_manifest -> appendChild($E_objectives);
        }

        return null;
    }

    function procChkOrganization($M_o) {
        foreach (selectNodes($M_o, 'item') as $n) {
            $this->ChkItemNode($n);
        }
        if ($this->isSCORM) {
            $this->ChkSeqNode(selectSingleNode($M_o, 'imsss:sequencing'));
        }
    }

    function ChkItemNode($M_i) {
        $M_i -> getAttribute('identifierref') === '' ? $this->ChkBlockNode($M_i) : $this->ChkLeafNode($M_i);
    }

    // Block Node の生成
    function ChkBlockNode($M_i) {
        foreach (selectNodes($M_i, 'item') as $i) {
            $this->ChkItemNode($i);
        }

        if ($this->isSCORM) {
            $this->ChkSeqNode(selectSingleNode($M_i, 'imsss:sequencing'));
        }
    }

    // Leaf Node の生成
    function ChkLeafNode($M_i) {
        if ($this->isSCORM) {
            $this->ChkSeqNode(selectSingleNode($M_i, 'imsss:sequencing'));
        }
    }

    function ChkSeqNode($M_s) {
        if (is_null($M_s)) {
            return;
        }

        $nodes = array();
        foreach ($M_s -> childNodes as $n) {
            if ($n -> nodeType === XML_ELEMENT_NODE) {
                $nodes[$n -> nodeName] = $n;
            }
        }
        if (isset($nodes['imsss:objectives'])) {
            $this->ChkSeqObj($nodes['imsss:objectives']);
        }
    }

    function ChkSeqObj($M_n) {
        if (!is_null($M_po = selectSingleNode($M_n, 'imsss:primaryObjective'))) {
            foreach (selectNodes($M_po, 'imsss:mapInfo') as $n) {
                $tmpStr = $n -> getAttribute('targetObjectiveID');
                $this->objAry[] = $tmpStr;
            }
        }
        // ローカル
        foreach (selectNodes($M_n, 'imsss:objective') as $n) {
            foreach (selectNodes($n, 'imsss:mapInfo') as $nn) {
                $tmpStr = $nn -> getAttribute('targetObjectiveID');
                $this->objAry[] = $tmpStr;
            }
        }
    }


    function procOrganization($M_o, $schemaversion) {
        $E_o = $this->E_doc -> createElement('item');
        $id = $M_o -> getAttribute('identifier');
        $E_o -> setAttribute('identifier', $id);        // identifier
        $E_o -> setAttribute('coType', $this->CO[0]);                                        // coType

        $E_o -> setAttribute('oGS', $M_o -> getAttribute('adlseq:objectivesGlobalToSystem') === 'false' ? 'false' : 'true');        // oGS

        $E_t = $this->E_doc -> createElement('title');        // title
        $E_t -> nodeValue = selectSingleNode($M_o, 'title') -> nodeValue;
        $E_o -> appendChild($E_t);

        $E_ID = $this->E_doc -> createElement('itemData');

        if ($this->isSCORM) {
            $E_ID -> setAttribute('xmlns:imsss', "http://www.imsglobal.org/xsd/imsss");
            $E_ID -> setAttribute('xmlns:adlseq', "http://www.adlnet.org/xsd/adlseq_v1p3");
            $E_ID -> setAttribute('xmlns:adlnav', "http://www.adlnet.org/xsd/adlnav_v1p3");
            $E_ID -> appendChild($this->makeSeqNode(selectSingleNode($M_o, 'imsss:sequencing'), $id, $schemaversion));
        }
        $E_o -> appendChild($E_ID);

        foreach (selectNodes($M_o, 'item') as $n) {
            $E_o -> appendChild($this->makeItemNode($n, $schemaversion));
        }

        $this->E_manifest -> appendChild($E_o);
        // startID

        if($this->firstItem != ""){
            $E_o -> setAttribute('startID', $this->firstItem);
        }else if($this->firstLeaf !=""){
            if ($this->isSCORM) {
                $E_o -> setAttribute('startID', '');
            }else{
                $E_o -> setAttribute('startID', $this->firstLeaf);
            }
        }
    }

    function makeItemNode($M_i, $schemaversion) {
        return $M_i -> getAttribute('identifierref') === '' ? $this->makeBlockNode($M_i, $schemaversion) : $this->makeLeafNode($M_i, $schemaversion);
    }

    // Block Node の生成
    function makeBlockNode($M_i, $schemaversion) {
        $E_i = $this->E_doc -> createElement('item');
        $id = $M_i -> getAttribute('identifier');
        $E_i -> setAttribute('identifier', $id);
        $E_i -> setAttribute('coType', $this->CO[1]);

        $E_t = $this->E_doc -> createElement('title');
        $E_t -> nodeValue = selectSingleNode($M_i, 'title') -> nodeValue;
        $E_i -> appendChild($E_t);

        $E_ID = $this->E_doc -> createElement('itemData');

        if ($this->isSCORM) {
            $E_ID -> setAttribute('xmlns:imsss', "http://www.imsglobal.org/xsd/imsss");
            $E_ID -> setAttribute('xmlns:adlcp', "http://www.adlnet.org/xsd/adlcp_v1p3");// itemの下のみ
            $E_ID -> setAttribute('xmlns:adlseq', "http://www.adlnet.org/xsd/adlseq_v1p3");

            $M_ct = selectSingleNode($M_i, 'adlcp:completionThreshold');
            if (!is_null($M_ct)) {
                $E_ct = $this->E_doc -> createElement('adlcp:completionThreshold');
                $E_ct -> nodeValue = $M_ct -> nodeValue;
                $E_ID -> appendChild($E_ct);
            }

            $E_ID -> appendChild($this->makeSeqNode(selectSingleNode($M_i, 'imsss:sequencing'), $id, $schemaversion));
            
            $this->handle_maxtimeallowed($E_ID, $M_i);
            $this->handle_masteryscore($E_ID, $M_i);
        }
        $E_i -> appendChild($E_ID);

        // 子 Node の生成
        foreach (selectNodes($M_i, 'item') as $i) {
            $E_i -> appendChild($this->makeItemNode($i, $schemaversion));
        }

        return $E_i;
    }

    // Leaf Node の生成
    function makeLeafNode($M_i, $schemaversion) {
        $E_i = $this->E_doc -> createElement('item');
        $id = $M_i -> getAttribute('identifier');
        $E_i -> setAttribute('identifier', $id);

        if ($this->firstLeaf === '') {
            $this->firstLeaf = $id;
        }

        $idref = $M_i -> getAttribute('identifierref');
        $E_i -> setAttribute('href', $this->urlAry[$idref] . $M_i -> getAttribute('parameters'));
        $E_i -> setAttribute('coType', $this->typeAry[$idref] === 'sco' ? $this->CO[2] : $this->CO[3]);

        $E_t = $this->E_doc -> createElement('title');
        $E_t -> nodeValue = selectSingleNode($M_i, 'title') -> nodeValue;
        $E_i -> appendChild($E_t);

        $E_ID = $this->E_doc -> createElement('itemData');

        if ($this->isSCORM) {
            $E_ID -> setAttribute('xmlns:imsss', "http://www.imsglobal.org/xsd/imsss");
            $E_ID -> setAttribute('xmlns:adlcp', "http://www.adlnet.org/xsd/adlcp_v1p3");// itemの下のみ
            $E_ID -> setAttribute('xmlns:adlseq', "http://www.adlnet.org/xsd/adlseq_v1p3");
            $E_ID -> setAttribute('xmlns:adlnav', "http://www.adlnet.org/xsd/adlnav_v1p3");

            $this->handle_dataFromLMS($E_ID, $M_i);

            $M_ct = selectSingleNode($M_i, 'adlcp:completionThreshold');
            if (!is_null($M_ct)) {
                $E_ct = $this->E_doc -> createElement('adlcp:completionThreshold');
                $E_ct -> nodeValue = $M_ct -> nodeValue;
                $E_ID -> appendChild($E_ct);
            }

            $this->handle_timeLimitAction($E_ID, $M_i);

            $E_ID -> appendChild($this->makeSeqNode(selectSingleNode($M_i, 'imsss:sequencing'), $id, $schemaversion));
            
            $this->handle_presentation($E_ID, $M_i);
            $this->handle_maxtimeallowed($E_ID, $M_i);
            $this->handle_masteryscore($E_ID, $M_i);
        }
        $E_i -> appendChild($E_ID);

        return $E_i;
    }

    function makeSeqNode($M_s, $tmpID, $schemaversion) {
        $E_s = $this->makeDefaultSeqNode($schemaversion);

        if (is_null($M_s)) {
            return $E_s;
        }

        $nodes = array();
        foreach ($M_s -> childNodes as $n) {
            if ($n -> nodeType === XML_ELEMENT_NODE) {
                $nodes[$n -> nodeName] = $n;
            }
        }

        if (isset($nodes['imsss:controlMode'])) {
            $this->setSeqControl($E_s, $nodes['imsss:controlMode']);
        }

        if (isset($nodes['imsss:sequencingRules'])) {
            $this->setSeqRules($E_s, $nodes['imsss:sequencingRules']);
        }

        if (isset($nodes['imsss:limitConditions'])) {
            $this->setSeqLimit($E_s, $nodes['imsss:limitConditions']);
        }

        if (isset($nodes['imsss:rollupRules'])) {
            $this->setSeqRoll($E_s, $nodes['imsss:rollupRules']);
        }

        if (isset($nodes['imsss:objectives'])) {
            $this->setSeqObj($E_s, $nodes['imsss:objectives'],$tmpID);
        }

        if (isset($nodes['imsss:deliveryControls'])) {
            $this->setSeqDC($E_s, $nodes['imsss:deliveryControls']);
        }

        if (isset($nodes['adlseq:constrainedChoiceConsiderations'])) {
            $this->setSeqAC($E_s, $nodes['adlseq:constrainedChoiceConsiderations']);
        }

        if (isset($nodes['adlseq:rollupConsiderations'])) {
            $this->setSeqAR($E_s, $nodes['adlseq:rollupConsiderations']);
        }

        return $E_s;
    }

    function makeDefaultSeqNode($schemaversion) {
        $E_s = $this->E_doc -> createElement('imsss:sequencing');
        // シーケンシング制御モード
        $E_c = $this->E_doc -> createElement('imsss:controlMode');
        $E_c -> setAttribute('choice', 'true');
        $E_c -> setAttribute('choiceExit', 'true');
        $E_c -> setAttribute('flow', ((trim($schemaversion) === '1.2') ? 'true' : 'false'));
        $E_c -> setAttribute('forwardOnly', 'false');
        $E_c -> setAttribute('useCurrentAttemptObjectiveInfo', 'true');
        $E_c -> setAttribute('useCurrentAttemptProgressInfo', 'true');
        $E_s -> appendChild($E_c);

        // ロールアップルール
        $E_r = $this->E_doc -> createElement('imsss:rollupRules');
        $E_r -> setAttribute('rollupObjectiveSatisfied', 'true');
        $E_r -> setAttribute('rollupProgressCompletion', 'true');
        $E_r -> setAttribute('objectiveMeasureWeight', '1.000');
        $E_s -> appendChild($E_r);

        // 学習目標
        $E_o = $this->E_doc -> createElement('imsss:objectives');
        $E_po = $this->E_doc -> createElement('imsss:primaryObjective');
        $E_po -> setAttribute('satisfiedByMeasure', 'false');
        $E_po -> setAttribute('objectiveID', '');
        $E_mn = $this->E_doc -> createElement('imsss:minNormalizedMeasure');
        $E_mn -> nodeValue = '1.0';
        $E_po -> appendChild($E_mn);
        $E_o -> appendChild($E_po);
        $E_s -> appendChild($E_o);

        // DE
        $E_d = $this->E_doc -> createElement('imsss:deliveryControls');
        $E_d -> setAttribute('tracked', 'true');
        $E_d -> setAttribute('completionSetByContent', 'false');
        $E_d -> setAttribute('objectiveSetByContent', 'false');
        $E_s -> appendChild($E_d);

        $E_ac = $this->E_doc -> createElement('adlseq:constrainedChoiceConsiderations');
        $E_ac -> setAttribute('preventActivation', 'false');
        $E_ac -> setAttribute('constrainChoice', 'false');
        $E_s -> appendChild($E_ac);

        $E_ar = $this->E_doc -> createElement('adlseq:rollupConsiderations');
        $E_ar -> setAttribute('requiredForSatisfied', 'always');
        $E_ar -> setAttribute('requiredForNotSatisfied', 'always');
        $E_ar -> setAttribute('requiredForCompleted', 'always');
        $E_ar -> setAttribute('requiredForIncomplete', 'always');
        $E_ar -> setAttribute('measureSatisfactionIfActive', 'true');
        $E_s -> appendChild($E_ar);
        return $E_s;
    }

    function setSeqControl(&$E_s, $M_n) {
        $E_n = selectSingleNode($E_s, 'imsss:controlMode');
        if ($M_n -> getAttribute('choice') === 'false') {
            $E_n -> setAttribute('choice', 'false');
        }
        if ($M_n -> getAttribute('choiceExit') === 'false') {
            $E_n -> setAttribute('choiceExit', 'false');
        }
        if ($M_n -> getAttribute('flow') === 'true') {
            $E_n -> setAttribute('flow', 'true');
        }
        if ($M_n -> getAttribute('forwardOnly') === 'true') {
            $E_n -> setAttribute('forwardOnly', 'true');
        }
        if ($M_n -> getAttribute('useCurrentAttemptObjectiveInfo') === 'false') {
            $E_n -> setAttribute('useCurrentAttemptObjectiveInfo', 'false');
        }
        if ($M_n -> getAttribute('useCurrentAttemptProgressInfo') === 'false') {
            $E_n -> setAttribute('useCurrentAttemptProgressInfo', 'false');
        }
    }

    function setSeqRules(&$E_s, $M_n) {
        $CD = array('imsss:preConditionRule','imsss:exitConditionRule','imsss:postConditionRule');
        $len = count($CD);
        $E_R = $this->E_doc -> createElement('imsss:sequencingRules');

        for($i=0;$i<$len;$i++){
            foreach (selectNodes($M_n, $CD[$i]) as $n) {
                $E_RC = $this->E_doc -> createElement($CD[$i]);
                $M_RR = selectSingleNode($n, 'imsss:ruleConditions');// 空はない
                $E_RCS = $this->E_doc -> createElement('imsss:ruleConditions');
                if ($M_RR -> getAttribute('conditionCombination') === 'any') {
                    $E_RCS -> setAttribute('conditionCombination', 'any');
                }else{
                    $E_RCS -> setAttribute('conditionCombination', 'all');
                }

                foreach (selectNodes($M_RR, 'imsss:ruleCondition') as $nn) {
                    $E_RR = $this->E_doc -> createElement('imsss:ruleCondition');
                    $tmpStr = $nn -> getAttribute('referencedObjective');
                    if($tmpStr == '' ||is_null($tmpStr)){
                        $tmpStr = '';
                    }
                    $E_RR->setAttribute('referencedObjective', $tmpStr);
                    $tmpStr = $nn -> getAttribute('measureThreshold');
                    if($tmpStr == '' ||is_null($tmpStr)){
                        $tmpStr = '';
                    }
                    $E_RR->setAttribute('measureThreshold', $tmpStr);
                    $tmpStr = $nn -> getAttribute('operator');
                    if($tmpStr == '' ||is_null($tmpStr)){
                        $tmpStr = 'noOp';
                    }
                    $E_RR->setAttribute('operator', $tmpStr);
                    $tmpStr = $nn -> getAttribute('condition');
                    $E_RR->setAttribute('condition', $tmpStr);
                    $E_RCS->appendChild($E_RR);
                }

                $M_RA = selectSingleNode($n, 'imsss:ruleAction');// 空はない
                $tmpStr = $M_RA->getAttribute('action');
                $E_RA = $this->E_doc -> createElement('imsss:ruleAction');
                $E_RA->setAttribute('action', $tmpStr);

                $E_RC -> appendChild($E_RCS);
                $E_RC -> appendChild($E_RA);
                $E_R -> appendChild($E_RC);
            }
        }
        $E_s -> appendChild($E_R);
    }

    function setSeqLimit(&$E_s, $M_n) {
        $E_R = $this->E_doc -> createElement('imsss:limitConditions');
        $tmpStr = $M_n -> getAttribute('attemptLimit');
        $E_R->setAttribute('attemptLimit', $tmpStr);
        $tmpStr = $M_n -> getAttribute('attemptAbsoluteDurationLimit');
        $E_R->setAttribute('attemptAbsoluteDurationLimit', $tmpStr);
        $E_s -> appendChild($E_R);
    }

    function setSeqRoll(&$E_s, $M_n) {
        $E_R = selectSingleNode($E_s, 'imsss:rollupRules');

        $tmpStr = $M_n -> getAttribute('rollupObjectiveSatisfied');
        if($tmpStr == 'false'){
            $E_R->setAttribute('rollupObjectiveSatisfied', 'false');
        }else{
            $E_R->setAttribute('rollupObjectiveSatisfied', 'true');
        }
        $tmpStr = $M_n -> getAttribute('rollupProgressCompletion');
        if($tmpStr == 'false'){
            $E_R->setAttribute('rollupProgressCompletion', 'false');
        }else{
            $E_R->setAttribute('rollupProgressCompletion', 'true');
        }
        $tmpStr = $M_n -> getAttribute('objectiveMeasureWeight');
        if($tmpStr == ''){
            $E_R->setAttribute('objectiveMeasureWeight', '1.0000');
        }else{
            $E_R->setAttribute('objectiveMeasureWeight', $tmpStr);
        }

        foreach (selectNodes($M_n, 'imsss:rollupRule') as $n) {
            $E_RR = $this->E_doc -> createElement('imsss:rollupRule');
            $tmpStr = $n -> getAttribute('childActivitySet');
            if($tmpStr == ''){
                $E_RR->setAttribute('childActivitySet', 'all');
            }else{
                $E_RR->setAttribute('childActivitySet', $tmpStr);
            }

            $tmpStr = $n -> getAttribute('minimumCount');
            if($tmpStr == ''){
                $E_RR->setAttribute('minimumCount', '0');
            }else{
                $E_RR->setAttribute('minimumCount', $tmpStr);
            }

            $tmpStr = $n -> getAttribute('minimumPercent');
            if($tmpStr == ''){
                $E_RR->setAttribute('minimumPercent', '0.0000');
            }else{
                $E_RR -> setAttribute('minimumPercent', $tmpStr);
            }

            $M_cs = selectSingleNode($n, 'imsss:rollupConditions');
            $E_cs = $this->E_doc -> createElement('imsss:rollupConditions');
            $tmpStr = $M_cs -> getAttribute('conditionCombination');
            if($tmpStr == 'all'){
                $E_cs -> setAttribute('conditionCombination', 'all');
            }else{
                $E_cs -> setAttribute('conditionCombination', 'any');
            }

            foreach (selectNodes($M_cs, 'imsss:rollupCondition') as $nn) {
                $E_c = $this->E_doc -> createElement('imsss:rollupCondition');
                $tmpStr = $nn -> getAttribute('operator');
                if($tmpStr == 'not'){
                    $E_c -> setAttribute('operator', 'not');
                }else{
                    $E_c -> setAttribute('operator', 'noOp');
                }
                $tmpStr = $nn -> getAttribute('condition');
                $E_c -> setAttribute('condition', $tmpStr);
                $E_cs -> appendChild($E_c);
            }
            $E_RR -> appendChild($E_cs);

            $M_a = selectSingleNode($n, 'imsss:rollupAction');
            $E_a = $this->E_doc -> createElement('imsss:rollupAction');
            $tmpStr = $M_a -> getAttribute('action');
            $E_a -> setAttribute('action', $tmpStr);

            $E_RR -> appendChild($E_a);
            $E_R -> appendChild($E_RR);
        }
        $E_s -> appendChild($E_R);
    }

    function setSeqObj(&$E_s, $M_n,$tmpID) {
        $M_po = selectSingleNode($M_n, 'imsss:primaryObjective');
        $E_R = selectSingleNode($E_s, 'imsss:objectives');
        if (!is_null($M_po)) {
            $E_po = selectSingleNode($E_R, 'imsss:primaryObjective');

            $tmpStr = $M_po -> getAttribute('objectiveID');
            if ($tmpStr !== '') {
                $E_po -> setAttribute('objectiveID', $tmpStr);
            }

            $tmpStr = $M_po -> getAttribute('satisfiedByMeasure');
            if ($tmpStr === 'true') {
                $E_po -> setAttribute('satisfiedByMeasure', 'true');
            }

            if (!is_null($M_mn = selectSingleNode($M_po, 'imsss:minNormalizedMeasure'))) {
                selectSingleNode($E_po, 'imsss:minNormalizedMeasure') -> nodeValue = $M_mn -> nodeValue;
            }

            foreach (selectNodes($M_po, 'imsss:mapInfo') as $n) {
                $E_mi = $this->E_doc -> createElement('imsss:mapInfo');
                $tmpStr = $n -> getAttribute('targetObjectiveID');
                $E_mi -> setAttribute('targetObjectiveID', $tmpStr);

                $fRSS = $n -> getAttribute('readSatisfiedStatus') === 'false' ? 'false' : 'true';
                $E_mi -> setAttribute('readSatisfiedStatus', $fRSS);
                if($fRSS === 'true'){
                    $this->objAryRSS[$tmpStr][] = $tmpID;
                }

                $fRNM = $n -> getAttribute('readNormalizedMeasure') === 'false' ? 'false' : 'true';
                $E_mi -> setAttribute('readNormalizedMeasure', $fRNM);
                if($fRNM === 'true'){
                    $this->objAryRNM[$tmpStr][] = $tmpID;
                }

                $fWSS = $n -> getAttribute('writeSatisfiedStatus') === 'true' ? 'true' : 'false';
                $E_mi -> setAttribute('writeSatisfiedStatus', $fWSS);
                if($fWSS === 'true'){
                    $this->objAryWSS[$tmpStr][] = $tmpID;
                }

                $fWNM = $n -> getAttribute('writeNormalizedMeasure') === 'true' ? 'true' : 'false';
                $E_mi -> setAttribute('writeNormalizedMeasure', $fWNM);
                if($fWNM === 'true'){
                    $this->objAryWNM[$tmpStr][] = $tmpID;
                }

                $E_po -> appendChild($E_mi);
            }
        }

        foreach (selectNodes($M_n, 'imsss:objective') as $n) {
            $E_o = $this->E_doc -> createElement('imsss:objective');
            $tmpStr = $n -> getAttribute('objectiveID');
            $E_o -> setAttribute('objectiveID', $tmpStr);

            $tmpStr = $n -> getAttribute('satisfiedByMeasure');
            if ($tmpStr === 'true') {
                $E_o -> setAttribute('satisfiedByMeasure', 'true');
            }else{
                $E_o -> setAttribute('satisfiedByMeasure', 'false');
            }

            $E_mn = $this->E_doc -> createElement('imsss:minNormalizedMeasure');
            $E_mn -> nodeValue = '1.0';

            $M_mn = selectSingleNode($n, 'imsss:minNormalizedMeasure');
            if (!is_null($M_mn)){
                $E_mn -> nodeValue = $M_mn -> nodeValue;
            }
            $E_o -> appendChild($E_mn);

            foreach (selectNodes($n, 'imsss:mapInfo') as $nn) {
                $E_mi = $this->E_doc -> createElement('imsss:mapInfo');
                $tmpStr = $nn -> getAttribute('targetObjectiveID');
                $E_mi -> setAttribute('targetObjectiveID', $tmpStr);

                $fRSS = $nn -> getAttribute('readSatisfiedStatus') === 'false' ? 'false' : 'true';
                $E_mi -> setAttribute('readSatisfiedStatus', $fRSS);
                if($fRSS === 'true'){
                    $this->objAryRSS[$tmpStr][] = $tmpID;
                }

                $fRNM = $nn -> getAttribute('readNormalizedMeasure') === 'false' ? 'false' : 'true';
                $E_mi -> setAttribute('readNormalizedMeasure', $fRNM);
                if($fRNM === 'true'){
                    $this->objAryRNM[$tmpStr][] = $tmpID;
                }

                $fWSS = $nn -> getAttribute('writeSatisfiedStatus') === 'true' ? 'true' : 'false';
                $E_mi -> setAttribute('writeSatisfiedStatus', $fWSS);
                if($fWSS === 'true'){
                    $this->objAryWSS[$tmpStr][] = $tmpID;
                }

                $fWNM = $nn -> getAttribute('writeNormalizedMeasure') === 'true' ? 'true' : 'false';
                $E_mi -> setAttribute('writeNormalizedMeasure', $fWNM);
                if($fWNM === 'true'){
                    $this->objAryWNM[$tmpStr][] = $tmpID;
                }

                $E_o -> appendChild($E_mi);
            }

            $E_R -> appendChild($E_o);
        }
    }

    function setSeqDC(&$E_s, $M_n) {
        $E_n = selectSingleNode($E_s, 'imsss:deliveryControls');
        if ($M_n -> getAttribute('tracked') === 'false'){
            $E_n -> setAttribute('tracked', 'false');
        }
        if ($M_n -> getAttribute('completionSetByContent') === 'true'){
            $E_n -> setAttribute('completionSetByContent', 'true');
        }
        if ($M_n -> getAttribute('objectiveSetByContent') === 'true'){
            $E_n -> setAttribute('objectiveSetByContent', 'true');
        }
    }

    function setSeqAC(&$E_s, $M_n) {
        $E_n = selectSingleNode($E_s, 'adlseq:constrainedChoiceConsiderations');
        if ($M_n -> getAttribute('preventActivation') === 'true'){
            $E_n -> setAttribute('preventActivation', 'true');
        }
        if ($M_n -> getAttribute('constrainChoice') === 'true'){
            $E_n -> setAttribute('constrainChoice', 'true');
        }

    }

    function setSeqAR(&$E_s, $M_n) {
        $E_n = selectSingleNode($E_s, 'adlseq:rollupConsiderations');
        if ($M_n -> getAttribute('requiredForSatisfied') !== ''){
            $E_n -> setAttribute('requiredForSatisfied', $M_n -> getAttribute('requiredForSatisfied'));
        }
        if ($M_n -> getAttribute('requiredForNotSatisfied') !== ''){
            $E_n -> setAttribute('requiredForNotSatisfied', $M_n -> getAttribute('requiredForNotSatisfied'));
        }
        if ($M_n -> getAttribute('requiredForCompleted') !== ''){
            $E_n -> setAttribute('requiredForCompleted', $M_n -> getAttribute('requiredForCompleted'));
        }
        if ($M_n -> getAttribute('requiredForIncomplete') !== ''){
            $E_n -> setAttribute('requiredForIncomplete', $M_n -> getAttribute('requiredForIncomplete'));
        }
        if ($M_n -> getAttribute('measureSatisfactionIfActive') === 'false'){
            $E_n -> setAttribute('measureSatisfactionIfActive', 'false');
        }
    }
    
    /**
     * adlcp:masteryscore を処理する。
     * 
     * @param DOMElement $E_ID <itemData>エレメント
     * @param DOMElement $M_i <item>エレメント
     */
    protected function handle_masteryscore(&$E_ID, $M_i) {
        $M_masteryscore = selectSingleNode($M_i, 'adlcp:masteryscore');
        if (is_null($M_masteryscore)) {
            return;
        }
        if (empty($M_masteryscore->nodeValue)) {
            return;
        }
        if (!is_numeric($M_masteryscore->nodeValue)) {
            return;
        }
        if ($M_masteryscore < 0 || 100 < $M_masteryscore) {
            return;
        }
        
        $E_sequencing = selectSingleNode($E_ID, 'imsss:sequencing');
        if (is_null($E_sequencing)) {
            $E_sequencing = $this->E_doc->createElement('imsss:sequencing');
            $E_ID->appendChild($E_sequencing);
            $E_sequencing = selectSingleNode($E_ID, 'imsss:sequencing');
        }
        
        $E_objectives = selectSingleNode($E_sequencing, 'imsss:objectives');
        if (is_null($E_objectives)) {
            $E_objectives = $this->E_doc->createElement('imsss:objectives');
            $E_sequencing->appendChild($E_objectives);
            $E_objectives = selectSingleNode($E_sequencing, 'imsss:objectives');
        }
        
        $E_primaryObjectives = selectSingleNode($E_objectives, 'imsss:primaryObjective');
        if (is_null($E_primaryObjectives)) {
            $E_primaryObjectives = $this->E_doc->createElement('imsss:primaryObjective');
            $E_objectives->appendChild($E_primaryObjectives);
            $E_primaryObjectives = selectSingleNode($E_objectives, 'imsss:primaryObjective');
        }
        $E_primaryObjectives->setAttribute('satisfiedByMeasure', 'true');
        
        $E_minNormalizedMeasure = selectSingleNode($E_primaryObjectives, 'imsss:minNormalizedMeasure');
        if (is_null($E_minNormalizedMeasure)) {
            $E_minNormalizedMeasure = $this->E_doc->createElement('imsss:minNormalizedMeasure');
            $E_primaryObjectives->appendChild($E_minNormalizedMeasure);
            $E_minNormalizedMeasure = selectSingleNode($E_primaryObjectives, 'imsss:minNormalizedMeasure');
        }
        $E_minNormalizedMeasure->nodeValue = round($M_masteryscore->nodeValue / 100.0, 2);
    }
    
    /**
     * adlcp:maxtimeallowed を処理する。
     * 
     * @param DOMElement $E_ID <itemData>エレメント
     * @param DOMElement $M_i <item>エレメント
     */
    protected function handle_maxtimeallowed(&$E_ID, $M_i) {
        $M_maxtimeallowed = selectSingleNode($M_i, 'adlcp:maxtimeallowed');
        if (is_null($M_maxtimeallowed)) {
            return;
        }
        if (empty($M_maxtimeallowed->nodeValue)) {
            return;
        }
        $duration = $this->convert_to_timeinterval_from_cmitimespan($M_maxtimeallowed->nodeValue);
        if (is_null($duration)) {
            return;
        }
        
        $E_sequencing = selectSingleNode($E_ID, 'imsss:sequencing');
        if (is_null($E_sequencing)) {
            $E_sequencing = $this->E_doc->createElement('imsss:sequencing');
            $E_ID->appendChild($E_sequencing);
            $E_sequencing = selectSingleNode($E_ID, 'imsss:sequencing');
        }
        
        $E_limitConditions = selectSingleNode($E_sequencing, 'imsss:limitConditions');
        if (is_null($E_limitConditions)) {
            $E_limitConditions = $this->E_doc->createElement('imsss:limitConditions');
            $E_sequencing->appendChild($E_limitConditions);
            $E_limitConditions = selectSingleNode($E_sequencing, 'imsss:limitConditions');
        }
        $E_limitConditions->setAttribute('attemptAbsoluteDurationLimit', $duration);
    }
    
    /**
     * adlcp:dataFromLMS を処理する。
     * 
     * @param DOMElement $E_ID <itemData>エレメント
     * @param DOMElement $M_i <item>エレメント
     */
    protected function handle_dataFromLMS(&$E_ID, $M_i) {
        $M_dataFromLMS = selectSingleNode($M_i, 'adlcp:dataFromLMS');
        if (is_null($M_dataFromLMS)) {
            $M_dataFromLMS = selectSingleNode($M_i, 'adlcp:datafromlms');
            if (is_null($M_dataFromLMS)) {
                return;
            }
        }
        
        $E_dataFromLMS = $this->E_doc->createElement('adlcp:dataFromLMS');
        $E_dataFromLMS->nodeValue = $M_dataFromLMS->nodeValue;
        $E_ID->appendChild($E_dataFromLMS);
        return;
    }
    
    /**
     * adlcp:timeLimitAction を処理する。
     * 
     * @param DOMElement $E_ID <itemData>エレメント
     * @param DOMElement $M_i <item>エレメント
     */
    protected function handle_timeLimitAction(&$E_ID, $M_i) {
        $M_timeLimitAction = selectSingleNode($M_i, 'adlcp:timeLimitAction');
        if (is_null($M_timeLimitAction)) {
            return;
        }
        
        $E_timeLimitAction = $this->E_doc->createElement('adlcp:timeLimitAction');
        $E_timeLimitAction->nodeValue = $M_timeLimitAction->nodeValue;
        $E_ID->appendChild($E_timeLimitAction);
        return;
    }
    
    /**
     * adlnav:presentation を処理する。
     * 
     * @param DOMElement $E_ID <itemData>エレメント
     * @param DOMElement $M_i <item>エレメント
     */
    protected function handle_presentation(&$E_ID, $M_i) {
        $M_presentation = selectSingleNode($M_i, 'adlnav:presentation');
        if (is_null($M_presentation)) {
            return;
        }
        
        $M_navigationInterface = selectSingleNode($M_presentation, 'adlnav:navigationInterface');
        if (is_null($M_navigationInterface)) {
            return;
        }
        
        $E_presentation = $this->E_doc->createElement('adlnav:presentation');
        $E_navigationInterface = $this->E_doc->createElement('adlnav:navigationInterface');
        
        foreach (selectNodes($M_navigationInterface, 'adlnav:hideLMSUI') as $M_hideLMSUI) {
            $E_hideLMSUI = $this->E_doc->createElement('adlnav:hideLMSUI');
            $E_hideLMSUI->nodeValue = $M_hideLMSUI->nodeValue;
            $E_navigationInterface->appendChild($E_hideLMSUI);
        }
        $E_presentation->appendChild($E_navigationInterface);
        $E_ID->appendChild($E_presentation);
        return;
    }
    
    /**
     * CMITimespan形式の文字列をtimeinterval形式に変換する。
     * 
     * @param string $cmi_timespan CMITimespan形式の文字列
     * @return string timeinterval形式の文字列。変換できない場合はnullを返す。
     */
    private function convert_to_timeinterval_from_cmitimespan($cmi_timespan) {
        if (!preg_match("/^([0-9]{2,4}):([0-9]{2}):([0-9]{2}(\\.[0-9]{1,2})?)$/", $cmi_timespan, $matches)) {
            return null;
        }
        
        $hours = '';
        if (!preg_match("/^0+$/", $matches[1])) {
            $hours = ltrim($matches[1], '0') . 'H';
        }
        
        $minutes = '';
        if (!preg_match("/^0+$/", $matches[2])) {
            $minutes = ltrim($matches[2], '0') . 'M';
        }
        
        $seconds = '';
        if (!preg_match("/^[0\\.]+$/", $matches[3])) {
            $seconds = ltrim($matches[3], '0') . 'S';
            if (substr($seconds, 0, 1) === '.') {
                $seconds = '0' . $seconds;
            }
        }
        
        $result = 'PT' . $hours . $minutes . $seconds;
        if ($result === 'PT') {
            return '';
        }
        
        return $result;
    }
}
