/**
 * コアオブジェクト。
 * ElecoaCore
 */
var Core = new ElecoaCore();

/**
 * フレームセット基本オブジェクト。
 */
var Frameset = null;

/**
 * ナビゲーションボタンオブジェクト。
 * ElecoaNavigationButtons
 */
var NavigationButtons = null;

/**
 * 目次ツリービューオブジェクト。
 * ElecoaIndexTreeView
 */
var IndexTreeView = null;

/**
 * レイアウト処理オブジェクト。
 * ElecoaFramesetLayout
 */
var FramesetLayout = null;


var Container = (function() {
    /**
     * コンストラクタ。
     */
    function Container(args) {
        var containerObject = this;
        // ナビゲーションボタン操作オブジェクトの生成
        top.NavigationButtons = new ElecoaNavigationButtons({
            element_id: {
                PREVIOUS: 'link-nav-previous',
                CONTINUE: 'link-nav-continue',
                SUSPEND: 'link-nav-suspend',
                EXITALL: 'link-nav-exitall'
            }
        });

        // 目次ツリービューオブジェクトの生成
        top.IndexTreeView = new ElecoaIndexTreeview({
            treeview_element_id: 'ELECOA_INDEXTREEVIEW', 
            title_element_id: 'ELECOA_INDEXTITLE'
        });
    }

    Container.prototype = {
        /**
         * コンテンツを読み込む。
         * @param {Object} args
         */
        loadContent: function() {
            var targetDocument = top.ELECOA_MAIN;
            if (typeof targetDocument.location == 'undefined') {
                targetDocument = targetDocument.contentDocument;
            }
            targetDocument.location.href = top.baseUrl + '/pluginfile.php/' + top.contextID + '/' + encodeURIComponent(top.moduleName) + '/content/0/' + top.contentUrl;
        },

        /**
         * コンテンツ領域をブランクにする。
         * @param {Object} args
         */
        unloadContent: function() {
            var targetDocument = top.ELECOA_MAIN;
            if (typeof targetDocument.location == 'undefined') {
                targetDocument = targetDocument.contentDocument;
            }
            targetDocument.location.replace(top.baseUrl + '/mod/' + encodeURIComponent(top.modulePathName) + '/blank.html');
        },

        /**
         * メニューの初期化処理を実行する。
         * @param menuArgs 処理対象ボタン情報
         */
        renderMenu: function(menuArgs) {
            // showByParamsはボタン名一覧を引数に取るので、commandキーを配列に集める
            var params = [];
            for (var i in menuArgs) {
                var button = menuArgs[i];
                params.push(button.command);
            }
            top.NavigationButtons.showByParams(params);

            for (var i in menuArgs) {
                var button = menuArgs[i];
                if (!button.enabled) {
                    top.NavigationButtons.disableButton(button.command);
                }
            }
        },

        /**
         * 目次の初期化処理を実行する。
         * @param tocArgs 処理対象目次情報
         */
        renderToC: function(tocArgs) {
            top.IndexTreeView.output(tocArgs);
        },

        /**
         * インターフェースの初期化処理を実行する。
         * @param object args (doINITRTM:INITRTMも実行するか、onInterfaceInitialized:インターフェース初期化完了時に呼ぶコールバック関数）
         */
        initializeInterface: function(args) {
            var results = top.Core.sendCommand('GET_INTERFACE_DATA' + (args.doINITRTM ? '&VAL=WITH_INITRTM' : ''));
            if (typeof results.commandResultArray != 'undefined') {
                results = results.commandResultArray;
            }
            var button_param = (results['rtm_button_param'] ? results['rtm_button_param'].split(",") : []);

            // renderMenu/renderToC用引数を作成
            var menuArgs = [];
            for (var i in button_param) {
                var button = button_param[i];
                var menu = {"command": button, "enabled": true};

                if (results['prev'] == 'false' && button == "PREVIOUS") {
                    menu.enabled = false;
                }

                if (results['cont'] == 'false' && button == "CONTINUE") {
                    menu.enabled = false;
                }
                menuArgs.push(menu);
            }

            var tocArgs = eval('(' + results['index'] + ')');
            // コールバックを呼び出し
            top.Core.onRenderUIReady(menuArgs, tocArgs);

            // レイアウトを調整する
            top.FramesetLayout = new ElecoaFramesetLayout();
            top.FramesetLayout.adjustLayout();

            if (typeof args.onInterfaceInitialized == 'function') {
                args.onInterfaceInitialized(this, results);
            }
        },

        /**
         * コマンドの実行結果に応じた処理をする。
         * @param {Object} results
         */
        _handleCommandResults: function(results) {
            if (!results.result) {
                if (typeof results.commandResultArray['error'] != 'undefined') {
                    setTimeout(function(){
                        top.Core.handleError({
                            code: results.commandResultArray['error']
                        });
                    }, 0);
                } else {
                    setTimeout(function(){
                        top.Core.handleError();
                    }, 0);
                }
                this.showNavigationInterface();
                return;
            }

            if (results.action) {
                if (results.action.type == top.Core.ACTION_CLOSE) {
                    var document_element = top.document;
                    var dialog = document_element.createElement('div');
                    var dialog_onOK = function () {
                        top.Frameset.exitContent();
                    }
                    dialog.id = "dialog";
                    dialog.innerHTML = '<div class="msgbody">' + dialog_message + '</div><div class="msgfooter"><button>OK</button></div>';
                    $(dialog).on('click', 'button', function() {
                        $('#dialog').hide();
                        setTimeout(dialog_onOK, 0);
                    });
                    document_element.body.appendChild(dialog)
                    setTimeout(dialog_onOK, 500);
                    return;
                }
                else if (results.action.type == top.Core.ACTION_MOVE) {
                    var con = this;
                    setTimeout(function () {
                        con.moveTo(results.action.to);
                    }, 0);
                    return;
                }
            }
        },

        /**
         * ナビゲーションインターフェースを表示する。
         */
        showNavigationInterface: function() {
            top.NavigationButtons.showAll();
            top.IndexTreeView.show();
        },

        /**
         * ナビゲーションインターフェースを隠す。
         */
        hideNavigationInterface: function() {
            top.NavigationButtons.hideAll();
            top.IndexTreeView.hide();
        },

        /**
         * ナビゲーションコマンドを実行する。
         * @param string ナビゲーションコマンド文字列
         */
        doNavigationCommand: function(command) {
            this.hideNavigationInterface();

            var results = top.Core.sendCommand(command);
            this._handleCommandResults(results);
        },

        /**
         * 「次へ」処理を実行する。
         */
        goContinue: function() {
            this.doNavigationCommand('CONTINUE');
        },

        /**
         * 「前へ」処理を実行する。
         */
        goPrevious: function() {
            this.doNavigationCommand('PREVIOUS');
        },

        /**
         * 「中断」処理を実行する。
         */
        suspend: function() {
            this.doNavigationCommand('SUSPEND');
        },

        /**
         * 「終了」処理を実行する。
         */
        exitAll: function() {
            this.doNavigationCommand('EXITALL');
        },

        /**
         * 「選択」処理を実行する。
         * @param string nextID 移動するアクティビティID
         */
        choice: function(nextID) {
            this.doNavigationCommand('CHOICE&VAL=' + encodeURIComponent(nextID));
        },

        /**
         * トップ画面に移動する。
         */
        exitContent: function() {
            top.location.href = top.baseUrl + '/course/view.php?id=' + encodeURIComponent(top.cid);
        },

        /**
         * 指定されたIDの教材に移動する。
         * @param string id
         */
        moveTo: function(id) {
            top.location.href = top.baseUrl + '/mod/' + encodeURIComponent(top.modulePathName) + '/container.php?id=' + encodeURIComponent(top.cmid) + '&NextID=' + encodeURIComponent(id);
        },

        /**
         * エラー時用の「閉じる」リンクを挿入する。
         */
        insertCloseLink: function() {
            var document_element = top.ELECOA_MAIN.document || top.ELECOA_MAIN.contentDocument || top.ELECOA_MAIN.contentWindow.document;
            if (!document_element.getElementById('elecoa-error-close')) {
                var span = document_element.createElement('span');
                span.id = 'elecoa-error-close';
                span.innerHTML = '<a style="border:solid 1px #ccc;-webkit-border-radius:12px;border-radius:12px;font-size:12px;font-family:sans-serif;text-shadow:1px 1px 0 rgba(255, 255, 255, 0.6);color:#666;background-color:#ddd;display:inline-block;text-decoration:none;text-align:center;line-height:24px;width:24px;height:24px;cursor:default;" title="close" onclick="javascript:top.Frameset.exitAll();">x</a>';
                document_element.body.appendChild(span);
            }
        }
    };

    return Container;
})();

window.onload = function() {
    top.Frameset = new Container({});
    (function(frameset) {
        top.Core.onInitialized();

        frameset.initializeInterface({
            doINITRTM: true,
            onInterfaceInitialized: function(sender, results) {
                top.Core.onBeforeLoadContent(sender, results);

                frameset.loadContent();
            }
        })
    })(top.Frameset);
};

/**
 * initializeInterface用コールバック登録
 */
top.Core.onRenderUIReady = function(menuArgs, tocArgs) {
    top.Frameset.renderMenu(menuArgs);
    top.Frameset.renderToC(tocArgs);
};
