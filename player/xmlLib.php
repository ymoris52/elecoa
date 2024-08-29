<?php

class SerializableNode
{
    private $nodeData;

    function __construct (DOMNode $node=null) {
        if ($node != null) {
            $this->nodeData = $this->nodeToArray($node);
        }
    }

    public function __get($name) {
        if ($name === 'nodeValue') {
            if (!isset($this->nodeData['_value'])) {
                $trace = debug_backtrace();
                trigger_error(
                    '_value is not set: ' .
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
            }
            return $this->nodeData['_value'];
        }
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function getAttribute($name) {
        $_attributes = $this->nodeData['_attributes'];
        if (isset($_attributes[$name])) {
            return $_attributes[$name];
        } else {
            return '';
        }
    }

    public static function createFromData($nodeData) {
        $instance = new SerializableNode();
        $instance->nodeData = $nodeData;
        return $instance;
    }

    public function childNodes() {
        $array = [];
        foreach ($this->nodeData['_children'] as $child) {
            $array[] = SerializableNode::createFromData($child);
        }
        return $array;
    }

    public function nodeName() {
        return $this->nodeData['_name'];
    }

    private function nodeToArray(DOMNode $node) {
        $array = [];

        $array['_name'] = $node->nodeName;
    
        $array['_attributes'] = [];
        if ($node->hasAttributes()) {
            $attributes = $node->attributes;
            foreach ($attributes as $attr) {
                $array['_attributes'][$attr->nodeName] = $attr->nodeValue;
            }
        }

        $childIndex = 0;
        $array['_children'] = [];
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $childArray = $this->nodeToArray($child);

                if ($child->hasChildNodes() && $child->childNodes->length === 1 && $child->firstChild->nodeType === XML_TEXT_NODE) {
                    $childArray['_value'] = $child->firstChild->nodeValue;
                } else {
                    $childArray['_value'] = '';
                }

                $array['_children'][$childIndex++] = $childArray;
            }
        }

        return $array;
    }
}

function selectNodes($node, $name) {
    $array = [];
    foreach ($node->childNodes() as $childNode) {
        if ($childNode->nodeName() === $name) {
            $array[] = $childNode;
        }
    }
    return $array;
}

function selectSingleNode($node, $name) {
    foreach ($node->childNodes() as $childNode) {
        if ($childNode->nodeName() === $name) {
            return $childNode;
        }
    }
    return null;
}

function selectDOMNodes($node, $path) {
    if (is_null($node)) {
        $trace = debug_backtrace();
        trigger_error(
            '$node is null ' . $path .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
    }
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

function selectSingleDOMNode($node, $path) {
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
