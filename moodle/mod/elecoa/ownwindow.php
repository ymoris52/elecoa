<?php
require_once(dirname(__FILE__) . '/core/init_www.php');

// パラメータの取得
$id = required_param('id', PARAM_INT);
$nextid = required_param('NextID', PARAM_ALPHANUMEXT);
if (!$id) {
    elecoa_print_error('invalidframesetparam');
}
if ($nextid === '') {
    elecoa_print_error('error');
}

// コースモジュールの取得
list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($id);
$context = context_module::instance($cm->id);

// ログインチェック
require_login($course, TRUE, $cm);

// 初期化チェック
if (!elecoa_session_has_data($elecoa->id)) {
    elecoa_print_error('error');
}

// アクティビティオブジェクトの取得
$activities = elecoa_session_get_activities($elecoa->id);
$next_activity_index = find_activity_by_id($activities, $nextid);
if ($next_activity_index === FALSE) {
    elecoa_print_error('error');
}

$elecoa_context = elecoa_session_get_context($elecoa->id);
$objectives = elecoa_session_get_objectives($elecoa->id);
colib::dynamicAppend($elecoa_context, $activities, $objectives);
elecoa_session_set_data($elecoa->id, $activities, $nextid, $objectives, $elecoa_context);

$provider = $activities[$next_activity_index]->getAPIAdapterProvider();
$url = $activities[$next_activity_index]->getURL();
$resource_identifier = $activities[$next_activity_index]->getResourceIdentifier();
$type = get_class($activities[$next_activity_index]);
elecoa_session_set_currentid($elecoa->id, $nextid);

// APIプロバイダコンテンツ情報
$provider_contents = $provider->getCDObjects();
$provider_verparam = array_key_exists('version', $provider_contents) ? '&amp;' . urlencode($provider_contents['version']) : '';

$class = 'nav-close';

echo '<!DOCTYPE html>' . "\n";
echo '<html lang="en">' . "\n";
echo ' <head>' . "\n";
echo '  <meta charset="utf-8">' . "\n";
echo '  <title></title>' . "\n";
echo '  <script>'
       . 'var baseUrl = "' . $CFG->wwwroot . '"; '
       . 'var cid = ' . $course->id . '; '
       . 'var content_id = ' . $elecoa->id . '; '
       . 'var cmid = ' . $cm->id . '; '
       . 'var contextID = ' . $context->id . '; '
       . 'var contentUrl = "' . $url . '"; '
       . 'var item_identifier = "' . addslashes($nextid) . '"; '
       . 'var resource_identifier = "' . addslashes($resource_identifier) . '"; '
       . 'var dialog_message = "' . elecoa_get_string('dialogmessage') . '"; '
       . 'var contentType = "' . $type . '"; '
       . 'var userID = ' . $USER->id . '; '
       . 'var userName = "' . addslashes($USER->lastname . ',' . $USER->firstname) . '"; '
       . 'var moduleName = "' . addslashes(elecoa_get_module_name()) . '"; '
       . 'var modulePathName = "' . addslashes(elecoa_get_module_path_name()) . '"; '
       . '</script>' . "\n";

echo '  <script src="./js/core.js"></script>' . "\n";
echo '  <script>top.Core = new ElecoaCore();</script>' . "\n";

foreach ($provider_contents['javascripts'] as $javascript) {
    echo '  <script src="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;src=' . urlencode($javascript) . $provider_verparam . '"></script>' . "\n";
}

echo ' </head>' . "\n";
echo ' <body>' . "\n";
echo '  <div id="ELECOA_CONTAINER">' . "\n";
echo '   <div id="ELECOA_NAV" class="' . $class . '">' . "\n";
echo '    <div id="ELECOA_NAVCLOSEBOX"><span id="elecoa-nav-closebox"></span></div>' . "\n";
echo '    <div id="ELECOA_INDEXTITLE">&nbsp;</div>' . "\n";
echo '    <div id="ELECOA_INDEXTREEVIEWCONTAINER">' . "\n";
echo '     <div id="ELECOA_INDEXTREEVIEW"></div>' . "\n";
echo '    </div>' . "\n";
echo '   </div>' . "\n";
echo '   <div id="ELECOA_SEPARATOR" class="' . $class . '"></div>' . "\n";
echo '   <div id="ELECOA_MAINCONTAINER" class="' . $class . '">' . "\n";

if (is_mobile()) {
    echo '    <object type="text/html" data="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN" style="width:99%; border-style:none;"></object>' . "\n";
} else {
    if (use_object_tag()) {
        echo '    <object type="text/html" data="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN" style="width:100%; border-style:none;"></object>' . "\n";
    } else {
        echo '    <iframe src="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN" style="width:99%; border-style:none;"></iframe>' . "\n";
    }
}

// APIアダプタプロバイダから追加HTMLを取得して出力
foreach ($provider_contents['apiobjects'] as $apiobject) {
    if (use_object_tag()) {
        echo '    <object type="text/html" data="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;name=' . urlencode($apiobject['name']) . $provider_verparam . '" id="' . htmlspecialchars($apiobject['name']) . '" name="' . htmlspecialchars($apiobject['name']) . '" style="display:inline-block; height:0; width:1%; border-style:none;"></object>' . "\n";
    } else {
        echo '    <iframe src="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;name=' . urlencode($apiobject['name']) . $provider_verparam . '" id="' . htmlspecialchars($apiobject['name']) . '" name="' . htmlspecialchars($apiobject['name']) . '" style="display:inline-block; height:0; width:1%; border-style:none;"></iframe>' . "\n";
    }
}

echo '   </div>' . "\n";
echo '  </div>' . "\n";
echo '  <script>' . "\n";
echo '   document.addEventListener("DOMContentLoaded", function(e) {' . "\n";
echo '    top.Core.onInitialized();' . "\n";
echo '    var results = top.Core.sendCommand("GET_INTERFACE_DATA&VAL=WITH_INITRTM"); top.Core.onBeforeLoadContent(null, results.commandResultArray);' . "\n";
echo '    var targetDocument = top.ELECOA_MAIN;' . "\n";
echo '    if (!targetDocument.location) {' . "\n";
echo '     targetDocument = targetDocument.contentDocument;' . "\n";
echo '    }' . "\n";
echo '    targetDocument.location.href = top.baseUrl + "/pluginfile.php/" + top.contextID + "/" + encodeURIComponent(top.moduleName) + "/content/0/" + top.contentUrl;' . "\n";
echo '   });' . "\n";
echo '  </script>' . "\n";
echo ' </body>' . "\n";
echo '</html>' . "\n";
