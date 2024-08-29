<?php
require_once(dirname(__FILE__) . '/../init_www.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect();
}
session_check();

// パラメータの取得
if (!isset($_POST['cid'])) {
	error();
}
$cid = $_POST['cid'];
if ($cid === '.') {
    error('Please select a content to start.');
}
if ($cid === '' or preg_match('/[^A-Za-z0-9_-]/', $cid)) {
    error();
}
$resume_option = '0';
if (isset($_POST['res'])) {
	$resume_option = $_POST['res'];
}
$startid = "";
if (isset($_POST['sid'])) {
    $startid = $_POST['sid'];
}

// ログインチェック
if (!elecoa_session_loggedin()) {
    error();
}
$uid = elecoa_session_get_userid();

// ２重起動チェック
if (elecoa_session_has_data($cid)) {
    error('You seem to have started another (or the same) content already.');
}

// 基本コンテキストの作成
$resume = false;
$attempt_number = 1;

$log = getLogModule();
$lastattempt = $log->getLastAttempt($uid, $cid);
if ($resume_option === '1') {
    // resume
    $attempt_number = $lastattempt;
    $resume = true;
}
else if ($resume_option === '2') {
    // clear attempts
    $log -> clearAttempts($uid, $cid);
    $attempt_number = 1;
}
else {
    // new
    $attempt_number = $lastattempt + 1;
}

$elecoa_context = makeContext($uid, $cid, $attempt_number);

// 学習データ記録の準備
$doc = new DOMDocument();
if (!$doc->load(content_path . '/' . $cid . '/elecoa.xml')) {
    error();
}

$manifestNode = $doc->documentElement;

$activity_root_node = selectSingleDOMNode($manifestNode, 'item');
if (is_null($activity_root_node)) {
    error();
}
$sgo = !($activity_root_node->getAttribute('oGS') === 'false'); // adlseq:objectivesGlobalToSystem

if (!($log->makeLogReady($uid, $cid, $attempt_number, $sgo))) {
    error();
}

// 学習目標のインスタンス化
$objectives = array();
$objective_root_node = selectSingleDOMNode($manifestNode, 'objectives');
if (!is_null($objective_root_node)) {
    foreach (selectDOMNodes($objective_root_node, 'objective') as $objective_node) {
        $objective_node_id = $objective_node->getAttribute('id');
        $objective_node_cotype = $objective_node->getAttribute('coType');
        $objectives[$objective_node_id] = new $objective_node_cotype($elecoa_context, $objective_node_id, $objective_node, $resume, $sgo);
    }
}

// 階層構造のインスタンス化
$activities = array();
$activity_root_node_cotype = $activity_root_node->getAttribute('coType');
if ($activity_root_node_cotype === '') {
    error();
}
$maketree = function($node, $parent_node_index) use (&$maketree, &$activities, &$objectives, $resume, &$elecoa_context) {
    foreach (selectDOMNodes($node, 'item') as $child_node) {
        $child_node_cotype = $child_node->getAttribute('coType');
        $activities[] = new $child_node_cotype($elecoa_context, $parent_node_index, $child_node, $resume, $objectives);
        $child_node_index = count($activities) - 1;
        $activities[$parent_node_index]->addChild($child_node_index);
        if ($activities[$child_node_index]->getType() === 'BLOCK') {
            $maketree($child_node, $child_node_index);
        }
    }
};

$activities[] = new $activity_root_node_cotype($elecoa_context, -1, $activity_root_node, $resume, $objectives);
$maketree($activity_root_node, 0);

Platform::createInstance($activities, $objectives);
colib::dynamicAppend($elecoa_context, $activities, $objectives);

// 配信ノードの決定
// この直後 startmodule.js から READY コマンドが送られるが、その時にノードが決まっていないといけないため、このタイミングで実行
// mainmodule.php の中で READY コマンドの時に実行するでも良いかもしれない
if ($resume) {
    $current_node_id = $log->getCurrentIDForResumption($uid, $cid, $attempt_number);
    if ($current_node_id === false) {
        error();
    }
} else {
    $current_node_id = $activity_root_node->getAttribute('startID');
}

if ($current_node_id === '') {
    $results = $activities[0]->exeStart();
    if(isset($results['NextID']))
        $current_node_id = $results['NextID'];
    else
        $current_node_id = "";

} else {
    $current_node_index = find_activity_by_id($activities, $current_node_id);
    if ($current_node_index === false) {
        error();
    }
    if ($activities[$current_node_index]->getType() === 'BLOCK') {
        $results = $activities[$current_node_index]->exeStart();
        $current_node_id = $results['NextID'];
    }
}

// セッションにデータを保持
elecoa_session_set_data($cid, $activities, $current_node_id, $objectives, $elecoa_context);

// 出力（処理はstartmodule.jsで行なう）
echo '<!DOCTYPE html>' . "\n";
echo '<html lang="en">' . "\n";
echo ' <head>' . "\n";
echo '  <meta charset="utf-8">' . "\n";
echo '  <title></title>' . "\n";
echo '  <script>' . "\n";
echo '   document.write(\'<base href="' . preg_replace('/startmodule.php.*$/', 'startmodule.php', $_SERVER['REQUEST_URI']) . '">\');' . "\n";
echo '   document.write(\'<script>var content_id = "' . addslashes($cid) . '";<\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/core.js"><\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/startmodule.js"><\/script>\');' . "\n";
echo '  </script>' . "\n";
echo ' </head>' . "\n";
echo ' <body>' . "\n";
echo ' </body>' . "\n";
echo '</html>' . "\n";
