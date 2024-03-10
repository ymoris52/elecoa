<?php

    class BaseAPIAdapterProvider {
        /**
         * アダプタ名を返す。
         * @return string アダプタ名
         */
        public function getAdapterName() {
            return 'Base';
        }

        /**
         * アダプタコンテンツ情報を返す
         * @return array 'version':バージョン情報、'javascripts':スクリプト情報、'stylesheets':スタイルシート情報、'apiobjects':APIオブジェクト情報
         */
        public function getCDObjects() {
            return array(
                'version' => '1',
                'javascripts' => array(),
                'stylesheets' => array(),
                'apiobjects'  => array()
            );
        }

        /**
         * ファイルコンテンツを返す。
         * @param $name APIオブジェクト名
         * @param $src ファイル名
         * @param $is_mobile モバイルかどうか
         */
        public function getContent($name = null, $src = null, $is_mobile = FALSE) {
            $dirname = dirname(__FILE__);
            $adaptername = $this->getAdapterName();
            $contents = $this->getCDObjects();
            if (!is_null($name)) {
                $apiobject = null;
                foreach ($contents['apiobjects'] as $obj) {
                    if ($obj['name'] === $name) {
                        $apiobject = $obj;
                        break;
                    }
                }
                if (!is_null($apiobject)) {
                    if (is_null($src)) {
                        $content = '<!DOCTYPE html>';
                        $content .= '<html>';
                        $content .=  '<head>';
                        $content .=   '<meta charset="utf-8">';
                        foreach ($apiobject['parts'] as $part) {
                            $version_param = array_key_exists('version', $contents) ? '&amp;' . urlencode($contents['version']) : '';
                            $content .= '<script src="./api.php?type=' . urlencode($adaptername) . '&amp;name=' . urlencode($name) . '&amp;src=' . urlencode($part['src']) . $version_param . '"></script>';
                        }
                        $content .=  '</head>';
                        $content .=  '<body></body>';
                        $content .= '</html>';
                        return $content;
                    } else {
                        $partsfile = array();
                        foreach ($apiobject['parts'] as $part) {
                            $partsfile[] = $part['src'];
                        }
                        if (preg_match('/\.(js)$/', $src) && in_array($src, $partsfile)) {
                            if ($is_mobile && file_exists($dirname . '/m/' . basename($src))) {
                                return file_get_contents($dirname . '/m/' . basename($src));
                            } else {
                                return file_get_contents($dirname . '/' . basename($src));
                            }
                        } else {
                            return FALSE;
                        }
                    }
                } else {
                    return FALSE;
                }
            }
            else if (preg_match('/\.(js|css)$/', $src) && (in_array($src, $contents['javascripts']) || in_array($src, $contents['stylesheets']))) {
                if ($is_mobile && file_exists($dirname . '/m/' . basename($src))) {
                    return file_get_contents($dirname . '/m/' . basename($src));
                } else {
                    return file_get_contents($dirname . '/' . basename($src));
                }
            } else {
                return FALSE;
            }
        }
    }
