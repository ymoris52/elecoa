/**
 * Array.indexOfの定義（IE対策）
 */
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function(elt /*, from*/) {
        var len = this.length >>> 0;
        
        var from = Number(arguments[1]) || 0;
        from = (from < 0)
               ? Math.ceil(from)
               : Math.floor(from);
        if (from < 0)
            from += len;
        
        for (; from < len; from++) {
            if (from in this && this[from] === elt)
                return from;
        }
        return -1;
    };
}


/**
 * ナビゲーションボタン
 */
ElecoaNavigationButtons = (function() {

    /**
     * コンストラクタ
     */
    function ElecoaNavigationButtons(config) {
        this.buttons = {};
        if (config && config.element_id) {
            for (key in config.element_id) {
                this.buttons[key] = {
                    id: config.element_id[key]
                };
            }
        }
    }

    ElecoaNavigationButtons.prototype = {
        /**
         * ボタンをすべて表示する。
         */
        showAll: function() {
            for (key in this.buttons) {
                var element = $('#' + this.buttons[key].id);
                if (element) {
                    element.show();
                }
            }
        },

        /**
         * ボタンをすべて非表示にする。
         */
        hideAll: function() {
            for (key in this.buttons) {
                var element = $('#' + this.buttons[key].id);
                if (element) {
                    element.hide();
                }
            }
        },

        /**
         * ボタンパラメータによってボタンの表示非表示を設定する。
         * @param array params
         */
        showByParams: function(params) {
            for (key in this.buttons) {
                if (params.indexOf(key) >= 0) {
                    var element = $('#' + this.buttons[key].id);
                    if (element) {
                        element.show();
                    }
                }
            }
        },

        /**
         * ボタンを無効にする。
         * @param string command_name
         */
        disableButton: function(command_name) {
            if (command_name in this.buttons) {
                var element = $('#' + this.buttons[command_name].id);
                if (element) {
                    element.attr('_href', element.attr('href'));
                    element.removeAttr('href');
                    element.addClass('disabled');
                    element.prop('disabled', true);
                }
            }
        },

        /**
         * ボタンを有効にする。
         * @param string command_name
         */
        enableButton: function(command_name) {
            if (command_name in this.buttons) {
                var element = $('#' + this.buttons[command_name].id);
                if (element) {
                    element.attr('href', element.attr('_href'));
                    element.removeAttr('_href');
                    element.removeClass('disabled');
                    element.prop('disabled', false);
                }
            }
        }
    };

    return ElecoaNavigationButtons;
})();


/**
 * 目次ツリービュー
 */
ElecoaIndexTreeview = (function() {
    /**
     * コンストラクタ
     * @param object config { title_element_id, treeview_element_id }
     */
    function ElecoaIndexTreeview(config) {
        //ツリービューオブジェクト
        this._treeview = null;
        //ツリービューのエレメントID
        this._treeview_element_id = config.treeview_element_id;
        //タイトルのエレメントID
        this._title_element_id = config.title_element_id;
    }

    ElecoaIndexTreeview.prototype = {
        /**
         * 子ノードをツリービューに追加する。
         * @param object parent_node 親ノードオブジェクト。
         * @param array child 子アクティビティ。
         * @param {Object} hidden_from_choice
         * @param {Object} disabled
         */
        _add_child_node: function(parent_node, child, hidden_from_choice, disabled){
            var cssclass = '';
            if (child.type == 'BLOCK' || child.type == 'ROOT') {
                cssclass = 'elecoa-treenode-block';
                if (child.success_status == 'satisfied' || child.success_status == 'passed') {
                    cssclass += ' passed';
                }
                else if (child.success_status == 'not satisfied' || child.success_status == 'failed') {
                    cssclass += ' failed';
                }
                if (child.completion_status == 'completed') {
                    cssclass += ' completed';
                }
                else if (child.completion_status == 'not completed') {
                    cssclass += ' not-completed';
                }
            }
            else if (child.type == 'LEAF') {
                cssclass = 'elecoa-treenode-leaf';
                if (child.is_active) {
                    cssclass += ' current';
                }
                else {
                    if (child.success_status == 'satisfied' || child.success_status == 'passed') {
                        cssclass += ' passed';
                    }
                    else if (child.success_status == 'not satisfied' || child.success_status == 'failed') {
                         cssclass += ' failed';
                    }
                    
                    if (child.completion_status == 'completed') {
                        cssclass += ' completed';
                    }
                    else if (child.completion_status == 'not completed') {
                        cssclass += ' not-completed';
                    }
                }
            }

            hidden_from_choice = hidden_from_choice || child.hidden_from_choice;
            var href = "javascript:top.Frameset.choice('" + child.id + "');";
            if (child.request_valid == 'false' || disabled || child.hidden_from_choice) {
                href = null;
            }

            var node = document.createElement('div');
            node.style.display = 'table';
            if (child.type == 'BLOCK' || child.type == 'ROOT') {
                $(node).append('<div style="display:table-cell;width:15px;" class="ygtvtm tvcol" nowrap><div style="width:15px;cursor:pointer;">&nbsp;</div></div>');
            } else {
                $(node).append('<div style="display:table-cell;width:15px;" nowrap>&nbsp;</div>');
            }
            $(node).find(".tvcol > div").click(function() {
                $(this).parent().toggleClass("ygtvtm").toggleClass("ygtvtp").parent().find(".tvchildren").toggle();
            });
            var node_item = document.createElement('div');
            if (href === null) {
                node_item.innerText = child.title;
            } else {
                $('<a>').appendTo($(node_item)).attr("href", href).text(child.title);
            }
            $(node_item).addClass("tvitem");
            $(node_item).addClass(cssclass);
            var node_children = document.createElement('div');
            node_children.style.marginLeft = '1px';
            $(node_children).addClass("tvchildren");
            node.appendChild(node_item);
            node.appendChild(node_children);
            $(parent_node).find(".tvchildren").get(0).appendChild(node);

            if (child.type == 'BLOCK' || child.type == 'ROOT') {
                for (var i = 0; i < child.children.length; i++) {
                    this._add_child_node(node, child.children[i], hidden_from_choice, disabled);
                }
            }
        },

        /**
         * 目次データを元に目次を出力する。
         * @param object data
         */
        output: function(data) {
            if (!data) {
                return;
            }
            // タイトルの設定
            var title_element = document.getElementById(this._title_element_id);
            if (title_element) {
                if (typeof title_element.textContent != 'undefined') {
                    title_element.textContent = data.title;
                }
                else {
                    title_element.innerText = data.title;
                }
            }

            // ツリービューの作成
            this._treeview = document.getElementById(this._treeview_element_id);
            var root = document.createElement('div');
            root.innerHTML = '<div class="tvitem"></div><div class="tvchildren"></div>';
            this._add_child_node(root, data, false, data.disabled);
            this._treeview.appendChild(root);
        },

        /**
         * ツリービューを表示する。
         */
        show: function() {
            $('#' + this._treeview_element_id).show();
        }, 

        /**
         * ツリービューを非表示にする。
         */
        hide: function() {
            $('#' + this._treeview_element_id).hide();
        }
    };

    return ElecoaIndexTreeview;
})();
