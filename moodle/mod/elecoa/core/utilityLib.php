<?php

/**
 * モバイルかどうかを返す。
 * @return boolean モバイルならTRUE、そうでなければFALSE
 */
function is_mobile() {
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    
    if (preg_match('/.+iPhone/', $useragent)) {
        return TRUE;
    }
    
    if (preg_match('/.+iPad/', $useragent)) {
        return TRUE;
    }
    
    if (preg_match('/.+Android/', $useragent)) {
        return TRUE;
    }
    
    return FALSE;
}


/**
 * objectタグを使うかどうかを返す。
 * @return boolean objectタグを使うならTRUE、そうでなければFALSE
 */
function use_object_tag() {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $msie = strpos($ua, 'MSIE ') !== FALSE || strpos($ua, 'Trident/7.') !== FALSE || strpos($ua, 'Edge/') !== FALSE;
    return !$msie;
}


/**
 * アクティビティを検索する。
 * @param array $activities アクティビティ配列
 * @param function $function 検索関数 function(&$activity, $index) で、bool値を返すもの
 * @return アクティビティのインデックス値。見つからなければFALSE
 */
function find_activity(&$activities, $function) {
    for ($i = 0; $i < count($activities); $i++) {
        if ($function($activities[$i], $i)) {
            return $i;
        }
    }
    
    return FALSE;
}


/**
 * 指定されたIDのアクティビティのインデックス値を返す。
 * @param array $activities
 * @param string $id
 */
function find_activity_by_id(&$activities, $id) {
    $find_function = function(&$activity, $index) use ($id) {
        return ($activity->getID() === $id);
    };
    return find_activity($activities, $find_function);
}


/**
 * DB用espace_string
 * @param string $string
 */
function db_escape_string($string) {
    global $CFG;
    
    switch ($CFG->dbtype) {
        case 'mysqli':
            return addslashes($string);
        
        case 'mysql':
            return mysql_real_escape_string($string);
            
        case 'pgsql':
            return pg_escape_string($string);
            
        default:
            return addslashes($string);
    }
}
