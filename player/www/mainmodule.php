<?php
require_once dirname(__FILE__) . "/../init_www.php";

// 内容のチェックはせずに代入
$cid = @$_GET['CID'];
$cmd = @$_GET['CMD'];   // コマンド
$val = @$_GET['VAL'];   // 値

$rtm = file_get_contents('php://input');
if (!isset($rtm)) {
    $rtm = '';
}

// グローバル変数
$activities = null;
$current = null;
$objectives = null;
$context = null;

if ($cmd === null) {
    elecoa_error_message("コマンド情報がありません。");
    return;
}

session_start();

if (!elecoa_session_has_data($cid)) {
    elecoa_error_message("セッション情報がありません。");
    return;
}

// コマンドの実行
if ($cmd === 'REQUEST_VALID_NAV' or $cmd === 'GET_INTERFACE_DATA') {
    if ($cmd === 'GET_INTERFACE_DATA') {
        // INITRTM + REQUEST_VALID_NAV + INDEX
        $ini_result = execute_command($cid, (($val === 'WITH_INITRTM') ? 'INITRTM' : 'INITRTM_NOREWRITE'), '', '', TRUE);
        list($init_activities, $init_current, $init_objectives, $init_context) = array($activities, $current, $objectives, $context);
        $cont_result = execute_command($cid, 'REQUEST_VALID', 'CONTINUE', '', TRUE);
        list($activities, $current, $objectives, $context) = array($init_activities, $init_current, $init_objectives, $init_context);
        $prev_result = execute_command($cid, 'REQUEST_VALID', 'PREVIOUS', '', TRUE);
        list($activities, $current, $objectives, $context) = array($init_activities, $init_current, $init_objectives, $init_context);
        $idx_result = execute_command($cid, 'INDEX', '', '', TRUE);
    }
    else {
        $cont_result = execute_command($cid, 'REQUEST_VALID', 'CONTINUE', '', TRUE);
        list($activities, $current, $objectives, $context) = array(null, null, null, null);
        $prev_result = execute_command($cid, 'REQUEST_VALID', 'PREVIOUS', '', TRUE);
    }
    $value = "result=true\n";
    if (preg_match('/^result=true/', $cont_result)) {
        $value .= "cont=true\n";
    }
    else {
        $value .= "cont=false\n";
    }
    if (preg_match('/^result=true/', $prev_result)) {
        $value .= "prev=true";
    }
    else {
        $value .= "prev=false";
    }
    if (preg_match('/rtm_button_param=(.*)\n?/', $ini_result, $match)) {
        $value .= "\n";
        $value .= $match[0];
    }
    $value .="\ninitrtm_result=" . rawurlencode($ini_result);
    if ($cmd === 'GET_INTERFACE_DATA') {
        $value .= "\nindex=";
        $value .= $idx_result;
    }
    elecoa_write_data($value, FALSE);
}
else {
    execute_command($cid, $cmd, $val, $rtm);
}

// END

/**
 * コマンドを実行する。
 * 
 * @param string $content_id
 * @param string $command
 * @param string $value
 * @param string $runtime
 * @param bool $return
 */
