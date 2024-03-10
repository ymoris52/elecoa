<?php

require_once(dirname(__FILE__) . '/xmlLib.php');

class cmi5_manifest_converter {
    private $cmi5_doc;
    private $elecoa_doc;
    private $nodeCounter;

    public function get_redirector_html()
    {
        return implode("\n", array(
            '<!DOCTYPE html>',
            '<html>',
            '  <head>',
            '    <meta charset="utf-8">',
            '  </head>',
            '  <body>',
            '    <script>',
            '      var url = top.CMI5AdapterAPI.GetValue("launcher.url");',
            '      (function() { top.location.href = url })();',
            '    </script>',
            '  </body>',
            '</html>'));    
    }

    public function convert_xml($cmi5_xml, &$elecoa_xml) {
        $this->cmi5_doc = new DOMDocument();
        if (!($this->cmi5_doc->loadXML($cmi5_xml))) {
            return "Can't read XML";
        }

        $this->elecoa_doc = new DOMDocument('1.0', 'utf-8');
        $this->elecoa_doc->preserveWhiteSpace = FALSE;
        $this->elecoa_doc->formatOutput = TRUE;
        $this->elecoa_doc->xmlStandalone = FALSE;

        $elecoa_manifest = $this->elecoa_doc->createElement('manifest');
        $elecoa_manifest->setAttribute('xmlns', 'http://elecoa.ouj.ac.jp/ns/elecoa/1.0');
        $this->elecoa_doc->appendChild($elecoa_manifest);

        //
        // convert cmi5.xml to elecoa manifest
        //
        if ($this->cmi5_doc->documentElement->nodeName !== 'courseStructure') {
            return "Invalid cmi5.xml: courseStructure node is not found.\n";
        }
        $this->nodeCounter = 1;
        $this->convert_courseStructure($this->cmi5_doc->documentElement, $this->elecoa_doc->documentElement);

        $elecoa_xml = $this->elecoa_doc->saveXML();
    }

