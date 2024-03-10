<?php
    require_once dirname(__FILE__) . '/BaseAPIAdapterProvider.php';
    
    class ScormAPIAdapterProvider extends BaseAPIAdapterProvider {
        /**
         * アダプタ名を返す。
         * @return string アダプタ名
         */
        public function getAdapterName() {
            return 'Scorm';
        }

        /**
         * アダプタコンテンツ情報を返す
         * @return array 'version':バージョン情報、'javascripts':スクリプト情報、'stylesheets':スタイルシート情報、'apiobjects':APIオブジェクト情報
         */
        public function getCDObjects() {
            return array(
                'version' => '20210830',
                'javascripts' => array(
                    'scormapiadapter.js',
                    'scormerrorhandler.js'
                ),
                'stylesheets' => array(),
                'apiobjects'  => array(
                    array(
                        'name' => 'API_1484_11',
                        'parts' => array(
                            array('src' => 'scormapi.js'),
                            // xpath-installer.js の中で js/javascript-xpath.js をインクルードして実行
                            // js の中で IE かどうかを判断し IE なら インクルードする
                            array('src' => 'xpath-installer.js'),
                        )
                    ),
                    array(
                        'name' => 'API',
                        'parts' => array(
                            array('src' => 'scorm1.2api.js'),
                        )
                    )
                )
            );
        }
    }
