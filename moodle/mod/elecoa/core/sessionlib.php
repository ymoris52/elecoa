<?php
define('ELECOA_SESSION',            'elecoa_session');
define('ELECOA_SESSION_ACTIVITIES', 'elecoa_activities');
define('ELECOA_SESSION_OBJECTIVES', 'elecoa_objectives');
define('ELECOA_SESSION_CURRENTID',  'elecoa_currentid');
define('ELECOA_SESSION_CONTEXT',    'elecoa_context');
define('ELECOA_SESSION_USERID',     'elecoa_userid');
define('ELECOA_SESSION_USERNAME',   'elecoa_username');

/**
 * セッションを準備する。
 */
function elecoa_session_prepare() {
    if (isset($_SESSION[ELECOA_SESSION])) {
        return;
    }
    
    $_SESSION[ELECOA_SESSION] = array();
}

/**
 * セッション情報をクリアする。
 */
function elecoa_session_clear() {
    $_SESSION = array();
}

/**
 * ログインしているかどうかを返す。
 * 
 * @return boolean
 */
function elecoa_session_loggedin() {
    return isset($_SESSION[ELECOA_SESSION_USERID]);
}

/**
 * ログインユーザーIDを返す。
 * 
 * @return mixed
 */
function elecoa_session_get_userid() {
    return $_SESSION[ELECOA_SESSION_USERID];
}

/**
 * ログインユーザー名を返す。
 * 
 * @return mixed
 */
function elecoa_session_get_username() {
    return $_SESSION[ELECOA_SESSION_USERNAME];
}

/**
 * ログインユーザーをセットする。
 * 
 * @param mixed $userid
 * @param mixed $username
 */
function elecoa_session_set_user($userid, $username) {
    $_SESSION[ELECOA_SESSION_USERID] = $userid;
    $_SESSION[ELECOA_SESSION_USERNAME] = $username;
}

/**
 * elecoa用データがセッション上にあるかを返す。
 * 
 * @return boolean
 */
function elecoa_session_exists() {
    return isset($_SESSION[ELECOA_SESSION]);
}

/**
 * 指定されたコンテントのデータがセッション上にあるかを返す。
 * 
 * @param integer $contentid コンテントID
 * @return boolean
 */
function elecoa_session_has_data($contentid) {
    return isset($_SESSION[ELECOA_SESSION][$contentid]);
}

/**
 * 指定されたコンテントのデータをセッションから削除する。
 * 
 * @param integer $contentid コンテントID
 */
function elecoa_session_clear_data($contentid) {
    unset($_SESSION[ELECOA_SESSION][$contentid]);
}

/**
 * アクティビティ情報を返す。
 * 
 * @param integer $contentid
 * @return array
 */
function elecoa_session_get_activities($contentid) {
    return $_SESSION[ELECOA_SESSION][$contentid][ELECOA_SESSION_ACTIVITIES];
}

/**
 * カレントID情報を返す。
 * 
 * @param integer $contentid
 * @return integer
 */
function elecoa_session_get_currentid($contentid) {
    return $_SESSION[ELECOA_SESSION][$contentid][ELECOA_SESSION_CURRENTID];
}

/**
 * オブジェクティブ情報を返す。
 * 
 * @param integer $contentid
 * @return array
 */
function elecoa_session_get_objectives($contentid) {
    return $_SESSION[ELECOA_SESSION][$contentid][ELECOA_SESSION_OBJECTIVES];
}

/**
 * コンテキスト情報を返す。
 * 
 * @param integer $contentid
 * @return object
 */
function elecoa_session_get_context($contentid) {
    if (!array_key_exists(ELECOA_SESSION, $_SESSION)) {
        return NULL;
    }
    if (!array_key_exists($contentid, $_SESSION[ELECOA_SESSION])) {
        return NULL;
    }
    return $_SESSION[ELECOA_SESSION][$contentid][ELECOA_SESSION_CONTEXT];
}

/**
 * データをセットする。
 * 
 * @param array $activities
 * @param integer $currentid
 * @param array $objectives
 * @param object $context
 */
function elecoa_session_set_data($contentid, array &$activities, $currentid, array &$objectives, ElecoaContext &$context) {
    elecoa_session_prepare();
    
    $_SESSION[ELECOA_SESSION][$contentid] = array(
        ELECOA_SESSION_ACTIVITIES => $activities, 
        ELECOA_SESSION_CURRENTID  => $currentid, 
        ELECOA_SESSION_OBJECTIVES => $objectives, 
        ELECOA_SESSION_CONTEXT    => $context
    );
}

/**
 * カレントID情報をセットする。
 * 
 * @param integer $contentid
 * @param integer $currentid
 */
function elecoa_session_set_currentid($contentid, $currentid) {
    elecoa_session_prepare();
    
    $_SESSION[ELECOA_SESSION][$contentid][ELECOA_SESSION_CURRENTID] = $currentid;
}
