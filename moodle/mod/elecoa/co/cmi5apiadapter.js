
/**
 * コンテンツが読み込まれる前に呼ばれる。
 * @param {Object} sender 送信オブジェクト
 * @param {Object} results INITRTMした場合の結果
 */
top.Core.onBeforeLoadContent = function(sender, results) {
    var results_array = top.Core._makeArray(decodeURIComponent(results['initrtm_result']));
    top.CMI5AdapterAPI.Initialize(JSON.parse(results_array['init_result_js']));
}

top.CMI5AdapterAPI = (function() {

    var _data = null;

    Initialize = function (args) {
        _data = args;
    }

    SetValue = function (value) {
        top.Core.sendCommand('SETVALUE', value);
    }

    GetValue = function (key) {
        if (key === 'launcher.url') {
            var url = _data['baseUrl'];
            var registration = _data['registration'];
            var actor = _data['actor'];
            var activityId = _data['activityId'];
            var key = _data['key'];
            if (url.indexOf("?") >= 0) {
                url += "&";
            } else {
                if ((url.indexOf("http://") === 0) || (url.indexOf("https://") === 0)) {
                    url += "?";
                } else if (url.indexOf("://") >= 0) {
                    // do nothing
                } else {
                    url += "?";
                }
            }
            return url + 'endpoint=' + encodeURIComponent(top.baseUrl + '/mod/elecoa/lrslistener.php/') + '&fetch=' + encodeURIComponent(top.baseUrl + '/mod/elecoa/tokengen.php?k=' + key) + '&actor=' + actor + '&registration=' + registration + '&activityId=' + activityId;
        }
        return _data[key];
    }

    return {Initialize: Initialize, GetValue: GetValue, SetValue: SetValue};
})();
