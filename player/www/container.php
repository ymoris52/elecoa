<?php
require_once(dirname(__FILE__) . '/../init_www.php');

session_start();

if (!elecoa_session_loggedin()) {
    error();
}

// パラメータの取得
if (!isset($_GET['cid'])) {
    error('Content ID is required.');
}
$contentid = $_GET['cid'];

if (!isset($_GET['NextID'])) {
    error('Next ID is required.');
}
$nextid = $_GET['NextID'];

// 初期化チェック
if (!elecoa_session_has_data($contentid)) {
    error('Invalid session.');
}

// アクティビティオブジェクトの取得
$activities = elecoa_session_get_activities($contentid);
$next_activity_index = find_activity_by_id($activities, $nextid);
if ($next_activity_index === false) {
    error('No activity specified.');
}

$elecoa_context = elecoa_session_get_context($contentid);
$objectives = elecoa_session_get_objectives($contentid);
colib::dynamicAppend($elecoa_context, $activities, $objectives);
elecoa_session_set_data($contentid, $activities, $nextid, $objectives, $elecoa_context);

$provider = $activities[$next_activity_index]->getAPIAdapterProvider();
$url = $activities[$next_activity_index]->getURL();
$resource_identifier = $activities[$next_activity_index]->getResourceIdentifier();
$type = get_class($activities[$next_activity_index]);
elecoa_session_set_currentid($contentid, $nextid);

// APIプロバイダコンテンツ情報
$provider_contents = $provider->getCDObjects();

// 目次領域の表示非表示等を取得
$class = 'nav-open';
if (isset($_COOKIE['elecoa-nav-openclose']) && ($_COOKIE['elecoa-nav-openclose'] === 'close')) {
    $class = 'nav-close';
}
$nav_style = '';
$main_style = '';
if ($class === 'nav-open') {
    if (isset($_COOKIE['elecoa-nav-width']) && (is_numeric($_COOKIE['elecoa-nav-width']))) {
        $nav_style = ' style="width:' . $_COOKIE['elecoa-nav-width'] . 'px;"';
        $main_style = ' style="margin-left:' . ($_COOKIE['elecoa-nav-width'] + 20) . 'px;"';
    }
}

// レンダリング
echo '<!DOCTYPE html>' . "\n";
echo '<html lang="en">' . "\n";
echo ' <head>' . "\n";
echo '  <meta charset="utf-8">' . "\n";
echo '  <title>ELECOA Player</title>' . "\n";
echo '  <link rel="stylesheet" href="./css/default.css">' . "\n";
echo '  <link rel="stylesheet" href="./css/elecoa.css">' . "\n";
echo '  <script>' . "\n";
echo '   var baseUrl = "' . web_base_path . '";' . "\n";
echo '   var content_id = "' . addslashes($contentid) . '";' . "\n";
echo '   var contentUrl = "' . $url . '";' . "\n";
echo '   var item_identifier = "' . addslashes($nextid) . '";' . "\n";
echo '   var resource_identifier = "' . addslashes($resource_identifier) . '";' . "\n";
echo '   var dialog_message = "finish automatically...";' . "\n";
echo '   var contentType = "' . $type . '";' . "\n";
echo '   var userID = "' . addslashes(elecoa_session_get_userid() ). '";' . "\n";
echo '   var userName = "' . addslashes(elecoa_session_get_username()) . '";' . "\n";
echo '  </script>' . "\n";
echo '  <script src="./js/core.js"></script>' . "\n";
echo '  <script src="./js/jquery-3.3.1.min.js"></script>' . "\n";
echo '  <script src="./js/jquery-ui.custom.min.js"></script>' . "\n";
echo '  <script src="./js/frameset-layout.js"></script>' . "\n";
echo '  <script src="./js/frameset-objects.js"></script>' . "\n";
echo '  <script src="./js/container.js"></script>' . "\n";

