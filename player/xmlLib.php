<?php

function selectNodes($node, $path) {
    $retAry = array();
    $cList = $node -> childNodes;
    for ($i = 0; $i < $cList -> length; $i++) {
        $n = $cList -> item($i);
        // ノードのみ抽出
        if ($n -> nodeType == XML_ELEMENT_NODE and $n -> nodeName === $path) {
            $retAry[] = $n;
        }
    }
    return $retAry;
}

function selectSingleNode($node, $path) {
    $cList = $node -> childNodes;
    for ($i = 0; $i < $cList -> length; $i++) {
        $n = $cList -> item($i);
        // ノードのみ抽出
        if ($n -> nodeType == XML_ELEMENT_NODE and $n -> nodeName === $path) {
            return $n;
        }
    }
    return null;
}
