(function()
{

function cmi_datamodel()
{
    return '<?xml version="1.0"?>'
    +'<cmi>'
    +'  <comments_from_learner>'
    +'    <n>'
    +'      <comment readwrite="rw" datatype="lstring4000" />'
    +'      <location readwrite="rw" datatype="string250" />'
    +'      <timestamp readwrite="rw" datatype="time" />'
    +'    </n>'
    +'  </comments_from_learner>'
    +'  <comments_from_lms>'
    +'    <n>'
    +'      <comment readwrite="r" datatype="string4000" />'
    +'      <location readwrite="r" datatype="string250" />'
    +'      <timestamp readwrite="r" datatype="time" />'
    +'    </n>'
    +'  </comments_from_lms>'
    +'  <completion_status readwrite="rw" datatype="keyword">'
    +'    <keyword>completed</keyword>'
    +'    <keyword>incomplete</keyword>'
    +'    <keyword>not attempted</keyword>'
    +'    <keyword def="t">unknown</keyword>'
    +'  </completion_status>'
    +'  <completion_threshold readwrite="r" datatype="real7_range1" />'
    +'  <credit readwrite="r" datatype="keyword">'
    +'    <keyword def="t">credit</keyword>'
    +'    <keyword>no-credit</keyword>'
    +'  </credit>'
    +'  <entry readwrite="r" datatype="keyword">'
    +'    <keyword def="t">ab-initio</keyword>'
    +'    <keyword>resume</keyword>'
    +'    <keyword />'
    +'  </entry>'
    +'  <exit readwrite="w" datatype="keyword">'
    +'    <keyword>time-out</keyword>'
    +'    <keyword>suspend</keyword>'
    +'    <keyword>logout</keyword>'
    +'    <keyword>normal</keyword>'
    +'    <keyword def="t" />'
    +'  </exit>'
    +'  <interactions>'
    +'    <n>'
    +'      <id readwrite="rw" datatype="identifier4000" />'
    +'      <type readwrite="rw" datatype="keyword">'
    +'        <keyword def="t">true-false</keyword>'
    +'        <keyword>choice</keyword>'
    +'        <keyword>fill-in</keyword>'
    +'        <keyword>long-fill-in</keyword>'
    +'        <keyword>likert</keyword>'
    +'        <keyword>matching</keyword>'
    +'        <keyword>performance</keyword>'
    +'        <keyword>sequencing</keyword>'
    +'        <keyword>numeric</keyword>'
    +'        <keyword>other</keyword>'
    +'      </type>'
    +'      <objectives>'
    +'        <n>'
    +'          <id readwrite="rw" datatype="identifier4000" />'
    +'        </n>'
    +'      </objectives>'
    +'      <timestamp readwrite="rw" datatype="time" />'
    +'      <correct_responses>'
    +'        <n>'
    +'          <pattern readwrite="rw" datatype="response" />'
    +'        </n>'
    +'      </correct_responses>'
    +'      <weighting readwrite="rw" datatype="real7" />'
    +'      <learner_response readwrite="rw" datatype="response" />'
    +'      <result readwrite="rw" datatype="keyword_or_real7">'
    +'        <keyword>correct</keyword>'
    +'        <keyword>incorrect</keyword>'
    +'        <keyword>unanticipated</keyword>'
    +'        <keyword>neutral</keyword>'
    +'      </result>'
    +'      <latency readwrite="rw" datatype="timeinterval" />'
    +'      <description readwrite="rw" datatype="lstring250" />'
    +'    </n>'
    +'  </interactions>'
    +'  <launch_data readwrite="r" datatype="string4000" />'
    +'  <learner_id readwrite="r" datatype="identifier4000" />'
    +'  <learner_name readwrite="r" datatype="string250" />'
    +'  <learner_preference>'
    +'    <audio_level readwrite="rw" datatype="real7_ge0" />'
    +'    <language readwrite="rw" datatype="lang" />'
    +'    <delivery_speed readwrite="rw" datatype="real7_ge0" />'
    +'    <audio_captioning readwrite="rw" datatype="keyword">'
    +'      <keyword>-1</keyword>'
    +'      <keyword def="t">0</keyword>'
    +'      <keyword>1</keyword>'
    +'    </audio_captioning>'
    +'  </learner_preference>'
    +'  <location readwrite="rw" datatype="string1000" />'
    +'  <max_time_allowed readwrite="r" datatype="timeinterval" />'
    +'  <mode readwrite="r" datatype="keyword">'
    +'    <keyword>browse</keyword>'
    +'    <keyword def="t">normal</keyword>'
    +'    <keyword>review</keyword>'
    +'  </mode>'
    +'  <objectives>'
    +'    <n>'
    +'      <id readwrite="rw" datatype="identifier4000" />'
    +'      <score>'
    +'        <scaled readwrite="rw" datatype="real7_range1" />'
    +'        <raw readwrite="rw" datatype="real7" />'
    +'        <max readwrite="rw" datatype="real7" />'
    +'        <min readwrite="rw" datatype="real7" />'
    +'      </score>'
    +'      <success_status readwrite="rw" datatype="keyword">'
    +'        <keyword>passed</keyword>'
    +'        <keyword>failed</keyword>'
    +'        <keyword def="t">unknown</keyword>'
    +'      </success_status>'
    +'      <completion_status readwrite="rw" datatype="keyword">'
    +'        <keyword>completed</keyword>'
    +'        <keyword>incomplete</keyword>'
    +'        <keyword>not attempted</keyword>'
    +'        <keyword def="t">unknown</keyword>'
    +'      </completion_status>'
    +'      <progress_measure readwrite="rw" datatype="real7_ge0range1" />'
    +'      <description readwrite="rw" datatype="string250" />'
    +'    </n>'
    +'  </objectives>'
    +'  <progress_measure readwrite="rw" datatype="real7_ge0range1" />'
    +'  <scaled_passing_score readwrite="r" datatype="real7_range1" />'
    +'  <score>'
    +'    <scaled readwrite="rw" datatype="real7_range1" />'
    +'    <raw readwrite="rw" datatype="real7" />'
    +'    <max readwrite="rw" datatype="real7" />'
    +'    <min readwrite="rw" datatype="real7" />'
    +'  </score>'
    +'  <session_time readwrite="w" datatype="timeinterval" />'
    +'  <success_status readwrite="rw" datatype="keyword">'
    +'    <keyword>passed</keyword>'
    +'    <keyword>failed</keyword>'
    +'    <keyword def="t">unknown</keyword>'
    +'  </success_status>'
    +'  <suspend_data readwrite="rw" datatype="string64000" />'
    +'  <time_limit_action readwrite="r" datatype="keyword">'
    +'    <keyword>exit,message</keyword>'
    +'    <keyword>exit,no message</keyword>'
    +'    <keyword>continue,message</keyword>'
    +'    <keyword def="t">continue,no message</keyword>'
    +'  </time_limit_action>'
    +'  <total_time readwrite="r" datatype="timeinterval" />'
    +'</cmi>';
}

function util_encURI(s) { return s === null ? "" : encodeURIComponent(s); }

function util_decURI(s) { return s === null ? "" : decodeURIComponent(s); }

function util_null2e(s) { return s === null ? "" : s; }

function util_isIE() { return !window.DOMParser; }

function util_ieversion() {
    var ie = (function(){
        var ua = navigator.userAgent;
        var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null) {
            return parseFloat(RegExp.$1);
        } else {
            var array = ua.indexOf('Trident') >= 0 ? /rv:\s?([\d\.]+)/.exec(ua) : null;
            var version = (array) ? array[1] : '';
            if (version) {
                return parseFloat(version);
            }
        }
        return undefined;
    }());
    
    return ie;
}

function util_ms(t) {
    var ms = 0;
    var hms = t.split(":");
    ms += (parseInt(hms[0], 10) * 60 * 60 * 1000);
    ms += (parseInt(hms[1], 10) * 60 * 1000);
    ms += (parseInt(hms[2], 10) * 1000);
    return ms;
}

function util_mkArray(str){
    var i, pos, key, val;
    var buf = str.split("\n");
    var len = buf.length;
    var retArray = new Array();
    for (i = 0; i < len; i++) {
        if(buf[i] != "") {
            pos = buf[i].indexOf("=");
            key = buf[i].substring(0, pos);
            val = buf[i].substring(pos + 1);
            retArray[key] = val;
        }
    }
    return retArray;
}

function util_sum_timeinterval(timeinterval1, timeinterval2) {
    var time1 = util_get_seconds_from_timeinterval(timeinterval1);
    var time2 = util_get_seconds_from_timeinterval(timeinterval2);

    return util_get_timeinterval_from_seconds(time1 + time2);
}

function util_get_seconds_from_timeinterval(timeinterval) {
    if ((timeinterval == null) || (typeof timeinterval == 'undefined')) {
        return 0.0;
    }
    
    if (!timeinterval.match(/^P(?:(\d*)Y)?(?:(\d*)M)?(?:(\d*)D)?(?:T(?:(\d*)H)?(?:(\d*)M)?(?:(\d*(?:.\d{1,2})?)S)?)?$/)) {
        return 0.0;
    }
    else {
        var years = Number(RegExp.$1);
        var months = Number(RegExp.$2);
        var days = Number(RegExp.$3);
        var hours = Number(RegExp.$4);
        var minutes = Number(RegExp.$5);
        var seconds = Number(RegExp.$6);
        
        seconds += 60.0 * minutes;
        seconds += 60.0 * 60.0 * hours;
        seconds += 60.0 * 60.0 * 24.0 * days;
        seconds += 60.0 * 60.0 * 24.0 * 30.0 * months;
        seconds += 60.0 * 60.0 * 24.0 * 30.0 * 365.0 * years;
        
        return seconds;
    }
}

function util_get_timeinterval_from_seconds(seconds) {
    var ti_seconds = String((seconds % 60.0).toFixed(2));
    var ti_minutes = String(Math.floor(Math.floor(seconds / 60.0) % 60.0));
    var ti_hours = String(Math.floor(seconds / (60.0 * 60.0)));

    return 'PT' + ti_hours + 'H' + ti_minutes + 'M' + ti_seconds + 'S';
}

function util_dom2xml(node) {
   try {
      // Gecko- and Webkit-based browsers (Firefox, Chrome), Opera.
      return (new XMLSerializer()).serializeToString(node);
  }
  catch (e) {
     try {
        // Internet Explorer.
        return node.xml;
     }
     catch (e) {
        //Other browsers without XML Serializer
        alert('Xmlserializer not supported');
     }
   }
   return false;
}

// XMLDom Class Definition

var XMLDom = function() {
    this.xml = "";
    this.async = false; //async property is always false.
    var parser = new DOMParser();
    this.dom = parser.parseFromString("<xmldom/>", "text/xml");
    this.documentElement = this.dom.documentElement;
};

XMLDom.prototype.loadXML = function(xml) {
    var parser = new DOMParser();
    this.dom = parser.parseFromString(xml, "text/xml");
    this.documentElement = this.dom.documentElement;
    this.xml = xml;
};

XMLDom.prototype.createElement = function(name) {
    return this.dom.createElement(name);
};

XMLDom.prototype.appendChild = function(node) {
    if (this.dom.documentElement.nodeName === "xmldom") {
        this.dom.replaceChild(node, this.dom.documentElement);
        this.documentElement = node;
    } else {
        this.dom.documentElement.appendChild(node);
    }
};

XMLDom.prototype.selectSingleNode = function(xpath) {
    var _xpath = xpath;
    if (xpath.indexOf("/") != 0) {
        _xpath = "/" + xpath;
    }
    var _doc = this.dom.evaluate ? this.dom : document;
    var xpathResult = _doc.evaluate(_xpath, this.dom.documentElement, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
    if (xpathResult.snapshotLength > 0) {
        return xpathResult.snapshotItem(0);
    } else {
        return null;
    }
};

function node_selectSingleNode(node, xpath) {
    if (util_ieversion()) {
        return node.selectSingleNode(xpath);
    } else {
        if (node.selectSingleNode) {
            return node.selectSingleNode(xpath);
        } else {
            var xpathResult = node_selectNodes(node, xpath);
            if(xpathResult.snapshotLength > 0) {
                return xpathResult.snapshotItem(0);
            } else {
                return null;
            }
        }
    }
}

function node_selectNodes(node, xpath) {
    var _xpath = xpath;
    if (xpath.indexOf("/") != 0) {
        _xpath = "./" + xpath;
    }
    var _doc = node.ownerDocument.evaluate ? node.ownerDocument : document;
    return _doc.evaluate(_xpath, node, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
}

function node_includesTextInNodes(node, xpath, s) {
    if (util_ieversion()) {
        var n = node.selectNodes(xpath);
        for (i=0; i < n.length; i++) {
            if (dom_get_node_text(n.item(i)) == s) {
                return true;
            }
        }
    } else {
        var xpathResult = node_selectNodes(node, xpath);
        for (i=0; i < xpathResult.snapshotLength; i++) {
            if (dom_get_node_text(xpathResult.snapshotItem(i)) == s) {
                return true;
            }
        }
    }
    return false;
}

function dom_createXMLDom() {
    var ieversion = util_ieversion();
    if (ieversion) {
        if (ieversion < 9) {
            return new ActiveXObject("Microsoft.XMLDOM");
        }
        else {
            return new ActiveXObject("MSXML2.DOMDocument");
        }
    }
    
    return new XMLDom();
}

function dom_addNode(dom, org, name) {
    if (node_selectSingleNode(org, name) === null) {
        var elm = dom.createElement(name);
        org.appendChild(elm);
        return false;
    } else {
        return true;
    }
}

function dom_set_node_text(node, text) {
    if (typeof node.text != 'undefined') {
        node.text = text;
    }
    if (typeof node.textContent != 'undefined') {
        node.textContent = text;
    }
    else if (typeof node.innerText != 'undefined') {
        node.innerText = text;
    }
}

function dom_get_node_text(node) {
    if (typeof node.textContent != 'undefined') {
        return node.textContent;
    }
    else if (typeof node.innerText != 'undefined') {
        return node.innerText;
    }
    else if (typeof node.text != 'undefined') {
        return node.text;
    }

    return undefined;
}

function dom_addText(dom, p, name, s) {
    var pn = node_selectSingleNode(dom, p);
    if (node_selectSingleNode(pn, name) === null) {
        var tn = dom.createElement(name);
        dom_set_node_text(tn, s);
        pn.appendChild(tn);
        return false;
    } else {
        return true;
    }
}

function dom_setText(dom, path, node_name, value) {
    var parent_node = node_selectSingleNode(dom, path);
    var target_node = node_selectSingleNode(parent_node, node_name);
    if (target_node) {
        dom_set_node_text(target_node, value);
    }
    else {
        var text_node = dom.createElement(node_name);
        dom_set_node_text(text_node, value);
        parent_node.appendChild(text_node);
    }
}

function InitSco(dom, s) {
    var i, buf, key, button_param;
    var success_Status, scaled_Score, passing_Score;
    var completion_Status, progress_Measure, completion_Threshold, total_Time;
    var attemptAbsoluteDurationLimit, entry, credit, mode, launch_data, time_limit_action;

    s = s.replace(/\r/g, "");
    buf = util_mkArray(s);

    if (buf['rtm_xml'] && buf['rtm_xml'] != '') {
       var xml = decodeURIComponent(buf['rtm_xml']);
       dom.loadXML(xml);
    }
    else {
        var root = dom.createElement("cmi");
        dom.appendChild(root);
    }

    button_param                 = buf['rtm_button_param'];
    success_Status               = buf['rtm_success_Status'];
    scaled_Score                 = buf['rtm_scaledScore'];
    passing_Score                = buf['rtm_passingScore'];
    completion_Status            = buf['rtm_completionStatus'];
    progress_Measure             = buf['rtm_progressMeasure'];
    completion_Threshold         = buf['rtm_completionThreshold'];
    total_Time                   = buf['rtm_totalTime'];
    attemptAbsoluteDurationLimit = buf['rtm_attemptAbsoluteDurationLimit'];
    entry                        = buf['rtm_entry'];
    credit                       = buf['rtm_credit'];
    mode                         = buf['rtm_mode'];
    launch_data                  = buf['rtm_dataFromLMS'];
    time_limit_action            = buf['rtm_timeLimitAction'];
    suspend_data                 = util_decURI(buf['rtm_suspendData']);

    var learnerInfo = top.Core.getLearnerInfo();
    dom_addText(dom, "/cmi", "learner_id", learnerInfo.UserID);
    dom_addText(dom, "/cmi", "learner_name", learnerInfo.UserName);
    dom_addText(dom, "/cmi", "success_status", success_Status);
    dom_addText(dom, "/cmi", "completion_status", completion_Status);
    if (passing_Score != '') {
        dom_addText(dom, "/cmi", "scaled_passing_score", passing_Score);
    }
    if (scaled_Score != '') {
        dom_addNode(dom, node_selectSingleNode(dom, "/cmi"), "score");
        dom_addText(dom, "/cmi/score", "scaled", scaled_Score);
    }
    if (progress_Measure != '') {
        dom_addText(dom, "/cmi", "progress_measure", progress_Measure);
    }
    if (completion_Threshold != '') {
        dom_addText(dom, "/cmi", "completion_threshold", completion_Threshold);
    }
    if (total_Time != '') {
        dom_addText(dom, "/cmi", "total_time", total_Time);
    }
    if (node_selectSingleNode(dom, "/cmi/learner_preference") === null) {
        dom_addNode(dom, node_selectSingleNode(dom, "/cmi"), "learner_preference");
    }
    dom_addText(dom, "/cmi/learner_preference", "delivery_speed", "1"); //REQ_68.3.3
    dom_addText(dom, "/cmi/learner_preference", "audio_captioning", "0"); //REQ_68.4.3
    dom_addText(dom, "/cmi/learner_preference", "audio_level", "1"); //REQ_68.5.3
    dom_addNode(dom, node_selectSingleNode(dom, "/cmi"), "objectives");
    if (attemptAbsoluteDurationLimit != '') {
        dom_setText(dom, "/cmi", "max_time_allowed", attemptAbsoluteDurationLimit);
    }
    if (launch_data != '') {
        dom_setText(dom, "/cmi", "launch_data", launch_data);
    }
    dom_setText(dom, "/cmi", "time_limit_action", time_limit_action);
    dom_setText(dom, "/cmi", "entry", entry);
    dom_setText(dom, "/cmi", "credit", credit);
    dom_setText(dom, "/cmi", "mode", mode);
    if (entry == "resume") {
        dom_setText(dom, "/cmi", "suspend_data", suspend_data);
    }
    for (i = 0; i < 100; i++) {
        key = 'rtm_objData.' + i;
        if (typeof(buf[key]) == 'undefined') {
            break;
        } else {
            SetObjectives(dom, i, buf[key]);
        }
    }
	
	
    // インターフェースの初期化
    ADP.InitializeInterface({ rtm_button_param: buf['rtm_button_param'] ? buf['rtm_button_param'].split(',') : [] });

    return true;
}

function SetObjectives(dom, num, s) {
    var node = node_selectSingleNode(dom, "/cmi/objectives");
    var data = s.split(",");
    if (data.length > 4) {
        var sNum = "i" + num;
        dom_addNode(dom, node, sNum);
        var path = "/cmi/objectives/" + sNum;
        dom_addNode(dom, node, sNum);
        var sNode = node_selectSingleNode(node, sNum);
        dom_setText(dom, path, "id", data[0]);
        dom_setText(dom, path, "success_status", data[1]);

        if (data[2] != '') {
            dom_addNode(dom, sNode, "score");
            dom_setText(dom, path + "/score", "scaled", data[2]);
        }
        dom_setText(dom, path, "completion_status", data[3]);

        if (data[4] != '') {
            dom_setText(dom, path, "progress_measure", data[4]);
        }
    }
    return;
}

function apiAdapter() {
    this.DM = dom_createXMLDom();
    this.SCO = null;
    this.mrt;
    this.stime = 0;
    this.write = 0;

    this.InitializeAdapter = InitializeAdapter;
    this.InitializeInterface = function(){
		var func = top.ScormAPIAdapter.getInitializationFunction();
		if (func) {
			func.apply(null, arguments);
		}
	};
    this.InitializeSCO = InitializeSCO;
    this.TerminateSCO = TerminateSCO;

    this.sco_GetLimitTime = sco_GetLimitTime;
    this.sco_GetValue = sco_GetValue;
    this.sco_GetText = sco_GetText;
    this.sco_GetCount = sco_GetCount;
    this.sco_Exists = sco_Exists;
    this.sco_Double = sco_Double;
    this.sco_SetValue = sco_SetValue;
    this.sco_Commit = sco_Commit;
    this.sco_SetObjID = sco_SetObjID;

    this.cmi_CheckModel = cmi_CheckModel;
    this.cmi_CheckChildren = cmi_CheckChildren;
    this.cmi_CheckCount = cmi_CheckCount;
    this.cmi_CheckData = cmi_CheckData;
    this.cmi_CheckValue = cmi_CheckValue;
}

function InitializeAdapter(){
    this.DM.async = false;
    this.DM.loadXML(cmi_datamodel());
    return (this.DM.xml == "");
}

function InitializeSCO() {
    var ret = top.ScormAPIAdapter.doCommand("INITRTM");
    this.SCO = dom_createXMLDom();
    if (!InitSco(this.SCO, ret)) {
        alert("Error InitializeSCO");
        return 0;
    }
    var now = new Date();
    this.stime = now.getTime();
    this.write = 1;
    return 1;
}

function TerminateSCO() {
    ADP.sco_Commit(1);
    return 1;
}

function sco_GetLimitTime() {
    var lt, tt;

    lt = this.sco_GetValue("cmi.max_time_allowed");
    if (lt === null) return 0;
    lt = util_ms(lt);

    tt = this.sco_GetValue("cmi.total_time");
    tt = (tt === null) ? 0 : util_ms(tt);

    lt -= tt;
    if (lt <= 0) lt = 1;
    return lt;
}

function sco_GetValue(m) {
    m = cmi_RepS(m);
    return this.sco_GetText(m);
}

function sco_GetText(m) {
    var n = node_selectSingleNode(this.SCO, m);
    return n === null ? null : dom_get_node_text(n);
}

function sco_GetCount(m) {
    m = cmi_RepS(m);
    m = m.replace("/_count", "");
    var n = node_selectSingleNode(this.SCO, m);
    return n === null ? 0 : n.childNodes.length;
}

function sco_Exists(m) {
    var n = node_selectSingleNode(this.SCO, m);
    return !(n === null);
}

function sco_Double(m, mct, s) {
    var buf = m.split(".");
    var mdam = "/cmi/interactions/i" + buf[2] + "/correct_responses";
    var node = node_selectSingleNode(this.SCO, mdam);
    if (node != null) {
        var nn = node.childNodes;
        var nnn = null;
        for (i = 0; i < nn.length; i++) {
            if (i != mct) {
                nnn = node_selectSingleNode(nn.item(i), "pattern");
                if (nnn != null) {
                    if (s == dom_get_node_text(nnn)) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function sco_SetValue(m, s) {
    if (m == "cmi.session_time") {
        this.stime = 0;
    }
    m = cmi_RepS(m);

    var i, tn, nn, cn;
    var count = 0;
    var buf = m.split("/");
    cn = this.SCO;
    for (i = 0; i < buf.length; i++) {
        tn = node_selectSingleNode(cn, buf[i]);
        if (tn === null) {
            nn = this.SCO.createElement(buf[i]);
            cn.appendChild(nn);
            cn = nn;
        } else {
            cn = tn;
        }

        if (buf[i].match(/^i{1}\d+/) != null) {
            count++;
            if (count === 1) {
                cn.setAttribute("f_chg", "1");
            }
        }
    }
    dom_set_node_text(cn, s);
    this.write = 1;
}

function sco_Commit(num) {
    var sData = "";
    var i,j;
    var pn,cnl;
    var tagname, pathname;
    var CRLF = "\r\n";
    var dom = dom_createXMLDom();
    var node_cmi = this.SCO.documentElement.cloneNode(true);
    dom.appendChild(node_cmi);

    sData = "[Core]" + CRLF;
    sData += "lessonLocation=" + util_encURI(this.sco_GetValue("cmi.location")) + CRLF;

    var bss = this.sco_GetValue("cmi.success_status");
    var bcs = this.sco_GetValue("cmi.completion_status");

    var s_scaled = util_null2e(this.sco_GetValue("cmi.score.scaled"));
    var s_raw = util_null2e(this.sco_GetValue("cmi.score.raw"));
    var s_max = util_null2e(this.sco_GetValue("cmi.score.max"));
    var s_min = util_null2e(this.sco_GetValue("cmi.score.min"));

    var mscore = this.sco_GetValue("cmi.scaled_passing_score");
    if ((mscore != null) && (s_scaled != '')) {
        bss = eval(s_scaled) >= eval(mscore) ? "passed" : "failed";
    }

    var mmj = this.sco_GetValue("cmi.completion_threshold");
    var p_measure = util_null2e(this.sco_GetValue("cmi.progress_measure"));
    if ((mmj != null) && (p_measure != '')) {
        bcs = eval(p_measure) >= eval(mmj) ? "completed" : "incomplete";
    }

    sData += "successStatus=" + bss + CRLF;
    sData += "completionStatus=" + bcs + CRLF;
    sData += "scoreAll=" + util_encURI(s_scaled + "," + s_raw + "," + s_max + "," + s_min) + CRLF;
    sData += "progressMeasure=" + p_measure + CRLF;
    if (this.sco_GetValue("cmi.exit") == 'suspend') {
        sData += "exit=suspend" + CRLF;
    } else {
        sData += "exit=" + CRLF;
    }
    var session_time = this.sco_GetValue("cmi.session_time");
    if (session_time !== null) {
        sData += "sessionTime=" + util_encURI(session_time) + CRLF;
    }

    sData += "suspendData=" + util_encURI(this.sco_GetValue("cmi.suspend_data")) + CRLF;

    dom_setText(dom, '/cmi', 'success_status', bss);
    dom_setText(dom, '/cmi', 'completion_status', bcs);

    sData += "runtimeXML=" + util_encURI(util_dom2xml(dom.documentElement)) + CRLF;
    //var node_cmi_interactions = node_selectSingleNode(dom.documentElement, '/cmi/interactions');
    //var node_cmi_comments = node_selectSingleNode(dom.documentElement, '/cmi/comments_from_learner');
    //sData += "xml=" + util_encURI('<cmi>')
    //                + (node_cmi_interactions ? util_encURI(util_dom2xml(node_cmi_interactions)) : '')
    //                + (node_cmi_comments ? util_encURI(util_dom2xml(node_cmi_comments)) : '')
    //                + util_encURI('</cmi>') + CRLF;

    sData += "[Objectives_Status]" + CRLF;

    pn = node_selectSingleNode(this.SCO, cmi_RepS("cmi.objectives"));
    if (pn != null) {
        cnl = pn.childNodes;

        var oid, ss, s_s, cs, p_m;
        for (i = 0; i < cnl.length; i++) {
            tagname = cnl.item(i).tagName;
            if (tagname.match(/^i{1}\d+/) != null) {
                if (cnl.item(i).getAttribute("f_chg") == "1") {
                    tagname = tagname.replace("i","");
                    pathname = "cmi.objectives.i";

                    oid = this.sco_GetValue(pathname + tagname + ".id");
                    ss  = this.sco_GetValue(pathname + tagname + ".success_status");
                    s_s = this.sco_GetValue(pathname + tagname + ".score.scaled");
                    cs  = this.sco_GetValue(pathname + tagname + ".completion_status");
                    p_m = this.sco_GetValue(pathname + tagname + ".progress_measure");
                    if (ss === null) ss = "unknown";
                    if (s_s === null) s_s = "";
                    if (cs === null) cs = "unknown";
                    if (p_m === null) p_m = "";

                    sData += "ObjData=" + util_encURI(oid + "," + ss + "," + s_s + "," + cs + "," + p_m) + CRLF;
                    post_ResetFlag("cmi.objectives.i" + tagname);
                }
            }
        }
    }

    sData += "[Interactions_Data]" + CRLF;

    pn = node_selectSingleNode(this.SCO, cmi_RepS("cmi.interactions"));
    if (pn != null) {
        var iwflg = false;
        cnl = pn.childNodes;
        pathname = "cmi.interactions.i";
        for (i = 0; i < cnl.length; i++) {
            tagname = cnl.item(i).tagName;
            if (tagname.match(/^i{1}\d+/) != null) {
                iwflg = true;
                if (cnl.item(i).getAttribute("f_chg") == "1") {
                    tagname = tagname.replace("i", "");
                    sData += '"' + util_encURI(post_GetValue(pathname + tagname + ".timestamp")) + '","';
                    sData += util_encURI(post_GetValue(pathname + tagname + ".id")) + '","';

                    var temp = post_GetMultiValue(pathname + tagname + ".objectives", "id");
                    if (temp != null) {
                        sData += util_encURI(temp);
                    }

                    sData += '","' + util_encURI(post_GetValue(pathname + tagname + ".type")) + '","';

                    temp = post_GetMultiValue(pathname + tagname + ".correct_responses", "pattern");
                    if (temp != null) {
                        sData += util_encURI(temp);
                    }

                    sData += '","';
                    sData += util_encURI(post_GetValue(pathname + tagname + ".learner_response")) + '","';
                    sData += util_encURI(post_GetValue(pathname + tagname + ".result")) + '","';
                    sData += util_encURI(post_GetValue(pathname + tagname + ".weighting")) + '","';
                    sData += util_encURI(post_GetValue(pathname + tagname + ".latency")) + '","';
                    sData += util_encURI(post_GetValue(pathname + tagname + ".description")) + '"' + CRLF;

                    post_ResetFlag("cmi.interactions.i" + tagname);
                }
            }
        }
    }

    sData += "[Comments_From_Learner]" + CRLF;

    pn = node_selectSingleNode(this.SCO, cmi_RepS("cmi.comments_from_learner"));
    if (pn != null) {
        cnl = pn.childNodes;
        pathname = "cmi.comments_from_learner.i";
        for (i = 0; i < cnl.length; i++) {
            tagname = cnl.item(i).tagName;
            if (tagname.match(/^i{1}\d+/) != null) {
                tagname = tagname.replace("i", "");
                sData += '"' + util_encURI(post_GetValue(pathname + tagname + ".date_time")) + '","';
                sData += util_encURI(post_GetValue(pathname + tagname + ".location")) + '","';
                sData += util_encURI(post_GetValue(pathname + tagname + ".comment")) + '"' + CRLF;
            }
        }
    }

    var ret = num ? top.ScormAPIAdapter.doCommand("FINRTM", sData) : top.ScormAPIAdapter.doCommand("COMMIT", sData);
    return true;
}

function sco_SetObjID(m, s) {
    var zbuf = m.split(".");
    var idNode;
    if (zbuf[1] == "objectives") {
        var cNum = parseInt(zbuf[2]);
        var node = node_selectSingleNode(this.SCO, "/cmi/objectives");

        if (node != null) {
            var iList = node.childNodes;

            for (i = 0; i < iList.length; i++) {
                idNode = node_selectSingleNode(iList.item(i), "id");
                if (i != cNum) {
                    if (idNode != null) {
                        if (dom_get_node_text(idNode) == s) {
                            return true;
                        }
                    }
                } else {
                    if (dom_get_node_text(idNode) != s) {
                        return true; // The data model element's value is already set and cannot be changed.
                    }
                }
            }
        }
    } else if (zbuf[1] == "interactions") {
        var cNum = parseInt(zbuf[4]);
        var node = node_selectSingleNode(this.SCO, "/cmi/interactions/i" + zbuf[2] + "/objectives");

        if (node != null) {
            var iList = node.childNodes;

            for (i = 0; i < iList.length; i++) {
                if (i != cNum) {
                    idNode = node_selectSingleNode(iList.item(i), "id");
                    if (idNode != null) {
                        if (dom_get_node_text(idNode) == s) {
                            return true;
                        }
                    }
                }
            }
        }
    } else {
        return true;
    }

    return false;
}

function scoutil_replace_learner_preference(m) {
    var retval = m;
    if (m == "cmi.learner_preference.audio") {
        retval = "cmi.learner_preference.audio_level";
    }

    if (m == "cmi.learner_preference.speed") {
        retval = "cmi.learner_preference.delivery_speed";
    }

    if (m == "cmi.learner_preference.text") {
        retval = "cmi.learner_preference.audio_captioning";
    }
    return retval;
}

function cmi_CheckModel(m) { return node_selectSingleNode(ADP.mrt, m); }

function cmi_CheckChildren(mm) {
    var m = cmi_RepG(mm);
    m = m.replace("/_children","");

    var i;
    var ret = "";
    var n = this.cmi_CheckModel(m);
    if (n === null) { return null; }

    n = n.childNodes;
    for (i = 0; i < n.length; i++) {
        ret += n.item(i).nodeName + ",";
    }
    ret =  (ret == "") ? "" : ret.substring(0, ret.length - 1);
    if (ret == "n") {
        return this.cmi_CheckChildren(mm + ".n");
    } else {
        return ret;
    }
}

function cmi_CheckCount(m) {
    m = cmi_RepG(m);
    m = m.replace("/_count", "");

    var n = this.cmi_CheckModel(m);
    if (n === null) { return null; }

    n = node_selectSingleNode(n, "n");
    return (n === null) ? 0 : 1;
}

function cmi_CheckData(m, f) {
    m = cmi_RepG(m);

    var n = this.cmi_CheckModel(m);
    if (n === null) { return null; }

    var ret = n.getAttribute("readwrite");
    if (ret === null) { return null; }

    if ((ret == "w") && (!f)) { return 0; }
    if ((ret == "r") && (f)) { return 0; }

    return n.getAttribute("datatype");
}

function cmi_CheckValue(t, s, m) {
    var f = false;
    if (t == "real7") {
        if (!isNaN(s)) {
            f = (0 <= eval(s)) ? true : _setLastErrorR("407", false);
        }
    } else if (t == "real7_range1") {
        if (!isNaN(s)) {
            f = (Math.abs(eval(s)) <= 1) ? true : _setLastErrorR("407", false);
        }
    } else if (t == "real7_ge0") {
        if (!isNaN(s)) {
            f = (0 <= eval(s)) ? true : _setLastErrorR("407", false)
        }
    } else if (t == "real7_ge0range1") {
        if (!isNaN(s)) {
            f = ((0 <= eval(s)) && (eval(s) <= 1)) ? true : _setLastErrorR("407", false);
        }
    } else if (t == "lang") {
        f = s == "" ? true : check_localLang("{lang=" + s + "}", 0);
    } else if (t == "string250") {
        f = Check_string250(s);
    } else if (t == "lstring250") {
        f = Check_lstring250(s);
    } else if (t == "string1000") {
        f = Check_string1000(s);
    } else if (t == "string4000") {
        f = Check_string4000(s);
    } else if (t == "string64000") {
        f = Check_string64000(s);
    } else if (t == "lstring4000") {
        f = Check_lstring4000(s);
    } else if (t == "identifier4000") {
        f = Check_identifier4000(s);
    } else if (t == "response") {
        f = true;
    } else if (t == "keyword") {
        f = Check_keyword(s, m);
    } else if (t == "keyword_or_real7") {
        f = Check_keyword(s, m);
        if (!f) {
            if (isNaN(s)) {
                f = _setLastErrorR("406", false);
            } else {
                f = _setLastErrorR("0", true);
            }
            if (s.length == 0) {
                f = _setLastErrorR("406", false);
            }
        }
    } else if (t == "timeinterval") {
        f = Check_timeinterval(s);
    } else if (t == "time") {
        f = Check_time(s);
    }
    return f;
}

function cmi_RepG(m) {
    var i;
    var buf = m.split(".");
    for (i in buf) {
        if (!isNaN(buf[i])) buf[i] = "n";
    }
    return "/" + buf.join("/");
}

function cmi_RepS(m) {
    var i;
    var buf = m.split(".");
    for (i in buf) {
        if (!isNaN(buf[i])) buf[i] = "i" + buf[i];
    }
    return buf.join("/");
}

function cmi_RepS2(m, flg) {
    var i;
    var retval = "";
    var buf = m.split(".");
    for (i in buf) {
        if (!isNaN(buf[i])) {
            if (flg) {
                if (parseInt(buf[i]) == 0) {
                    return "";
                } else {
                    buf[i] = "i" + (parseInt(buf[i]) - 1);
                }
                retval += buf[i];
                return retval;
            } else {
                buf[i] = "i" + buf[i];
                retval += buf[i] + "/";
                flg = true;
            }
        } else {
            retval += buf[i] + "/";
        }
    }
    return "";
}

function cmi_RepS3(m, n1, n2) {
    var buf = m.split(".");
    if (buf.length < 3) {
        alert("model error");
    }
    return "cmi/" + n1 + "/i" + buf[2] + "/" + n2;
}

function Check_keyword(s, m) {
    m = cmi_RepG(m) + "/keyword";
    return node_includesTextInNodes(ADP.mrt, m, s);
}

function Check_string250(s) { return (s.length <= 250); }

function Check_string4000(s) { return (s.length <= 4000); }

function Check_string64000(s) { return (s.length <= 64000); }

function Check_string1000(s) { return (s.length <= 1000); }

function Check_timeinterval(s) {
    var DTFlg = true;
    var DDFlg = false;
    var subStr, bufStr, code, pos;
    var buf = ["", "", "", "", "", ""];
    if (s.charAt(0) != "P") {
        return false;
    }

    if (s.charAt(s.length - 1) == "T") {
        return false;
    }

    subStr = s.substring(1, s.length);

    bufStr = "";
    for (i = 0; i < subStr.length; i++) {
        code = subStr.charCodeAt(i);
        if ((47 < code) && (code < 58)) {
            bufStr += subStr.charAt(i);
        } else if(code == 46) {
            if (DDFlg) {
                return false;
            }
            DDFlg = true;
            bufStr += subStr.charAt(i);
        } else {
            if (code == 89) {
                pos = 0;
            } else if (code == 77) {
                pos = DTFlg ? 1 : 4;
            } else if (code == 68) {
                pos = 2;
            } else if (code == 84) {
                DTFlg = false;
            } else if (code == 72) {
                if (DTFlg) { return false; }
                pos = 3;
            } else if (code == 83) {
                if (DTFlg) { return false; }
                pos = 5;
            } else {
                return false;
            }
            if (code != 84) {
                buf[pos] = bufStr;
                bufStr = "";
            }
        }
    }

    for (i = 0; i < 6; i++) {
        if (buf[i] != "") {
            if (i != 5) {
                if (/\D/.test(buf[i])) { return false; }
            }

            if (isNaN(buf[i])) { return false; }
            if (Number(buf[i]) < 0) { return false; }

            if (i == 5) {
                if (DTFlg) {
                    dbuf = buf[5].split(".");
                    if (dbuf.length != 2) { return false; }
                    if ((dbuf[1].length == 0) || (dbuf[1].length > 2)) { return false; }
                }
            }
        }
    }
    return true;
}

function Check_time(s) {
    var buf = ["", "", "", "", "", "", ""];
    var mBuf = s.split("T");
    var sBus = mBuf[0].split("-");
    var i;

    for (i = 0; i < sBus.length; i++) {
        if (i == 0) {
            if (sBus[i].length != 4) { return false; }
        } else {
            if (sBus[i].length != 2) { return false; }
        }
        buf[i] = eval(sBus[i]);
    }

    if (mBuf.length == 2) {
        var pus = false;
        var tzn = "";
        if (mBuf[1].indexOf("-") != -1) {
            pus = true;
            qbuf = mBuf[1].split("-");
            if (qbuf.length != 2) { return false; }

            mBuf[1] = qbuf[0];
            tzn = qbuf[1];
            if (tzn == "") { return false; }
        } else if (mBuf[1].indexOf("+") != -1) {
            pus = true;
            qbuf = mBuf[1].split("+");
            if (qbuf.length != 2) { return false; }
            mBuf[1] = qbuf[0];
            tzn = qbuf[1];
            if (tzn == "") { return false; }
        } else {
            if (mBuf[1].charAt(mBuf[1].length - 1) == "Z") {
                pus = true;
                mBuf[1] = mBuf[1].slice(0, -1);
            }
        }

        if (tzn != "") {
            if ((/^[012]\d$/.test(tzn)) || (/^[012]\d:[0-5]\d$/.test(tzn))) {
                if (tzn.charAt(0) == "2") {
                    if (eval(tzn.charAt(1)) > 3) { return false; }
                }
            } else {
                return false;
            }
        }

        sBus = mBuf[1].split(":");

        if (pus) {
            if (sBus.length != 3) { return false; }
            if (/\.\d{1,2}$/.test(sBus[2])) {
            } else {
                return false;
            }
        }
        var tBuf;
        for (i = 0;i < sBus.length; i++) {
            if (i == 2) {
                if ((sBus[2].length < 6) && (sBus[2].length != 3)) {
                    tBuf = sBus[2].split(".");
                    if (tBuf[0].length != 2) {
                        return false;
                    } else {
                        if (sBus[2].charAt(0) == "0") {
                            sBus[2] = sBus[2].slice(1);
                        }
                    }
                } else {
                    return false;
                }
            } else {
                if (sBus[i].length != 2) { return false; }
            }
            buf[i + 3] = eval(sBus[i]);
        }
    }

    var Y = buf[0], M = buf[1], D = buf[2], H = buf[3], m = buf[4], S = buf[5];

    if ((Y < 1970) || (2038 < Y)) { return false; }

    if (M === "") {
        if ((D != "") || (H != "") || (m != "") || (S != "")) { return false; }
    } else {
        if ((M < 1) || (M > 12)) { return false; }
    }

    if (D === "") {
        if ((H != "") || (m != "") || (S != "")) { return false; }
    } else {
        if ((D < 1) || (D > 31)) { return false; }
        if (M == 2) {
            if (Y % 4 == 0) {
                if (D > 29) { return false; }
            } else {
                if (D > 28) { return false; }
            }
        }
        if ((M == 4) || (M == 6) || (M == 9) || (M == 11)) {
            if (D > 30) { return false; }
        }
    }

    if (H === "") {
        if ((m != "") || (S != "")) { return false; }
    } else {
        if ((H < 0) || (H > 23)) { return false; }
    }

    if (m === "") {
        if (S != "") { return false; }
    } else {
        if (!((0 <= m) && (m < 60))) { return false; }
    }

    if (S != "") {
        if (!((0 <= S) && (S < 60))) { return false; }
    }
    return true;
}

function Check_lstring4000(s) {
    return check_localLang(s, 4000);
}

function Check_lstring250(s) {
    return check_localLang(s, 250);
}

function Check_identifier4000(s) {
    if (s.indexOf(":") == -1) {
        return check_shortID(s);
    } else {
        var ubuf = s.split(":");
        if (ubuf.length === 3) {
            if (ubuf[0] != "urn") { return false; }
            if (!check_shortID(ubuf[1])) { return false; }
            if (!check_shortID(ubuf[2])) { return false; }
            return true;
        } else {
            return false;
        }
    }
}

function check_localLang(s, n) {
    var exp = /^\{lang=[^\}]*\}/g;
    if (exp.test(s)) {
        exp.lastIndex = 0;
        var rlt = String(exp.exec(s));
        str = rlt.slice(6, -1);
        if ((str != "") && (str.length > 1)) {
            if (IANA.indexOf(str) != -1) { return (s.length - 7 - str.length <= n); }

            if ((str.charAt(0) == "x") && (str.charAt(1) == "-")) { return (s.length - 7 - str.length <= n); }
            if ((str.charAt(0) == "i") && (str.charAt(1) == "-")) { return (s.length - 7 - str.length <= n); }

            var buf = str.split("-");
            if ((buf.length == 0) || (buf.length > 2)) { return false; }
            if (buf.length == 1) {
                if (buf[0].length == 2) {
                    if (/[^[a-z][A-Z]]/.test(buf[0])) { return false; }
                    if (buf[0] == "sp") { return false; }
                    return true;
                } else if (buf[0].length === 3) {
                    if (/[^[a-z][A-Z]]/.test(buf[0])) { return false; }
                    if ((buf[0] == "frl") || (buf[0] == "exg") || (buf[0] == "ruq")) {
                        return false;
                    }
                    return (s.length - 7 - str.length <= n);
                } else {
                    return false;
                }
            } else if (buf.length === 2) {
                if (buf[0].length === 2) {
                    if (/[^[a-z][A-Z]]/.test(buf[0])) { return false; }
                    if (buf[0] == "sp") { return false; }
                } else if (buf[0].length === 3) {
                    if (/[^[a-z][A-Z]]/.test(buf[0])) { return false; }
                    if ((buf[0] == "scn") || (buf[0] == "frl") || (buf[0] == "exg") || (buf[0] == "ruq")) {
                        return false;
                    }
                }
                if (buf[1].length === 2) {
                    if (/[^[a-z][A-Z]]/.test(buf[0])) {
                        return false;
                    }
                    return (s.length - 7 - str.length <= n);
                } else if ((2 < buf[1].length) && (buf[1].length < 9)) {
                    var j, code;
                    for (j = 0; j < buf[1].length; j++) {
                        code = buf[1].charCodeAt(j);
                        if (((64 < code) && (code < 91)) || ((96 < code) && (code < 123))) {
                        } else {
                            return false;
                        }
                    }
                    return (s.length - 7 - str.length <= n);
                } else {
                    return false;
                }
            }
        }
        return false;
    } else {
        return (s.length <= n);
    }
}

function check_shortID(s) {
    if (s == "") { return false; }

    var str16 = "0123456789AaBbCcDdEeFf";
    var slen = s.length;
    var i, code;
    for (i = 0; i < slen; i++) {
        code = s.charCodeAt(i);
        if (((32 < code) && (code < 91)) && (code != 60) && (code != 62)) {
        } else if (((94 < code) && (code < 123)) && (code != 96)) {
        } else if (code == 126) {
        } else if(code == 32) {
            if (i + 2 < slen) {
                if ((str16.indexOf(s.charCodeAt(i + 1)) == -1) || (str16.indexOf(s.charCodeAt(i + 2)) == -1)) {
                    return false;
                }
                i = i + 2;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    return true;
}

function check_truefalse(s) {
    return ((s == _T) || (s == _F));
}

function check_multiple(s) {
    if (s == "") { return true; }

    var buf = s.split("[,]");
    var i, j;
    var len = buf.length;

    for (i = 0; i < len; i++) {
        if (!check_shortID(buf[i])) { return false; }

        for (j = i + 1; j < len; j++) {
            if (buf[i] == buf[j]) { return false; }
        }
    }
    return true;
}

function replace_ptn(s, ptn_t, ptn_f) {
    if (ptn_t.test(s)) {
        s = s.replace(ptn_t, "");
    } else if (ptn_f.test(s)) {
        s = s.replace(ptn_f, "");
    }
    return s;
}

function check_fill_case_matters(s) {
    return replace_ptn(s, /\{case_matters=true\}/g, /\{case_matters=false\}/g);
}

function check_fill_order_matters(s) {
    return replace_ptn(s, /\{order_matters=true\}/g, /\{order_matters=false\}/g);
}

function check_fillin(s) {
    var cs = s;
    var buf = cs.split("[,]");

    for (i in buf) {
        if (buf[i].indexOf("{case_matters=") == 0) {
            if (buf[i].substring(0, 19) == "{case_matters=true}") {
                buf[i] = buf[i].substring(20);
            } else if (buf[i].substring(0, 20) == "{case_matters=false}") {
                buf[i] = buf[i].substring(21);
            } else {
                return false;
            }
        }

        if (buf[i].indexOf("{order_matters=") == 0) {
            if (buf[i].substring(0, 20) == "{order_matters=true}") {
                buf[i] = buf[i].substring(21);
            } else if (buf[i].substring(0, 21) == "{order_matters=false}") {
                buf[i] = buf[i].substring(22);
            } else {
                return false;
            }
        }

        if (!check_localLang(buf[i], 250)) { return false; }
    }
    return true;
}

function check_longfillin(s) {
    var cs = s;
    var exp = /^\{lang=([^\}]*)\}/g;
    var lang = null;

    if (exp.test(cs)) {
        cs = cs.replace(exp, "");
        lang = RegExp.$1;
    }

    if (cs.indexOf("{case_matters=") == 0) {
        if (cs.substring(0, 19) == "{case_matters=true}") {
            cs = cs.substring(20);
        } else if (cs.substring(0, 20) == "{case_matters=false}") {
            cs = cs.substring(21);
        } else {
            return false;
        }
    }

    if (lang) {
        cs = '{lang=' + lang + '}' + cs;
    }

    if (!check_localLang(cs, 4000)) { return false; }

    return true;
}

function check_likert(s) {
    return check_shortID(s);
}

function check_matching(s) {
    var buf = s.split("[,]");
    var i, tbuf;
    for (i in buf) {
        tbuf = buf[i].split("[.]");
        if (tbuf.length != 2) { return false; }
        if ((!check_shortID(tbuf[0])) || (!check_shortID(tbuf[1]))) { return false; }
    }
    return true;
}

function check_performance(s) {
    s = check_fill_order_matters(s);

    var buf = s.split("[,]");
    var i, tbuf;
    for (i in buf) {
        tbuf = buf[i].split("[.]");
        if (tbuf.length != 2) { return false; }
        if ((tbuf[0] == "") && (tbuf[1] == "")) {
            return true;
        } else if (tbuf[0] == "") {
            if (!check_shortID(tbuf[1])) { return false; }
        } else if(tbuf[1] == "") {
            if (!check_shortID(tbuf[0])) { return false; }
        } else {
            if ((!check_shortID(tbuf[0])) || (!check_shortID(tbuf[1]))) { return false; }
        }
    }
    return true;
}

function check_sequencing(s) {
    if (s == "") { return false; }

    var buf = s.split("[,]");
    var i;
    for (i in buf) {
        if (!check_shortID(buf[i])) { return false; }
    }
    return true;
}

function check_numeric(s) {
    var buf = s.split("[:]");
    if (buf.length === 2) {
        if ((buf[0] != "") && isNaN(buf[0])) { return false; }
        if ((buf[1] != "") && isNaN(buf[1])) { return false; }
        if ((buf[0] != "") && (buf[1] != "")) {
            if (eval(buf[0]) > eval(buf[1])) { return _setLastErrorR("406", false); }
        }
        return true;
    }
    return false;
}

function check_other(s) {
    s = check_fill_case_matters(s);

    if (s.indexOf("{case_matters=") >= 0) { return false; }
    if (check_localLang(s, 4000)) { return true; }

    return false;
}

function post_ResetFlag(m) {
    m = cmi_RepS(m);
    var n = node_selectSingleNode(ADP.SCO, m);
    if (n == null) return false;
    n.setAttribute("f_chg", "0");
    return true;
}

function post_GetMultiValue(m, member) {
    var pn, nodes, ntag, tname, str, cn, i, buf;

    m = cmi_RepS(m);
    pn = node_selectSingleNode(ADP.SCO, m);
    if (pn != null){
        nodes = pn.childNodes;
        ntag = "";

        for (i=0; i < nodes.length; i++) {
            tname = nodes.item(i).tagName;
            if (tname.match(/^i{1}\d+/) != null) {
                ntag += tname + ",";
            }
        }

        if (ntag != "") {
            ntag = ntag.substring(0, ntag.length - 1);
            buf = ntag.split(",").sort();
            str = "";
            for (i=0; i < buf.length; i++) {
                cn = member == "" ? node_selectSingleNode(pn, buf[i]) : node_selectSingleNode(pn, buf[i] + "/" + member);
                str = cn == null ? str + ";" : str + dom_get_node_text(cn) + ";";
            }
            return (str == "") ? "" : str.substring(0, str.length - 1);
        } else {
            return null;
        }
    } else {
        return null;
    }
}

function post_GetValue(m) {
    m = cmi_RepS(m);
    var node = node_selectSingleNode(ADP.SCO, m);
    return node === null ? "" : dom_get_node_text(node);
}

/////////////////////////////////////////////////////////////////////////

var ADP = null;

var _adlCommand = '';
var _suspend = false;
var _exit = false;

var _currentStatus = "Not Initialized";
var _lastError = "0";

var _T = "true";
var _F = "false";

var _E = new Array();
_E["0"]   = "No error";
_E["101"] = "General exception";
_E["102"] = "General Initialization Failure";
_E["103"] = "Already Initialized ";
_E["104"] = "Content Instance Terminated ";
_E["111"] = "General Termination Failure ";
_E["112"] = "Termination Before Initialization ";
_E["113"] = "Termination After Termination ";
_E["122"] = "Retrieve Data Before Initialization ";
_E["123"] = "Retrieve Data After Termination ";
_E["132"] = "Store Data Before Initialization ";
_E["133"] = "Store Data After Termination ";
_E["142"] = "Commit Before Initialization ";
_E["143"] = "Commit After Termination ";
_E["201"] = "General Argument Error ";
_E["301"] = "General Get Failure ";
_E["351"] = "General Set Failure ";
_E["391"] = "General Commit Failure ";

_E["401"] = "Undefined Data Model Element ";
_E["402"] = "Unimplemented Data Model Element ";
_E["403"] = "Data Model Element Value Not Initialized ";
_E["404"] = "Data Model Element Is Read Only ";
_E["405"] = "Data Model Element Is Write Only ";
_E["406"] = "Data Model Element Type Mismatch ";
_E["407"] = "Data Model Element Value Out Of Range  ";
_E["408"] = "Data Model Dependency Not Established  ";

var IANA = "art-lojban,az-Arab,az-Cyrl,az-Latn,cel-gaulish,de-1901,de-1996,de-AT-1901,de-AT-1996,de-CH-1901,de-CH-1996,de-DE-1901,de-DE-1996,en-boont,en-GB-oed,en-scouse,i-ami,i-bnn,i-default,i-enochian,i-hak,i-klingon,i-lux,i-mingo,i-navajo,i-pwn,i-tao,i-tay,i-tsu,no-bok,no-nyn,sgn-BE-fr,sgn-BE-nl,sgn-BR,sgn-CH-de,sgn-CO,sgn-DE,sgn-DK,sgn-ES,sgn-FR,sgn-GB,sgn-GR,sgn-IE,sgn-IT,sgn-JP,sgn-MX,sgn-NL,sgn-NO,sgn-PT,sgn-SE,sgn-US,sgn-ZA,sl-rozaj,sr-Cyrl,sr-Latn,uz-Cyrl,uz-Latn,yi-Latn,zh-gan,zh-guoyu,zh-hakka,zh-Hans,zh-Hant,zh-min,zh-min-nan,zh-wuu,zh-xiang";

function _getStatus() { return _currentStatus; }
function _setStatus(status) { _currentStatus = status; }

function _getLastError() { return _lastError; }
function _setLastError(errorCode) { _lastError = errorCode; }
function _setLastErrorR(errorCode, retVal) { _lastError = errorCode; return retVal; }

//public function
viewXML = function() {
    alert(ADP.SCO.xml);
}

//SCORM run-time API function
Initialize = function(s) {
    _setLastError("0");

    var status = _getStatus();
    var RUNNING = "Running";

    if (status == RUNNING) { return _setLastErrorR("103", _F); }

    ADP = new apiAdapter()
    if (ADP.InitializeAdapter()) {
        return _setLastErrorR("101", _F);
    }
    ADP.mrt = ADP.DM.documentElement;

    if (s != "") { return _setLastErrorR("201", _F); }
    if (ADP.InitializeSCO() == null) { return _setLastErrorR("101", _F); }

    _setStatus(RUNNING);
    _suspend = false;

    return _T;
}

//SCORM run-time API function
Terminate = function(s) {
    _setLastError("0");

    var status = _getStatus();
    if (status == "Not Initialized") { return _setLastErrorR("112", _F); }
    if (status == "Terminated")      { return _setLastErrorR("113", _F); }

    if (s != "") { return _setLastErrorR("201", _F); }

    if (ADP.TerminateSCO() == null) { return _F; }

    _setStatus("Terminated");
    top.ScormAPIAdapter.setTerminated();   // Notify the termination status to scormapiadapter.js

    var commandSaved = top.ScormAPIAdapter.commandSaved();
    if (commandSaved) {
        _adlCommand = '';
    }

    if (_exit) { _adlCommand = 'exitAll'; }

    if ((_adlCommand != '') && (_adlCommand != '_none_')) {
        var cmd = '';

        if      (_adlCommand == 'continue')                      { cmd = 'CONTINUE'; }
        else if (_adlCommand == 'previous')                      { cmd = 'PREVIOUS'; }
        //else if (_adlCommand == 'exit')                          { cmd = 'EXIT'; }
        else if (_adlCommand == 'exitAll')                       { cmd = 'EXITALL'; }
        //else if (_adlCommand == 'abandon')                       { cmd = 'ABANDON'; }
        //else if (_adlCommand == 'abandonAll')                    { cmd = 'ABANDONALL'; }
        else if (_adlCommand == 'suspendAll')                    { cmd = 'SUSPEND'; }
        else if (_adlCommand.match(/^\{target=(.+)\}choice$/)) { cmd = "CHOICE&VAL=" + RegExp.$1; }

        if (cmd === '') { return _F; }

        top.ScormAPIAdapter.doNavigationCommand(cmd);
        return _T;
    }

    top.ScormAPIAdapter.doSavedNavigationCommand(); // Do navigation command if it saved

    return _T;
}

//SCORM run-time API function
GetValue = function(m) {
    _setLastError("0");

    m = scoutil_replace_learner_preference(m);

    var status = _getStatus();
    if (status == "Not Initialized") { return _setLastErrorR("122", ""); }
    if (status == "Terminated")      { return _setLastErrorR("123", ""); }

    if (typeof(m) == "undefined") { return _setLastErrorR("301", ""); }
    if (m == "")                  { return _setLastErrorR("301", ""); }

    if (m == "cmi._version") { return "1.0"; }

    var buf, kw, str;
    buf = m.split(".");
    if (buf.length == 1) {
        return _setLastErrorR("401", "");
    } else {
        if (buf[0] != "cmi") {
            if ((buf[0] == "adl") && (buf[1] == "nav")) {
                if (m == 'adl.nav.request') {
                    return (_adlCommand != '') ? _adlCommand : '_none_';
                }
                else if (m.match(/^adl\.nav\.request_valid\.(.+)$/)) {
                    return top.ScormAPIAdapter.getRequestValid(RegExp.$1);  // scormapiadapter.js
                }
            } else {
                return _setLastErrorR("401", "");
            }
        }
        kw = buf[buf.length - 1];

        if (kw == "_children") {
            if ((buf.length > 1) && (buf[buf.length - 2] == "n")) { return _setLastErrorR("401", ""); }
            ret = ADP.cmi_CheckChildren(m);
            if (ret == null || ret == "") { return _setLastErrorR("301", "");}
            return ret;
        } else if (kw == "_version") {
            if (m == "cmi._version") { return _setLastErrorR("201", _F); }
            return "";
        } else if (kw == "_count") {
            ret = ADP.cmi_CheckCount(m);
            if (ret == null)
                _setLastError("301");
            else if(ret)
                return String(ADP.sco_GetCount(m));
            else
                _setLastError("301");
            return "";
        } else {
            ret = ADP.cmi_CheckData(m, 0);
            if (ret == null) { return _setLastErrorR("401", ""); }
            if (ret) {
                str = ADP.sco_GetValue(m);
                if (str === null) {
                    var gpath = cmi_RepG(m);
                    if (gpath.indexOf("/n/") != -1) {
                        var spath = cmi_RepS(m);
                        var value = null;
                        var wbuf;
                        if (gpath.indexOf("cmi/objectives/n/score") != -1) {
                            wbuf = spath.split("/");
                            wbuf[wbuf.length - 1] = "";
                            wbuf[wbuf.length - 2] = "";
                            spath = wbuf.join("/");
                            spath = spath.slice(0, -2);
                            value = ADP.sco_GetText(spath);
                        } else {
                            wbuf = spath.split("/");
                            wbuf[wbuf.length - 1] = "";
                            spath = wbuf.join("/");
                            spath = spath.slice(0, -1);
                            value = ADP.sco_GetText(spath);
                        }

                        if (value == null) {
                            _setLastError("301");
                        } else {
                            if (gpath == "/cmi/objectives/n/success_status") { return "unknown"; }
                            if (gpath == "/cmi/objectives/n/completion_status") { return "unknown"; }
                            _setLastError("403");
                        }
                    } else {
                        _setLastError("403");
                    }
                    return "";
                } else {
                    str = str.replace(/&nbsp;/g, " ");
                    return str;
                }
            } else {
                return _setLastErrorR("405", "");
            }
        }
    }
}

//SCORM run-time API function
SetValue = function(m, s) {
    _setLastError("0");

    s = String(s);
    m = scoutil_replace_learner_preference(m);

    var status = _getStatus();
    if (status == "Not Initialized") { return _setLastErrorR("132", _F); }
    if (status == "Terminated")      { return _setLastErrorR("133", _F); }

    if (typeof(m) == "undefined") { return _setLastErrorR("351", _F); }
    if (m == "")                  { return _setLastErrorR("351", _F); }

    var buf, i, kw, str, cw;
    buf = m.split(".");

    if (buf.length === 1) {
        _setLastError("401");
    } else {
        if (buf[0] != "cmi") {
            if ((buf[0] == "adl") && (buf[1] == "nav")) {
                if (m == "adl.nav.request") {
                    _adlCommand = s;
                    return _T;
                } else {
                    return _F;
                }
            } else {
                return _setLastErrorR("401", _F);
            }
        }

        kw = buf[buf.length - 1];
        if ((kw == "_children") || (kw == "_version") || (kw == "_count")) {
            return _setLastErrorR("404", _F);
        } else {
            ret = ADP.cmi_CheckData(m, 1);
            if (ret === null) {
                return _setLastErrorR("401", _F);
            } else if (ret) {
                    var tStr = cmi_RepG(m);
                    if (tStr.indexOf("/n/") != -1) {
                        if ((tStr == "/cmi/interactions/n/objectives/n/id") || (tStr == "/cmi/interactions/n/correct_responses/n/pattern")) {
                            t1 = cmi_RepS2(m, true);
                            if (t1 == "") {
                            } else {
                                if (!ADP.sco_Exists(t1)) { return _setLastErrorR("351", _F); }
                            }

                            t1 = cmi_RepS2(m, false);
                            if (t1 == ""){
                                ;
                            } else {
                                if (!ADP.sco_Exists(t1)) { return _setLastErrorR("351", _F); }
                            }
                        } else {
                            t1 = cmi_RepS2(m, true);
                            if (t1 == "") {
                            } else {
                                if (!ADP.sco_Exists(t1)) { return _setLastErrorR("351", _F); }
                            }
                        }
                    }

                    if (tStr == "/cmi/objectives/n/id") {
                        if (ADP.sco_SetObjID(m, s)) { return _setLastErrorR("351", _F); }
                    }

                    if (tStr.indexOf("/cmi/objectives/") != -1) {
                        if (tStr != "/cmi/objectives/n/id") {
                            tt1 = cmi_RepS3(m, "objectives", "id");
                            if (!ADP.sco_Exists(tt1)) { return _setLastErrorR("408", _F); }
                        }
                    }

                    if (tStr.indexOf("/cmi/interactions/") != -1) {
                        if (tStr != "/cmi/interactions/n/id") {
                            tt1 = cmi_RepS3(m, "interactions", "id");
                            if (!ADP.sco_Exists(tt1)) { return _setLastErrorR("408", _F); }
                        }
                    }

                    if (tStr == "/cmi/interactions/n/objectives/n/id") {
                        if (ADP.sco_SetObjID(m, s)) { return _setLastErrorR("351", _F); }
                    }

                    if ((tStr == "/cmi/interactions/n/correct_responses/n/pattern") || (tStr == "/cmi/interactions/n/learner_response")) {
                        var cfg = (tStr == "/cmi/interactions/n/correct_responses/n/pattern");

                        t1 = cmi_RepS3(m, "interactions", "type");
                        if (!ADP.sco_Exists(t1)) { return _setLastErrorR("408", _F); }

                        var cary = (tStr == "/cmi/interactions/n/correct_responses/n/pattern");

                        var mct = 0;
                        if (cary) {
                            mdbuf = m.split(".");
                            mct = parseInt(mdbuf[4]);
                        }

                        qtp = ADP.sco_GetText(t1);

                        if (qtp == "true-false") {
                            if (cary && (0 < mct)) { return _setLastErrorR("351", _F); }
                            if (!check_truefalse(s)) { return _setLastErrorR("406", _F); }
                        } else if (qtp == "choice") {
                            if (!check_multiple(s)) { return _setLastErrorR("406", _F); }
                            if (cary && 9 < mct)  { return _setLastErrorR("351", _F); }
                            if (cary && ADP.sco_Double(m, mct, s)) { return _setLastErrorR("351", _F); }
                        } else if (qtp == "fill-in") {
                            if (!check_fillin(s)) { return _setLastErrorR("406", _F); }
                        } else if (qtp == "long-fill-in") {
                            if (!check_longfillin(s)) { return _setLastErrorR("406", _F); }
                        } else if (qtp == "likert") {
                            if (!check_likert(s)) { return _setLastErrorR("406", _F); }
                        } else if (qtp == "matching") {
                            if (!check_matching(s)) { return _setLastErrorR("406", _F); }
                            if (cary && 4 < mct)  { return _setLastErrorR("351", _F); }
                        } else if (qtp == "performance") {
                            if (!check_performance(s)) { return _setLastErrorR("406", _F); }
                        } else if (qtp == "sequencing") {
                            if (!check_sequencing(s)) { return _setLastErrorR("406", _F); }
                            if (cary && ADP.sco_Double(m, mct, s)) { return _setLastErrorR("406", _F); }
                        } else if (qtp == "numeric") {
                            if (cary) {
                                if (0 < mct) { return _setLastErrorR("351", _F); }
                                if (!check_numeric(s)) {
                                    if (_getLastError() == "0") { _setLastError("406"); }
                                    return _F;
                                }
                            } else {
                                if (isNaN(s)) { return _setLastErrorR("406", _F); }
                            }
                        } else if (qtp == "other") {
                            if (!check_other(s)) { return _setLastErrorR("406", _F); }
                        } else {
                            return _setLastErrorR("406", _F);
                        }
                    } else if (!ADP.cmi_CheckValue(ret, s, m)) {
                        if (_getLastError() == "0") { _setLastError("406"); }
                        return _F;
                    }

                    s = s.replace(/\s/g, "&nbsp;");
                    ADP.sco_SetValue(m, s);

                    if (m == "cmi.success_status" || m == "cmi.score.scaled") {
                        //REQ_77.5
                        var passing_score = ADP.sco_GetValue("cmi.scaled_passing_score");
                        var scaled_score = (m == "cmi.score.scaled") ? s : ADP.sco_GetValue("cmi.score.scaled");
                        if (!isNaN(passing_score) && passing_score !== null) {
                            if (!isNaN(scaled_score) && scaled_score !== null) {
                                if (Number(passing_score) > Number(scaled_score)) {
                                    ADP.sco_SetValue("cmi.success_status", "failed");
                                } else {
                                    ADP.sco_SetValue("cmi.success_status", "passed");
                                }
                            } else {
                                ADP.sco_SetValue("cmi.success_status", "unknown");
                            }
                        }
                    }

                    if (m == "cmi.completion_status" || m == "cmi.progress_measure") {
                        //REQ_59.5
                        var completion_threshold = ADP.sco_GetValue("cmi.completion_threshold");
                        var progress_measure = (m == "cmi.progress_measure") ? s : ADP.sco_GetValue("cmi.progress_measure");
                        if (!isNaN(completion_threshold) && completion_threshold !== null) {
                            if (!isNaN(progress_measure) && progress_measure !== null) {
                                if (Number(completion_threshold) > Number(progress_measure)) {
                                    ADP.sco_SetValue("cmi.completion_status", "incomplete");
                                } else {
                                    ADP.sco_SetValue("cmi.completion_status", "completed");
                                }
                            } else {
                                ADP.sco_SetValue("cmi.completion_status", "unknown");
                            }
                        }
                    }

                    if (m == "cmi.exit") {
                        if (s == "")         { _suspend = false; _exit = false; }
                        if (s == "normal")   { _suspend = false; _exit = false; }
                        if (s == "suspend")  { _suspend = true;  _exit = false; }
                        if (s == "logout")   { _suspend = false; _exit = true; } //REQ_63.4.3
                        if (s == "time-out") { _suspend = false; _exit = true; } //REQ_63.4.1
                    }

                    ADP.write = 1;
                    return _T;
            } else {
                return _setLastErrorR("404", _F);
            }
        }
    }
    return _F;
}

//SCORM run-time API function
Commit = function(s) {
    _setLastError("0");

    if (s != "") { return _setLastErrorR("201", _F); } //REQ_3.2

    var status = _getStatus();
    var S0 = "Not Initialized";
    var S1 = "Terminated";
    if (status == S0) { return _setLastErrorR("142", _F); }
    if (status == S1) { return _setLastErrorR("143", _F); }

    var r1 = ADP.sco_Commit(0);

    return (r1 == null) ? _F : _T;
}

//SCORM run-time API function
GetLastError = function() {
    return _getLastError();
}

//SCORM run-time API function
GetErrorString = function(s) {
    var result = _E[s];
    return result ? result : "";
}

//SCORM run-time API function
GetDiagnostic = function() {
    return "error";
}

version = "1.0";

})();
