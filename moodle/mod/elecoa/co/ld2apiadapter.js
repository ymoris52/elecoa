
/**
 * コンテンツが読み込まれる前に呼ばれる。
 * @param {Object} sender 送信オブジェクト
 * @param {Object} results INITRTMした場合の結果
 */
top.Core.onBeforeLoadContent = function(sender, results) {
    var results_array = top.Core._makeArray(decodeURIComponent(results['initrtm_result']));
    top.LDAPI.Initialize(JSON.parse(results_array['init_result_js']));
}

top.LDAPI = (function() {

    var _data = null;

    Initialize = function (args) {
        _data = args;
    }

    SetValue = function (value) {
        top.Core.sendCommand('SETVALUE', value);
    }

    GetValue = function (key) {
        if (key === 'token') {
            var result = top.Core.sendCommand('GETVALUE');
            return result.commandResultArray['token'];
        }
        if (key === 'launcher.url') {
            var url = _data['launcher.baseUrl'];
            var returnCmd = _data['launcher.returnCmd'];
            var cancelCmd = _data['launcher.cancelCmd'];
            return url + '&returnURL=' + encodeURIComponent(top.baseUrl + '/mod/elecoa/return.html?cmid=' + top.cmid + '&CID=' + top.content_id + '&' + returnCmd) + '&cancelURL=' + encodeURIComponent(top.baseUrl + '/mod/elecoa/return.html?cmid=' + top.cmid + '&CID=' + top.content_id + '&' + cancelCmd) + '&userName=' + encodeURIComponent(top.userName);
        }
        return _data[key];
    }

    return {Initialize: Initialize, GetValue: GetValue, SetValue: SetValue};
})();
