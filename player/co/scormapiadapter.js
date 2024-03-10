/**
 * Coreオブジェクトメソッドのオーバーライド。
 * Coreオブジェクトが初期化された時に呼ばれる。
 */
top.Core.onInitialized = function() {
    install_scorm_error_handler();    // scormerrorhandler.js
};


/**
 * Coreオブジェクトメソッドのオーバーライド。
 * コンテンツが読み込まれる前に呼ばれる。
 * @param {Object} sender 送信オブジェクト
 * @param {Object} results INITRTMした場合の結果
 */
top.Core.onBeforeLoadContent = function(sender, results) {
    top.ScormAPIAdapter = new ScormAPIAdapterClass();

    if (typeof results['initrtm_result'] != 'undefined') {
        top.ScormAPIAdapter.saveINITRTMResult(decodeURIComponent(results['initrtm_result']));
    }
};



/**
 * APIアダプタオブジェクト。
 * scormapi.js と container.js の間の橋渡しをすることと、SCORM処理に必要な調整を行なう。
 */
var ScormAPIAdapter = null;

var ScormAPIAdapterClass = (function() {
    /**
     * コンストラクタ。
     */
    function ScormAPIAdapterClass() {
        // doNavigationCommandをオーバーライド
        if (typeof top.Frameset.doNavigationCommand == 'function') {
            top.Frameset.doNavigationCommand = function(command) {
                top.Frameset.hideNavigationInterface();
                
                if (top.contentType == 'SCORMSco') {
                    if (top.ScormAPIAdapter.getTerminated()) {
                        var results = top.Core.sendCommand(command);
                        if (results.result) {
                            top.Frameset._handleCommandResults(results);
                        }
                    } else {
                        // コマンドを保存しておく
                        top.ScormAPIAdapter.saveCommand(command);

                        // コンテンツ領域をブランクにする（Terminateを呼ばせるため）
                        top.Frameset.unloadContent();
                    }
                }
                else {
                    // SCORMSco以外はそのままコマンドを実行する
                    var results = top.Core.sendCommand(command);
                    top.Frameset._handleCommandResults(results);
                }
                if (results && !results.action.type) {
                    // シーケンス命令なのにNextIDがない場合
                    if (results.command.match(/^(CONTINUE$|PREVIOUS$)/)) {
                        setTimeout(function() {
                            top.Frameset.exitContent();
                        }, 0);
                        return;
                    }
                    if (results.command.match(/^CHOICE&VAL=.+$/)) {
                        setTimeout(function() {
                            top.Core.handleError({
                                code: 'NB.2.1-11'
                            });
                        }, 0);
                        return;
                    }
                }
            };
        }
    }
    
    
    ScormAPIAdapterClass.prototype = {
        /**
         * Terminateが実行されたかどうか。
         */
        _terminated: false, 
        /**
         * 保存されたコマンド。
         */
        _savedCommand: '', 
        /**
         * 保存されたINITRTMの実行結果。
         */
        _savedINITRTMResult: '', 
        
        
        /**
         * 初期化関数を返す。
         * @return function 初期化関数
         */
        getInitializationFunction: function(){
            return function(args){
            };
        }, 
        
        
        /**
         * Terminateが実行されたことをセットする。
         */
        setTerminated: function() {
            this._terminated = true;
        }, 
        
        
        /**
         * Terminateが実行されかどうかを返す。
         */
        getTerminated: function() {
            return this._terminated;
        }, 
        
        
        /**
         * コマンドを保存する。
         */
        saveCommand: function(command) {
            this._savedCommand = command;
        }, 
        
        
        /**
         * INITRTMの結果を保存する。
         * @param {Object} result
         */
        saveINITRTMResult: function(result) {
            this._savedINITRTMResult = result;
        }, 
        
        
        /**
         * ユーザーIDを返す。
         */
        getUserID: function() {
            return top.userID;
        }, 
        
        
        /**
         * ユーザー名を返す。
         */
        getUserName: function() {
            return top.userName;
        }, 
        
        
        /**
         * コマンドを実行する。
         * @param string command
         * @param string data
         * @return string コマンド結果文字列
         */
        doCommand: function(command, data) {
            // INITRTMを実行済みの場合、その結果を返す
            if ((command == 'INITRTM') && (this._savedINITRTMResult != '')) {
                var result = this._savedINITRTMResult;
                this._savedINITRTMResult = '';
                return result;
            }
            var results = top.Core.sendCommand(command, data);
            return results.commandResult;
        }, 
        
        
        /**
         * ナビゲーションコマンドを実行する。
         * @param string command ナビゲーションコマンド文字列
         */
        doNavigationCommand: function(command) {
            var results = top.Core.sendCommand(command);
            top.Frameset._handleCommandResults(results);
        },
        
        
        /**
         * 保存されていたナビゲーションコマンドを実行する。
         */
        doSavedNavigationCommand: function() {
            if (this._savedCommand != '') {
                var __savedCommand = this._savedCommand;
                setTimeout(function() {
                    var results = top.Core.sendCommand(__savedCommand);
                    top.Frameset._handleCommandResults(results);
                }, 0);
                this._savedCommand = '';
            }
        }, 
        
        
        /**
         * adl.nav.request_valid.～ の値を返す。
         * @param string element 取得するデータモデル名。
         * @return string 結果文字列。
         */
        getRequestValid: function(element) {
            switch (element) {
                case 'continue':
                    var results = top.Core.sendCommand('REQUEST_VALID&VAL=CONTINUE');
                    if ('result' in results.commandResultArray) {
                        return results.commandResultArray['result'];
                    }
                    break;
                    
                case 'previous':
                    var results = top.Core.sendCommand('REQUEST_VALID&VAL=PREVIOUS');
                    if ('result' in results.commandResultArray) {
                        return results.commandResultArray['result'];
                    }
                    break;
                    
                default:
                    if (element.match(/^choice\.\{target=(.+)\}$/)) {
                        var results = top.Core.sendCommand('REQUEST_VALID&VAL=CHOICE.' + RegExp.$1);
                        if ('result' in results.commandResultArray) {
                            return results.commandResultArray['result'];
                        }
                    }
                    break;
            }
            
            return 'unknown';
        }
    };
    
    return ScormAPIAdapterClass;
})();
