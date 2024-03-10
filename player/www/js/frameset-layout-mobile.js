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
                $('#ELECOA_NAV').animate({width: 24}, 300, function() {
                    $('#ELECOA_NAV').removeClass('nav-open').addClass('nav-close');
                    $('#ELECOA_SEPARATOR').removeClass('nav-open').addClass('nav-close');

                    document.cookie = 'elecoa-nav-openclose=close;';

                    // レイアウトの調整
                    top.FramesetLayout.adjustLayout();
                });
                // show main_element
                $('#ELECOA_MAINCONTAINER').css({'position': 'static', 'top': 0});
                $('#ELECOA_MAINCONTAINER').animate({marginLeft: 0}, 300);
            }
            else {
                var maincontainer_width = main_element.get(0).clientWidth;
                $('#ELECOA_NAV').animate({width: maincontainer_width}, 300, function() {
                    $('#ELECOA_NAV').removeClass('nav-close').addClass('nav-open');
                    $('#ELECOA_SEPARATOR').removeClass('nav-close').addClass('nav-open');

                    document.cookie = 'elecoa-nav-openclose=open;';

                    // レイアウトの調整
                    top.FramesetLayout.adjustLayout();
                });
                $('#ELECOA_MAINCONTAINER').animate({marginLeft: (maincontainer_width + 40)}, 300);
                // hide main_element
                $('#ELECOA_MAINCONTAINER').css({'position': 'absolute', 'top': -9999});
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
            // エレメントの取得
            var main_element = $('#ELECOA_MAIN').get(0);
            var nav_element = $('#ELECOA_NAV').get(0);
            var separator_element = $('#ELECOA_SEPARATOR').get(0);
            
            // 幅を固定
            main_element.style.width = '100vw';
            if (main_element.clientWidth > 0) {
                main_element.style.width = main_element.clientWidth + 'px';
            }
            
            // adjust nav width
            if (nav_element.classList.contains('nav-open')) {
                nav_element.style.width = main_element.clientWidth + 'px';
                $('#ELECOA_MAINCONTAINER').css('left', (main_element.clientWidth + 40) + 'px');
            }
            var frame_height = main_element.clientHeight;
            var frame_width = main_element.clientWidth;
            
            // ELECOA_MAINのコンテンツの高さと幅を取得
            var content_element = null;
            try {
                content_element = (typeof window.ELECOA_MAIN.document.body == 'undefined') ? window.ELECOA_MAIN.document.documentElement : window.ELECOA_MAIN.document.body;
            } catch (e) {}
            if (!content_element) {
                return;
            }
            var content_height = content_element.scrollHeight;
            var content_width  = content_element.scrollWidth;
            
            // ELECOA_NAVの高さを取得
            if (nav_element.offsetHeight > content_height) {
                content_height = nav_element.offsetHeight;
            }
            
            // ELECOA_MAINとELECOA_SEPARATORの高さと幅を設定
            if (frame_width < content_width) {
                content_element.style.zoom = frame_width / content_width;
            }
            separator_element.style.height = '0px';
            
            // タイマーで幅と高さの変化を監視する
            if (window.timerID) {
                clearTimeout(window.timerID);
                window.timerID = undefined;
            }
            window.interval = 0;
            var adjust_function = function() {
                window.interval = (window.interval > 40) ? 40 : window.interval + 1;
                try {
                    var content_element = (typeof top.ELECOA_MAIN.document.body == 'undefined') ? top.ELECOA_MAIN.document.documentElement : top.ELECOA_MAIN.document.body;
                    var main_element = $('#ELECOA_MAIN').get(0);
                    
                    // adjust nav width
                    if ($('#ELECOA_NAV').hasClass('nav-open')) {
                        $('#ELECOA_NAV').get(0).style.width = main_element.clientWidth + 'px';
                        $('#ELECOA_MAINCONTAINER').get(0).style.left = (main_element.clientWidth + 40) + 'px';
                    }
                    
                    content_width = content_element.scrollWidth;
                    var zoom = main_element.clientWidth / content_width;
                    content_element.style.zoom = zoom;
                    if (content_element.scrollWidth > main_element.clientWidth) {
                        content_element.style.zoom = 0.99 * (main_element.clientWidth / content_element.scrollWidth);
                    }
                    
                    content_height = content_element.scrollHeight;
                    
                    if (main_element.clientHeight < content_height) {
                        main_element.style.height = content_height + 'px';
                        separator_element.style.height = content_height + 'px';
                    }
                }
                catch (e) {
                }
                finally {
                    if (window.timerID) {
                        clearTimeout(window.timerID);
                    }
                    window.timerID = setTimeout(adjust_function, window.interval * 25);
                }
            };
            window.timerID = setTimeout(adjust_function, 25);
        }
    };

    return ElecoaFramesetLayout;
})();
