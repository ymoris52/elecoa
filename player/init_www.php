<?php
    // mb_language('Japanese');
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');

    // インストール環境設定
    define('base_path', dirname(__FILE__));
    define('content_path', base_path . '/www/contents');
    define('log_path', base_path . '/log');
    define('syslog_path', base_path . '/syslog');
    define('web_base_path', '/elecoa_player');    // TODO: You have to rewrite this to the web base directory in your environment.
    define('web_content_path', web_base_path . '/contents');
    define('log_module', 'FileLog');
    define('show_log', FALSE);

    // 共通処理
    // date_default_timezone_set('Asia/Tokyo');
    ob_start();
    session_name('elecoaplayer');
    session_set_cookie_params(0, web_base_path . '/');
    require_once(base_path . '/xmlLib.php');
    require_once(base_path . '/contextLib.php');
    require_once(base_path . '/sessionlib.php');
    require_once(base_path . '/dbLib.php');
    require_once(base_path . '/coLib.php');
    require_once(base_path . '/Platform.php');
    require_once(base_path . '/CommandEntry.php');
    require_once(base_path . '/' . log_module . '.php');
    require_once(base_path . '/utilityLib.php');
    set_error_handler('error_handler');

    spl_autoload_register(function ($classname) {
        $classfile = base_path . '/co/' . $classname . '.php';
        if (file_exists($classfile)) {
            require_once($classfile);
        }
    });
    
    
    /**
     * 配列をコピーする。
     * PHPは自動的にコピー扱いになる。
     * @param array $source_array
     * @return array
     */
    function array_clone(&$source_array) {
        return $source_array;
    }
    
    function createAPIAdapterProvider($type) {
        $class_name = $type . 'APIAdapterProvider';
        return new $class_name();
    }
    
    function getLogModule() {
        $LogModule = log_module;
        $log = new $LogModule(log_path); //log_path is defined in init_www.php
        return $log;
    }

    function readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system = FALSE) {
        $log = getLogModule();
        return $log->readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system);
    }

    function writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system = FALSE) {
        $log = getLogModule();
        return $log->writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system);
    }

    function getGradeModule() {
        return null;
    }

    function session_check() {
        session_start();
        if (!elecoa_session_loggedin()) {
            elecoa_session_clear();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 86400, web_base_path . '/');
            }
            session_destroy();
            redirect();
        }
    }

    // コンテンツ名 (格納ディレクトリ名) から、ログディレクトリ名 (アテンプト番号は含まない) を返す
    function log_base($cid) {
        return elecoa_session_loggedin() ? log_path . '/' . $cid . '/' . urlencode(elecoa_session_get_userid()) : NULL;
    }

    // ディレクトリを削除
    function rmdir_r($dir) {
        if (!(is_dir($dir) and $files = scandir($dir))) {
            return;
        }
        foreach ($files as $f) {
            if ($f === '.' or $f === '..') {
                continue;
            }
            $f = "$dir/$f";
            if (is_dir($f)) {
                rmdir_r($f);
            }
            else {
                unlink($f);
            }
        }
        rmdir($dir);
    }

    // バックトレースを errorファイルに出力する。
    // 例外オブジェクトを指定した場合は、例外のスタックトレースも出力する。
    function print_backtrace($bt, $ex = NULL) {
        $fh = fopen(syslog_path . '/error', 'a+');
        $len = strlen($base_path = base_path . '/');
        foreach ($bt as $key => $val) {
            if ($key == 0) {
                fwrite($fh, date('Y/m/d H:i:s') . " #$key ");
            } else {
                fwrite($fh, "                    #$key ");
            }
            fwrite($fh, $val['file'] . "(" . $val['line'] . ") " . $val['function'] . "\n");
        }
        if (!is_null($ex)) {
            fwrite($fh, "                    Exception:\n");
            foreach ($ex->getTrace() as $t_key => $t_val) {
                fwrite($fh, "                    #$t_key " . $t_val['file'] . "(" . $t_val['line'] . "): " . $t_val['class'] . $t_val['type'] . $t_val['function']);
                fwrite($fh, "(");
                foreach ($t_val['args'] as $a_key => $a_val) {
                    if ($a_key != 0) {
                        fwrite($fh, ",");
                    }
                    if (is_string($a_val)) {
                        fwrite($fh, "'$a_val'");
                    } else if (is_null($a_val)) {
                        fwrite($fh, "NULL");
                    } else {
                        fwrite($fh, "$a_val");
                    }
                }
                fwrite($fh, ")\n");
            }
        }
        fclose($fh);
    }

    function error_handler($errno, $errstr, $errfile, $errline) {
        if ( E_RECOVERABLE_ERROR === $errno ) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
        return false;
    }

    function error($msg = NULL) {
        if (is_null($msg)) {
            $bt = debug_backtrace();
            print_backtrace($bt);
        }
        ob_clean();
        echo "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"no\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" dir="ltr">
 <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" href="./css/default.css" media="screen, print" title="default style" type="text/css" />
  <title>ELECOA Player</title>
 </head>
 <body>
  <h1>ELECOA Player</h1>
  <div id="error_msg" class="center">
   <?php echo is_null($msg) ? "Error\n" : "$msg\n" ?>
  </div>
  <div class="center"><a href="./logout.php">Sign Out</a></div>
 </body>
</html>
<?php
        exit(0);
    }

    function redirect($path = NULL) {
        if (is_null($path)) {
            $path = web_base_path . '/';
        }
        header('HTTP/1.1 302 Found');
        header('Location: ' . $path);
        exit(0);
    }