function execute_command($content_id, $command, $value, $runtime, $return = FALSE) {
    global $activities;
    global $current;
    global $objectives;
    global $context;

    // reuse these objects when you call execute_command continuously
    if (is_null($activities)) $activities = elecoa_session_get_activities($content_id);
    if (is_null($current))    $current    = elecoa_session_get_currentid($content_id);
    if (is_null($objectives)) $objectives = elecoa_session_get_objectives($content_id);
    if (is_null($context))    $context    = elecoa_session_get_context($content_id);

    if (!isset($activities)) {
        elecoa_ajax_error();
    }

    $number_of_activities = count($activities);
    $pos = '';
    for ($i = 0; $i < $number_of_activities; $i++) {
        if ($current === $activities[$i]->getID()) {
            $pos = $i;
            break;
        }
    }
    if ($pos === '') {
        for ($i = 0; $i < $number_of_activities; $i++) {
            if ($activities[$i]->getType() === 'LEAF') {
                $pos = $i;
                break;
            }
        }
    }
    if ($pos === '' or $pos === $number_of_activities) {
        elecoa_ajax_error();
    }

    // REQUEST_VALID呼び出しの場合、コマンドを書き換えて、結果をセッションに書き戻さないことで同じ仕組みで判定する
    $rewrite_session = true;
    $original_command = $command;
    if ($original_command === 'REQUEST_VALID') {
        $rewrite_session = false;

        if (preg_match('/^(CONTINUE|PREVIOUS)$/', $value)) {
            $command = $value;
            $value = '';
        }
        else if (preg_match('/^CHOICE\.(.*)$/', $value, $matches)) {
            $command = 'CHOICE';
            $value = $matches[1];
        }
    }
    if ($original_command === 'INDEX') {
        $rewrite_session = false;
    }
    if ($original_command === 'INITRTM_NOREWRITE') {
        $rewrite_session = false;
        $command = 'INITRTM';
    }
    if (!$rewrite_session) {
        // ディープコピーしてセッション内の変数等の書き換えを防ぐ
        $activities = unserialize(serialize($activities));
        $current = unserialize(serialize($current));
        $objectives = unserialize(serialize($objectives));

        foreach ($activities as $activity) {
            $activity->ignoreTrace();
        }
        foreach ($objectives as $objective) {
            $objective->ignoreTrace();
        }
    }

    try {
        Platform::createInstance($activities, $objectives);
        //$retarray = $activities[$pos]->callCommand($command, $value, $runtime);
        $commandEntry = new CommandEntry($activities[$pos]);
        if (!is_null($runtime) && strlen($runtime) > 0) $value = $runtime;
        $retarray = $commandEntry->callCommand($command, $value);
    } catch (Exception $e) {
        elecoa_ajax_error(null, $e);
    }

    if ($retarray['Result']) {
        if ($command === 'INDEX') {
            // request_validの結果を付加する
            append_request_valid($pos, $runtime, $retarray['Value']);
            // JSON化する
            $retstr = json_encode($retarray['Value']);
        }
        else if ($original_command === 'REQUEST_VALID') {
            if (isset($retarray['NextID']) and $retarray['NextID'] !== '') {
                $retstr = "result=true\n";
            }
            else {
                $retstr = "result=false\n";
            }
        }
        else {
            $retstr = "result=true\n";

            if (isset($retarray['NextID']) and $retarray['NextID'] !== '') {
                $retstr .= 'NextID=' . $retarray['NextID'] . "\n";
            }else{
                $retstr .= "NextID=\n";
            }

            // 戻り値がある場合
            if (isset($retarray['Value']) and $retarray['Value'] !== '') {
                $retstr .= $retarray['Value'];
            }
        }

        if ($rewrite_session) {
            $iscont = TRUE;
            if (isset($retarray['Command'])){
                if(($retarray['Command'] === 'EXITALL') or ($retarray['Command'] === 'SUSPEND')){
                    $iscont = FALSE;
                    $retstr .= 'close=true' . "\n";

                    // ここから終了処理
                    elecoa_session_clear_data($content_id);
                    colib::clear_session_data($content_id);
                    try {
                        foreach ($activities as $a) {
                            $a->terminate();

                            if ($a->getType() === 'ROOT') {
                                $grademodule = getGradeModule();
                                if ($grademodule) {
                                    if (method_exists($a, 'saveGrade')) {
                                        $a->saveGrade($grademodule);
                                    }
                                }
                            }
                        }

                        foreach ($objectives as $o) {
                            $o->terminate();
                        }
                    } catch (Exception $e) {
                        elecoa_ajax_error($e->debuginfo, $e);
                    }

                    if ($retarray['Command'] === 'EXITALL') {
                        $current = '';
                    }
                    $log = getLogModule();
                    if ($log->saveCurrentIDForResumption($context->getUid(), $context->getCid(), $context->getAttemptCount(), $current) === FALSE) {
                        $retstr = "result=false\nclose=true\n";
                    }
                } else {
                    try {
                        foreach ($activities as $a) {
                            $a->terminate();
                        }

                        foreach ($objectives as $o) {
                            $o->terminate();
                        }
                    } catch (Exception $e) {
                        elecoa_ajax_error($e->debuginfo, $e);
                    }
                }
            }
            if($iscont){
                elecoa_session_set_data($content_id, $activities, $current, $objectives, $context);
            }
        }
    }else if ($retarray['Result'] === 'unknown') {
        $retstr = "result=unknown";
    }else{
        $retstr = "result=false\nerror=" . (isset($retarray['Error']) ? $retarray['Error'] : 'unknown error');
    }

    if ($return) {
        return $retstr;
    } else {
        elecoa_write_data($retstr, ($command === 'INDEX'));
    }
}

