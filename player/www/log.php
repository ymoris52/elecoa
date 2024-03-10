<?php
    require_once(dirname(__FILE__) . '/../init_www.php');

    session_check();

    if (!show_log) {
        error();
    }

    if (!$dirs = scandir(log_path)) {
        error();
    }

    // $contents: ログのあるコンテンツ
    $contents = array();
    foreach ($dirs as $dir) {
        if (!in_array($dir, array('.', '..', 'GO')) && is_dir(log_path . '/' . $dir . '/' . urlencode(elecoa_session_get_userid()))) {
            array_push($contents, $dir);
        }
    }
    sort($contents);

    // $gobj: Objectives Global to System = True の共有グローバル学習目標
    $gobj = array();
    $gobj_dir = log_path . '/GO/' . urlencode(elecoa_session_get_userid());
    if (is_dir($gobj_dir)) {
        if (!$files = scandir($gobj_dir)) {
            error();
        }
        foreach ($files as $file) {
            if (!in_array($file, array('.', '..'))) {
                array_push($gobj, $file);
            }
        }
    }
    sort($gobj);

    // $cc: 指定コンテンツ
    if (isset($_GET['c'])) {
        $cc = urldecode($_GET['c']);
    } else {
        $cc = NULL;
    }

    // $ca: 指定学習回
    if (isset($_GET['a'])) {
        $ca = urldecode($_GET['a']);
    } else {
        $ca = NULL;
    }

    // $cf: 指定ログファイル (Objectives Global to System = True の共有グローバル学習目標以外)
    if (isset($_GET['f'])) {
        $cf = urldecode($_GET['f']);
    } else {
        $cf = NULL;
    }

    // $cg: 指定ログファイル (Objectives Global to System = True の共有グローバル学習目標)
    if (isset($_GET['g'])) {
        $cg = urldecode($_GET['g']);
    } else {
        $cg = NULL;
    }

    // $attempts: 学習回 (コンテンツが指定されていれば)
    $attempts = array();
    if (isset($cc)) {
        if (!in_array($cc, $contents)) {
            error();
        }
        $cc_dir = log_path . '/' . $cc . '/' . urlencode(elecoa_session_get_userid());
        if (!$dirs = scandir($cc_dir)) {
            error();
        }
        foreach ($dirs as $dir) {
            if (in_array($dir, array('.', '..'))) {
                continue;
            }
            if (preg_match('/^[1-9]\d*$/', $dir) && is_dir($cc_dir . '/' . $dir))
            {
                array_push($attempts, $dir);
            }
        }
        sort($attempts, SORT_NUMERIC);
    }

    // $log_files: ログファイル (学習回が指定されていれば)
    $log_files = array();
    if (isset($ca)) {
        if (!in_array($ca, $attempts)) {
            error();
        }
        $ca_dir = $cc_dir . '/' . $ca;
        if (!$files = scandir($ca_dir)) {
            error();
        }
        foreach ($files as $file) {
            if (in_array($file, array('.', '..', 'GO'))) {
                continue;
            }
            if (is_file($ca_dir . '/' . $file))
            {
                array_push($log_files, $file);
            }
        }
        sort($log_files);

        // $go: Objectives Global to System = False の共有グローバル学習目標
        $go = array();
        $go_dir = $ca_dir . '/GO';
        if (is_dir($go_dir)) {
            if (!$files = scandir($go_dir)) {
                error();
            }
            foreach ($files as $file) {
                if (!in_array($file, array('.', '..'))) {
                    array_push($go, 'GO/' . $file);
                }
            }
        }
        sort($go);

        $log_files = array_merge($go, $log_files);
    }

    if (isset($cf)) {
        if (!in_array($cf, $log_files)) {
            error();
        }
    }

    if (isset($cg)) {
        if (isset($cc) || !in_array($cg, $gobj)) {
            error();
        }
    }

    // ログファイルが指定されていれば出力
    $lf = NULL;
    if (isset($cf)) {
        $lf = $ca_dir . '/' . $cf;
    } elseif (isset($cg)) {
        $lf = $gobj_dir . '/' . $cg;
    }
    if (isset($lf)) {
        if ($lfc = file_get_contents($lf)) {
            header('Content-Type: ' . (preg_match('/\.xml$/', $lf) ? 'text/xml; charset=utf-8' : 'text/plain'));
            echo $lfc;
            exit(0);
        }
        error();
    }
?>
<!DOCTYPE html>
<html lang="ja">
 <head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="./css/default.css">
  <title>ELECOA Player</title>
 </head>
 <body>
  <h1>ELECOA Player</h1>
  <div id="session">
   You are signed in as <em><?php echo elecoa_session_get_userid(); ?></em>.
   (<a href="./logout.php">Sign Out</a>)
  </div>
  <h2>ログ閲覧</h2>
  <h3>共有グローバル学習目標 (Objectives Global to System = True)</h3>
<?php
    if (count($gobj) == 0) {
        echo "  <p>共有グローバル学習目標のログはありません。</p>\n";
    } else {
        echo "  <ul class=\"gobj\">\n";
        foreach ($gobj as $g) {
            echo '   <li><a href="./log.php?g=' . urlencode($g) . '">' . htmlspecialchars($g) . '</a></li>' . "\n";
        }
        echo "  </ul>\n";
    }
?>
  <h3>コンテンツ</h3>
<?php
    echo "  <table class=\"log\">\n";
    echo "   <thead>\n";
    echo "    <tr>\n";
    echo "     <th>コンテンツ</th>\n";
    echo "     <th>学習回</th>\n";
    echo "     <th>ログファイル</th>\n";
    echo "    </tr>\n";
    echo "   </thead>\n";
    echo "   <tbody>\n";
    echo "    <tr>\n";
    echo "     <td>\n";
    if (count($contents) == 0) {
        echo "      <div class=\"cm\">コンテンツのログはありません。</div>\n";
    } else {
        foreach ($contents as $c) {
            echo '      <div';
            if ($c === $cc) {
                echo ' class="c"';
            }
            echo '><a href="./log.php?c=' . urlencode($c) . '">' . htmlspecialchars($c) . '</a></div>' . "\n";
        }
    }
    echo "     </td>\n";
    echo "     <td>\n";
    if (!isset($cc)) {
        echo "      <div class=\"cm\">(←コンテンツを選択)</div>\n";
    } else {
        foreach ($attempts as $a) {
            echo '      <div';
            if ($a === $ca) {
                echo ' class="c"';
            }
            echo '><a href="./log.php?c=' . urlencode($cc) . '&amp;a=' . $a . '">' . $a . '</a></div>' . "\n";
        }
    }
    echo "     </td>\n";;
    echo "     <td>\n";
    if (!isset($ca)) {
        echo "      <div class=\"cm\">(←学習回を選択)</div>\n";
    } else {
        foreach ($log_files as $f) {
            $stat = stat($ca_dir . '/' . $f);
            if ($stat['size'] == 0) {
                echo '      <div>' . htmlspecialchars($f) . ' (0 bytes)</div>' . "\n";
            } else {
                echo '      <div><a href="./log.php?c=' . urlencode($cc) . '&amp;a=' . $ca . '&amp;f=' . urlencode($f) . '">' . htmlspecialchars($f) . '</a></div>' . "\n";
            }
        }
    }
    echo "     </td>\n";
    echo "    </tr>\n";
    echo "   </tbody>\n";
    echo "  </table>\n";

?>
  <div class="center"><a href="./menu.php">戻る</a></div>
 </body>
</html>