// APIアダプタプロバイダ追加ヘッダを出力
$provider_verparam = array_key_exists('version', $provider_contents) ? '&amp;' . urlencode($provider_contents['version']) : '';
foreach ($provider_contents['javascripts'] as $javascript) {
    echo '  <script src="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;src=' . urlencode($javascript) . $provider_verparam . '"></script>' . "\n";
}
foreach ($provider_contents['stylesheets'] as $stylesheet) {
    echo '  <link rel="stylesheet" href="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;src=' . urlencode($stylesheet) . $provider_verparam . '" />' . "\n";
}

echo ' </head>' . "\n";
echo ' <body>' . "\n";

echo '  <h1>ELECOA Player</h1>' . "\n";
echo '  <div id="ELECOA_CONTAINER">' . "\n";
echo '   <div id="ELECOA_NAV" class="' . $class . '"' . $nav_style . '>' . "\n";
echo '    <div id="ELECOA_NAVCLOSEBOX"><span id="elecoa-nav-closebox"></span></div>' . "\n";
echo '    <div id="ELECOA_INDEXTITLE">&nbsp;</div>' . "\n";
echo '    <div id="ELECOA_INDEXTREEVIEWCONTAINER">' . "\n";
echo '     <div id="ELECOA_INDEXTREEVIEW"></div>' . "\n";
echo '    </div>' . "\n";
echo '   </div>' . "\n";
echo '   <div id="ELECOA_SEPARATOR" class="' . $class . '"></div>' . "\n";
echo '   <div id="ELECOA_MAINCONTAINER" class="' . $class . '"' . $main_style . '>' . "\n";

output_navigation_buttons();
if (use_object_tag()) {
    echo '    <object type="text/html" data="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN"></object>' . "\n";
}
else {
    echo '    <iframe src="./blank.html" id="ELECOA_MAIN" name="ELECOA_MAIN"></iframe>' . "\n";
}

// APIアダプタプロバイダから追加HTMLを取得して出力
foreach ($provider_contents['apiobjects'] as $apiobject) {
    if (use_object_tag()) {
        echo '    <object type="text/html" data="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;name=' . urlencode($apiobject['name']) . $provider_verparam . '" id="' . htmlspecialchars($apiobject['name']) . '" name="' . htmlspecialchars($apiobject['name']) . '" style="display:inline-block; height:0; width:1%; border-style:none;"></object>' . "\n";
    }
    else {
        echo '    <iframe src="./api.php?type=' . urlencode($provider->getAdapterName()) . '&amp;name=' . urlencode($apiobject['name']) . $provider_verparam . '" id="' . htmlspecialchars($apiobject['name']) . '" name="' . htmlspecialchars($apiobject['name']) . '" style="display:inline-block; height:0; width:1%; border-style:none;"></iframe>' . "\n";
    }
}
echo '   </div>' . "\n";
echo '  </div>' . "\n";
echo ' </body>' . "\n";
echo '</html>' . "\n";

/**
 * ナビゲーションボタンを出力する。
 */
function output_navigation_buttons() {
    echo '    <div class="elecoa-nav-button-container">' . "\n";
    echo '     <div class="elecoa-nav-button-container-left">' . "\n";
    echo '      <a href="javascript:top.Frameset.goPrevious();" id="link-nav-previous" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-mark">&#x25C0;</span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-text" id="link-nav-previous-text">Previous</span></a>' . "\n";
    echo '      <a href="javascript:top.Frameset.goContinue();" id="link-nav-continue" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-text" id="link-nav-continue-text">Continue</span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-mark">&#x25B6;</span></a>' . "\n";
    echo '     </div>' . "\n";
    echo '     <div class="elecoa-nav-button-container-right">' . "\n";
    echo '      <a href="javascript:top.Frameset.suspend();" id="link-nav-suspend" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-mark"><img src="./images/pause.png" alt="pause" /></span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-text" id="link-nav-suspend-text">Suspend All</span></a>' . "\n";
    echo '      <a href="javascript:top.Frameset.exitAll();" id="link-nav-exitall" class="elecoa-nav-button" style="display:none;"><span class="elecoa-nav-button-mark">&#x25A0;</span><span class="elecoa-nav-button-space"></span><span class="elecoa-nav-button-text" id="link-nav-exitall-text">Exit All</span></a>' . "\n";
    echo '     </div>' . "\n";
    echo '    </div>' . "\n";
}