/**
 * request_validの結果も付加する。（INDEXの場合）
 * @param integer $call_activity_position
 * @param string $runtime
 * @param array $target_activity_info
 */
function append_request_valid($call_activity_position, $runtime, &$target_activity_info) {
    global $activities;
    global $objectives;

    if (!isset($activities[$call_activity_position])) {
        return;
    }
    if (!isset($target_activity_info['id']) || empty($target_activity_info['id'])) {
        return;
    }

    $copied_activities = unserialize(serialize($activities));
    $copied_objectives = unserialize(serialize($objectives));

    foreach ($copied_activities as $activity) {
        $activity->ignoreTrace();
    }
    foreach ($copied_objectives as $objective) {
        $objective->ignoreTrace();
    }

    Platform::createInstance($copied_activities, $copied_objectives);
    //echo $target_activity_info['id'] . "\n";
    $commandEntry = new CommandEntry($copied_activities[$call_activity_position]);
    $result = $commandEntry->callCommand('CHOICE', $target_activity_info['id'], $runtime);
    if (isset($result['Error'])) { echo 'error=' . $target_activity_info['id'] . ':' . $result['Error'] . "\n"; };

    $copied_activities = null;
    $copied_objectives = null;

    $request_valid = (isset($result['Error']) && $result['Error'] !== '') ? 'false' : 'true';
    if ($request_valid === 'true') {
        if (isset($result['NextID']) && $result['NextID'] !== '') {
            if ($target_activity_info['type'] === 'LEAF') {
                if ($target_activity_info['id'] !== $result['NextID']) {
                    $request_valid = 'false';
                }
            }
        } else {
            $request_valid = 'false';
        }
    }
    $target_activity_info['request_valid'] = $request_valid;

    if (!isset($target_activity_info['children']) || !is_array($target_activity_info['children'])) {
        return;
    }

    for ($i = 0; $i < count($target_activity_info['children']); $i++) {
        append_request_valid($call_activity_position, $runtime, $target_activity_info['children'][$i]);
    }
}

function elecoa_error_message($str) {
    echo "<html><body>" . $str . "</body></html>";
}

function elecoa_write_data($str, $is_json = false) {
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    if ($is_json) {
        header('Content-type: application/json; charset=UTF-8');
    }
    else {
        header('Content-type: text/plain; charset=UTF-8');
    }
    echo $str;
}

function elecoa_ajax_error($msg = null, $ex = null) {
    if (is_null($msg)) {
        $bt = debug_backtrace();
        $msg = 'errorCode=' . $bt[0]['line'];
    }
    header('Content-type: text/plain; charset=utf-8');
    echo "result=false\n";
    echo "$msg\n";
    if (!is_null($ex)) {
         echo $ex->getMessage();
         echo $ex->getFile();
         echo $ex->getLine();
    }
    exit(0);
}
