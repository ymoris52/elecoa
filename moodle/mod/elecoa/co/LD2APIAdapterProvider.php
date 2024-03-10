<?php
    require_once dirname(__FILE__) . '/BaseAPIAdapterProvider.php';
    
    class LD2APIAdapterProvider extends BaseAPIAdapterProvider {
        /**
         * アダプタ名を返す。
         * @return string アダプタ名
         */
        public function getAdapterName() {
            return 'LD2';
        }

        /**
         * アダプタコンテンツ情報を返す
         * @return array 'version':バージョン情報、'javascripts':スクリプト情報、'stylesheets':スタイルシート情報、'apiobjects':APIオブジェクト情報
         */
        public function getCDObjects() {
            return array(
                'version' => '201602010001',
                'javascripts' => array(
                    'ld2apiadapter.js'
                ),
                'stylesheets' => array(),
                'apiobjects'  => array()
            );
        }
    }
