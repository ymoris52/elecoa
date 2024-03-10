<?php
    require_once dirname(__FILE__) . '/BaseAPIAdapterProvider.php';
    
    class CMI5APIAdapterProvider extends BaseAPIAdapterProvider {
        /**
         * アダプタ名を返す。
         * @return string アダプタ名
         */
        public function getAdapterName() {
            return 'CMI5';
        }

        /**
         * アダプタコンテンツ情報を返す
         * @return array 'version':バージョン情報、'javascripts':スクリプト情報、'stylesheets':スタイルシート情報、'apiobjects':APIオブジェクト情報
         */
        public function getCDObjects() {
            return array(
                'version' => '201802010008',
                'javascripts' => array(
                    'cmi5apiadapter.js'
                ),
                'stylesheets' => array(),
                'apiobjects'  => array()
            );
        }
    }
