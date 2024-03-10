/**
 * コア処理
 */
var ElecoaCore = (function() {
    /**
     * コンストラクタ。
     */
    function ElecoaCore() {
    }
    
    
    ElecoaCore.prototype = {
        /**
         * アクションタイプ定数
         */
        ACTION_CLOSE: 'CLOSE',
        ACTION_MOVE: 'MOVE',
        
        
        /**
         * エラーハンドラ。
         * @param {Object} error
         */
        handleError: function(error) {
            if (typeof error == 'string') {
                error = {
                    code: undefined, 
                    message: error
                };
            }
            if ((typeof error == 'undefined') || (typeof error.code == 'undefined') && (typeof error.message == 'undefined')) {
                alert('An unexpected error occurred');
            }
            else {
                alert(((typeof error.code == 'undefined') ? '' : error.code + ': ') + ((typeof error.message == 'undefined') ? '' : error.message));
            }
        }, 
        
        
        /**
         * データを送信して結果をそのまま返す。
         * @param string params クエリパラメータ
         * @param string data POSTデータ
         * @return string 結果文字列
         */
        communicateData: function(params, data) {
            var xmlHttp = null;
            if (window.XMLHttpRequest) {
                xmlHttp = new XMLHttpRequest();
            }
            else {
                xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            var result = "";
            try {
                xmlHttp.open("POST", "mainmodule.php?CID=" + encodeURIComponent(top.content_id) + "&" + params, false);
                xmlHttp.send(data);
                result = xmlHttp.responseText;
            } catch (e) {
                setTimeout(function() {
                    xmlHttp.open("POST", "mainmodule.php?CID=" + encodeURIComponent(top.content_id) + "&" + params, false);
                    xmlHttp.send(data);
                }, 0);
                var timer = setInterval(function() {
                    result = xmlHttp.responseText;
                    if (result != "") {
                        clearInterval(timer);
                    }
                }, 100);
            }
            return result;
        }, 
        
        
        /**
         * 結果文字列を配列に格納する。
         * @param 結果文字列 result_string
         * @return 結果配列
         */
        _makeArray: function(result_string){
            var lines = result_string.split("\n");
            var result_array = new Array();
            for (var i = 0; i < lines.length; i++) {
                if (lines[i] == '') {
                    continue;
                }
                var equal_position = lines[i].indexOf("=");
                if (equal_position < 0) {
                    continue;
                }
                
                var key = lines[i].substring(0, equal_position);
                var value = lines[i].substring(equal_position + 1);
                result_array[key] = value;
            }
            return result_array;
        }, 
        
        
        /**
         * コマンドを送信する。
         * @param string command
         * @param string data
         */
        sendCommand: function(command, data) {
            if (typeof data == 'undefined') {
                data = null;
            }
            
            var results = {
                command:command,
                data:data,
                result:false,
                action:{
                    type: null
                },
                commandResult:null,
                commandResultArray: null
            };
            
            results.commandResult = this.communicateData("CMD=" + command, data);
            results.commandResultArray = this._makeArray(results.commandResult);
            if (results.commandResultArray['result'] != 'true') {
                return results;
            }
            
            results.result = true;
            
            // Close命令ありの場合
            if (results.commandResultArray['close'] == 'true') {
                results.action = {
                    type: this.ACTION_CLOSE
                };
                return results;
            }
            
            // NextIDありの場合
            if (results.commandResultArray['NextID'] != '') {
                results.action = {
                    type: this.ACTION_MOVE,
                    to: results.commandResultArray['NextID']
                };
                return results;
            }
            
            return results;
        }, 
        
        
        /**
         * Coreオブジェクトが初期化された後に呼ばれます。
         * アダプターで必要な処理がある場合、このメソッドをオーバーライドします。
         */
        onInitialized: function() {
        }, 
        
        
        /**
         * コンテンツが読み込まれる前に呼ばれます。
         * アダプターで必要な処理がある場合、このメソッドをオーバーライドします。
         * @param sender
         * @param results
         */
        onBeforeLoadContent: function(sender, results) {
        },

        /**
         * ユーザーIDとユーザー名を返します。
         */
        getLearnerInfo: function(){
          return {"UserID": top.userID, "UserName": top.userName};
        }

    };
    
    return ElecoaCore;
})();
