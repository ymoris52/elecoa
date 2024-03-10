/**
 * レイアウト処理オブジェクト
 */
ElecoaFramesetLayout = (function() {
    /**
     * コンストラクタ。
     */
    function ElecoaFramesetLayout(config) {
        //レイアウト調整用のタイマーID
        this.timerID = undefined;
        // エレメントを取得
        var nav_element = $('#ELECOA_NAV');
        var main_element = $('#ELECOA_MAINCONTAINER');
        var separator_element = $('#ELECOA_SEPARATOR');

        // ナビ領域の初期幅を取得
        var current_nav_width = nav_element.get(0).offsetWidth;
        if (nav_element.hasClass('nav-close')) {
            current_nav_width = 200;
        }

        // クローズボックスのクリックイベントバインド
        $('#elecoa-nav-closebox').click(function() {
            if (nav_element.hasClass('nav-open')) {
                $('#ELECOA_NAV').animate({width: 16}, 300, function() {
                    $('#ELECOA_NAV').removeClass('nav-open').addClass('nav-close');
                    $('#ELECOA_SEPARATOR').removeClass('nav-open').addClass('nav-close');
                    // セパレータのドラッグ停止
                    $('#ELECOA_SEPARATOR').draggable("disable");

                    document.cookie = 'elecoa-nav-openclose=close;';

                    // レイアウトの調整
                    top.FramesetLayout.adjustLayout();
                });
                $('#ELECOA_MAINCONTAINER').animate({marginLeft: 36}, 300);
            }
            else {
                $('#ELECOA_NAV').animate({width: current_nav_width}, 300, function() {
                    $('#ELECOA_NAV').removeClass('nav-close').addClass('nav-open');
                    $('#ELECOA_SEPARATOR').removeClass('nav-close').addClass('nav-open');
                    // セパレータのドラッグ再開
                    $('#ELECOA_SEPARATOR').draggable("enable");

                    document.cookie = 'elecoa-nav-openclose=open;';

                    // レイアウトの調整
                    top.FramesetLayout.adjustLayout();
                });
                $('#ELECOA_MAINCONTAINER').animate({marginLeft: (current_nav_width + 20)}, 300);
            }
        });

        var start_x;
        $('#ELECOA_SEPARATOR').draggable({ axis: "x",
            helper: function(e) {
                return $('<div class="SEPARATOR"></div>')
            },
            start: function(e) {
                start_x = e.pageX;
            },
            stop: function(e) {
                // 新しい幅の取得
                var delta = e.pageX - start_x;

                var nav_width = $('#ELECOA_NAV').get(0).offsetWidth + delta;
                if (nav_width < 100) {
                    nav_width = 100;
                }
                var main_width = $('#ELECOA_MAINCONTAINER').get(0).offsetWidth - delta;
                if (main_width < 400) {
                    nav_width = nav_width - (400 - main_width);
                }
                if (nav_width < 100) {
                    return;
                }

                // 新しい幅の設定
                $('#ELECOA_NAV').css('width', nav_width + 'px');
                $('#ELECOA_MAINCONTAINER').css('margin-left', (nav_width + 20) + 'px');

                current_nav_width = nav_width;
                document.cookie = 'elecoa-nav-width=' + current_nav_width + ';';
            }
        });
        // ウィンドウのonresizeイベントでレイアウト調整
        this.resizetimer = false;
        $(window).resize(function() {
            if (this.resizetimer !== false) {
                clearTimeout(this.resizetimer);
            }
            this.resizetimer = setTimeout(function() {
                // レイアウトの調整
                top.FramesetLayout.adjustLayout();
            }, 200);
        });
    }

    ElecoaFramesetLayout.prototype = {
        /**
         * レイアウトを調整する。
         */
        adjustLayout: function() {
            // 定数宣言
            var API_HEIGHT = 40;
            
            // エレメントの取得
            var main_element = $('#ELECOA_MAIN').get(0);
            var nav_element = $('#ELECOA_NAV').get(0);
            var separator_element = $('#ELECOA_SEPARATOR').get(0);
            
            // 幅を初期化して、フレームサイズを取得
            main_element.style.width = '99%';
            var frame_height = main_element.offsetHeight;
            var frame_width = main_element.offsetWidth;
            
            // ELECOA_MAINのコンテンツの高さと幅を取得
            // firefox: Firefox / msie: Internet Explorer / msie10p: Internet Explorer 10, 11, Microsoft Edge
            var firefox = (navigator.userAgent.indexOf('Firefox/') != -1);
            var msie10p = (navigator.userAgent.indexOf('MSIE 10.') != -1 || navigator.userAgent.indexOf('Trident/7.') != -1 || navigator.userAgent.indexOf('Edge/') != -1);
            var msie = msie10p || (navigator.userAgent.indexOf('MSIE ') != -1);
            var content_element = msie10p ? main_element.contentDocument.body : (msie ? main_element.contentWindow.document.body : (typeof window.ELECOA_MAIN.document.body == 'undefined') ? window.ELECOA_MAIN.document.documentElement : window.ELECOA_MAIN.document.body);
            if (!content_element) {
                return;
            }
            var content_height = firefox ? content_element.parentNode.scrollHeight : content_element.scrollHeight;
            var content_width  = firefox ? content_element.parentNode.scrollWidth : content_element.scrollWidth;
            
            if (msie10p) {
                // IE10+用の調整
                content_height = Math.max(main_element.contentDocument.body.scrollHeight, main_element.contentDocument.documentElement.scrollHeight);
            }
            else if (msie) {
                // IE用の調整
                content_height = Math.max(main_element.contentWindow.document.body.scrollHeight, main_element.contentWindow.document.documentElement.scrollHeight);
            }
            
            // ELECOA_NAVの高さを取得
            if (nav_element.offsetHeight > content_height + API_HEIGHT) {
                content_height = nav_element.offsetHeight - API_HEIGHT;
            }
            
            // ELECOA_MAINとELECOA_SEPARATORの高さと幅を設定
            if (frame_height < content_height) {
                main_element.style.height  = (content_height + 20) + 'px';
                separator_element.style.height = (content_height + 20 + API_HEIGHT) + 'px';
            }
            if (frame_width < content_width) {
                main_element.style.width = (content_width + 20) + 'px';
            }
            
            // タイマーで幅と高さの変化を監視する
            if (this.timerID) {
                clearInterval(this.timerID);
                this.timerID = undefined;
            }
            this.timerID = setInterval(function() {
                try {
                    content_element = msie ? main_element.contentWindow.document.body : (typeof window.ELECOA_MAIN.document.body == 'undefined') ? window.ELECOA_MAIN.document.documentElement : window.ELECOA_MAIN.document.body;
                    content_height = firefox ? content_element.parentNode.scrollHeight : content_element.scrollHeight;
                    if (msie) {
                      // IE用の調整
                      content_height = Math.max(main_element.contentWindow.document.body.scrollHeight, main_element.contentWindow.document.documentElement.scrollHeight);
                    }
                    if (main_element.offsetHeight < content_height) {
                        main_element.style.height = (content_height + 20) + 'px';
                        separator_element.style.height = (content_height + 20 + API_HEIGHT) + 'px';
                    }

                    content_width = firefox ? content_element.parentNode.scrollWidth : content_element.scrollWidth;

                    if (main_element.offsetWidth < content_width) {
                        main_element.style.width = (content_width + 20) + 'px';
                    }
                }
                catch (e) {
                    if (this.timerID) {
                        clearInterval(this.timerID);
                        this.timerID = undefined;
                    }
                }
            }, 1000);
        }
    };

    return ElecoaFramesetLayout;
})();