    private function convert_courseStructure($courseStructure, $elecoa_manifest) {
        $course = selectSingleNode($courseStructure, 'course');

        // item
        $elecoa_item = $this->elecoa_doc->createElement('item');
        $elecoa_item->setAttribute('identifier', 'item' . $this->nodeCounter++);
        $elecoa_item->setAttribute('coType', 'CMI5Course');
        $elecoa_item->setAttribute('oGS', 'true');
        $elecoa_item->setAttribute('start', '');
        $elecoa_item->setAttribute('ownwindow', 'true');
        $elecoa_item->setAttribute('showtoc', 'true');
        $elecoa_manifest->appendChild($elecoa_item);
        
        // title
        $elecoa_title = $this->elecoa_doc->createElement('title');
        $title = selectSingleNode($course, 'title');
        $langstringNodes = selectNodes($title, 'langstring');
        $elecoa_title_content = '';
        foreach ($langstringNodes as $langstring) {
            $lang = $langstring->getAttribute('lang');
            if ($lang === 'en-US') {
                $elecoa_title_content = trim($langstring->nodeValue);
            }
        }
        $elecoa_title->nodeValue = $elecoa_title_content;
        $elecoa_item->appendChild($elecoa_title);

        // itemData
        $elecoa_itemData = $this->elecoa_doc->createElement('itemData');
        $elecoa_item->appendChild($elecoa_itemData);

        // children
        $children = $courseStructure->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
            $node = $children->item($i);
            if ($node->nodeType == XML_ELEMENT_NODE and $node->nodeName === "au") {
                $this->convert_au($node, $elecoa_item);
            }
            if ($node->nodeType == XML_ELEMENT_NODE and $node->nodeName === "block") {
                $this->convert_block($node, $elecoa_item);
            }
        }
    }

    private function convert_au($au, $elecoa_parent) {
        // item
        $elecoa_item = $this->elecoa_doc->createElement('item');
        $elecoa_item->setAttribute('identifier', 'item' . $this->nodeCounter++);
        $elecoa_item->setAttribute('coType', 'CMI5AU');
        $elecoa_item->setAttribute('href', 'redirector.html');
        $elecoa_parent->appendChild($elecoa_item);

        // title
        $elecoa_title = $this->elecoa_doc->createElement('title');
        $title = selectSingleNode($au, 'title');
        $langstringNodes = selectNodes($title, 'langstring');
        $elecoa_title_content = '';
        foreach ($langstringNodes as $langstring) {
            $lang = $langstring->getAttribute('lang');
            if ($lang === 'en-US') {
                $elecoa_title_content = trim($langstring->nodeValue);
            }
        }
        $elecoa_title->nodeValue = $elecoa_title_content;
        $elecoa_item->appendChild($elecoa_title);

        // itemData
        $elecoa_itemData = $this->elecoa_doc->createElement('itemData');
        $elecoa_item->appendChild($elecoa_itemData);

        // launcher
        $elecoa_launcher = $this->elecoa_doc->createElement('launcher');
        $elecoa_itemData->appendChild($elecoa_launcher);

        // auId
        $elecoa_launcher->setAttribute('auId', $au->getAttribute('id'));

        // baseUrl
        $url = selectSingleNode($au, 'url');
        $elecoa_launcher->setAttribute('baseUrl', trim($url->nodeValue));

        // launchMethod
        $elecoa_launchMethod = 'AnyWindow';
        $launchMethod = $au->getAttribute('launchMethod');
        if (!empty($launchMethod)) {
            $elecoa_launchMethod = $launchMethod;
        }
        $elecoa_launcher->setAttribute('launchMethod', $elecoa_launchMethod);

        // moveOn
        $elecoa_moveOn = 'NotApplicable';
        $moveOn = $au->getAttribute('moveOn');
        if (!empty($moveOn)) {
            $elecoa_moveOn = $moveOn;
        }
        $elecoa_launcher->setAttribute('moveOn', $elecoa_moveOn);

        // masteryScore
        $elecoa_masteryScore = null;
        $masteryScore = $au->getAttribute('masteryScore');
        if (strlen($masteryScore) !== 0) {
            $elecoa_masteryScore = $masteryScore;
        }
        if (!is_null($elecoa_masteryScore)) {
            $elecoa_launcher->setAttribute('masteryScore', $elecoa_masteryScore);
        }

        // launchParameters
        $launchParameters = selectSingleNode($au, 'launchParameters');
        if (!is_null($launchParameters)) {
            $elecoa_launchParameters = $this->elecoa_doc->createElement('launchParameters');
            $elecoa_launchParameters->nodeValue = trim($launchParameters->nodeValue);
            $elecoa_launcher->appendChild($elecoa_launchParameters);
        }

        // entitlementKey
        $entitlementKey = selectSingleNode($au, 'entitlementKey');
        if (!is_null($entitlementKey)) {
            $elecoa_entitlementKey = $this->elecoa_doc->createElement('entitlementKey');
            $elecoa_entitlementKey->nodeValue = trim($entitlementKey->nodeValue);
            $elecoa_launcher->appendChild($elecoa_entitlementKey);
        }
    }

    private function convert_block($block, $elecoa_parent) {
        // item
        $elecoa_item = $this->elecoa_doc->createElement('item');
        $elecoa_item->setAttribute('identifier', 'item' . $this->nodeCounter++);
        $elecoa_item->setAttribute('coType', 'CMI5Block');
        $elecoa_parent->appendChild($elecoa_item);

        // title
        $elecoa_title = $this->elecoa_doc->createElement('title');
        $title = selectSingleNode($block, 'title');
        $langstringNodes = selectNodes($title, 'langstring');
        $elecoa_title_content = '';
        foreach ($langstringNodes as $langstring) {
            $lang = $langstring->getAttribute('lang');
            if ($lang === 'en-US') {
                $elecoa_title_content = trim($langstring->nodeValue);
            }
        }
        $elecoa_title->nodeValue = $elecoa_title_content;
        $elecoa_item->appendChild($elecoa_title);

        // itemData
        $elecoa_itemData = $this->elecoa_doc->createElement('itemData');
        $elecoa_item->appendChild($elecoa_itemData);

        // children
        $children = $block->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
            $node = $children->item($i);
            if ($node->nodeType == XML_ELEMENT_NODE and $node->nodeName === "au") {
                $this->convert_au($node, $elecoa_item);
            }
            if ($node->nodeType == XML_ELEMENT_NODE and $node->nodeName === "block") {
                $this->convert_block($node, $elecoa_item);
            }
        }
    }
}
