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

// パンくず
$PAGE->set_url('/mod/'.elecoa_get_module_path_name().'/view.php', array('id' => $cm->id));
$PAGE->set_title($elecoa->name);
$PAGE->set_heading($course->shortname);

$PAGE->set_cacheable(FALSE);
$PAGE->set_pagelayout('frametop'); // performance reason

// APIプロバイダコンテンツ情報
$provider_contents = $provider->getCDObjects();

// HTMLヘッダ
if (is_mobile()) {
    $CFG->additionalhtmlhead .= '<link rel="stylesheet" href="./css/mobile.css" />' . "\n";
}
else {
    $CFG->additionalhtmlhead .= '<link rel="stylesheet" href="./css/default.css" />' . "\n";
}

$provider_verparam = array_key_exists('version', $provider_contents) ? '&amp;' . urlencode($provider_contents['version']) : '';
foreach ($provider_contents['stylesheets'] as $stylesheet) {
    $CFG->additionalhtmlhead .= '<link rel="stylesheet" href="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;src=' . urlencode($stylesheet) . $provider_verparam . '" />' . "\n";
}
$CFG->additionalhtmlhead .= '<link rel="stylesheet" href="./css/moodle-override.css" />' . "\n";

// 目次領域の表示非表示等を取得
$class = is_mobile() ? 'nav-close' : 'nav-open';
if (isset($_COOKIE['elecoa-nav-openclose']) && ($_COOKIE['elecoa-nav-openclose'] === 'close')) {
    $class = 'nav-close';
}

// レンダリング
echo $OUTPUT->header();

echo '<script>'
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
. 'var modulePathName = "' . addslashes(elecoa_get_module_path_name()) . '";'
. '</script>' . "\n";

echo '<script src="./js/core.js"></script>' . "\n";
echo '<script src="./js/jquery-3.3.1.min.js"></script>' . "\n";
echo '<script src="./js/jquery-ui.custom.min.js"></script>' . "\n";
if (is_mobile()) {
    echo '<script src="./js/frameset-layout-mobile.js"></script>' . "\n";
}
else {
    echo '<script src="./js/frameset-layout.js"></script>' . "\n";
}
echo '<script src="./js/frameset-objects.js"></script>' . "\n";
echo '<script src="./js/container.js"></script>' . "\n";

foreach ($provider_contents['javascripts'] as $javascript) {
    echo '<script src="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;src=' . urlencode($javascript) . $provider_verparam . '"></script>' . "\n";
}

echo '<div id="ELECOA_CONTAINER">';
echo  '<div id="ELECOA_NAV" class="' . $class . '">';
echo   '<div id="ELECOA_NAVCLOSEBOX"><span id="elecoa-nav-closebox"></span></div>';
echo   '<div id="ELECOA_INDEXTITLE">&nbsp;</div>';
echo   '<div id="ELECOA_INDEXTREEVIEWCONTAINER">';
echo    '<div id="ELECOA_INDEXTREEVIEW"></div>';
echo   '</div>';
echo  '</div>';
echo  '<div id="ELECOA_SEPARATOR" class="' . $class . '"></div>';
echo  '<div id="ELECOA_MAINCONTAINER" class="' . $class . '">';

if (is_mobile()) {
    echo '<object type="text/html" data="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN"></object>';
    output_navigation_buttons(TRUE);
}
else {
    output_navigation_buttons(FALSE);
    if (use_object_tag()) {
        echo '<object type="text/html" data="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN"></object>';
    }
    else {
        echo '<iframe src="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN"></iframe>';
    }
}

// APIアダプタプロバイダから追加HTMLを取得して出力
foreach ($provider_contents['apiobjects'] as $apiobject) {
    if (use_object_tag()) {
        echo '<object type="text/html" data="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;name=' . urlencode($apiobject['name']) . $provider_verparam . '" id="' . htmlspecialchars($apiobject['name']) . '" name="' . htmlspecialchars($apiobject['name']) . '" style="display:inline-block; height:0; width:1%; border-style:none;"></object>';
    }
    else {
        echo '<iframe src="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;name=' . urlencode($apiobject['name']) . $provider_verparam . '" id="' . htmlspecialchars($apiobject['name']) . '" name="' . htmlspecialchars($apiobject['name']) . '" style="display:inline-block; height:0; width:1%; border-style:none;"></iframe>';
    }
}
echo  '</div>';
echo '</div>';

echo $OUTPUT->footer();


/**
 * ナビゲーションボタンを出力する。
 * @param boolean $is_mobile モバイル版かどうか
 */
function output_navigation_buttons($is_mobile) {
    $strings = elecoa_get_strings(array('previous', 'continue', 'suspendall', 'exitall'));
    echo '<div class="elecoa-nav-button-container">';
    echo   '<div class="elecoa-nav-button-container-left">';
    echo     '<a href="javascript:top.Frameset.goPrevious();" id="link-nav-previous" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-mark">&#x25C0;</span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-text" id="link-nav-previous-text">' . htmlspecialchars($strings->previous) . '</span></a>' . "\n";
    echo     '<a href="javascript:top.Frameset.goContinue();" id="link-nav-continue" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-text" id="link-nav-continue-text">' . htmlspecialchars($strings->continue) . '</span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-mark">&#x25B6;</span></a>' . "\n";
    echo   '</div>';
    echo   '<div class="elecoa-nav-button-container-right">';
    echo     '<a href="javascript:top.Frameset.suspend();" id="link-nav-suspend" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-mark"><img src="./images/pause.png" alt="pause" /></span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-text" id="link-nav-suspend-text">' . htmlspecialchars($strings->suspendall) . '</span></a>' . "\n";
    echo     '<a href="javascript:top.Frameset.exitAll();" id="link-nav-exitall" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-mark">&#x25A0;</span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-text" id="link-nav-exitall-text">' . htmlspecialchars($strings->exitall) . '</span></a>' . "\n";
    echo   '</div>';
    echo '</div>';
}
