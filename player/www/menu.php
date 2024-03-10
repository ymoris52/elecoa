<?php
    require_once(dirname(__FILE__) . '/../init_www.php');

    function selectChoiceItems($node, &$items) {
        foreach (selectNodes($node, 'item') as $n) {
            $identifier = $n->getAttribute('identifier');
            if (!is_null($title = selectSingleNode($n, 'title'))) {
                $title = $title->nodeValue;
            }
            $items["$identifier"] = $title;
            selectChoiceItems($n, $items);
        }
    }

    session_check();

    $attempts = array();
    $options = array();
    $choices = array();
    if (!$files = scandir(content_path)) {
        error();
    }
    foreach ($files as $file) {
        if ($file === '.' or $file === '..' or !is_file($elecoa_xml = content_path . '/' . $file . '/elecoa.xml')) {
            continue;
        }
        $doc = new DOMDocument();
        if (!$doc -> load($elecoa_xml)) {
            continue;
        }
        $choice_items = array();
        selectChoiceItems($doc -> documentElement, $choice_items);
        $choices[] = $choice_items;
        // DOMXPath は namespace が扱いづらいので使わない
        if (is_null($organization = selectSingleNode($doc -> documentElement, 'item'))) {
            continue;
        }
        if (!is_null($title = selectSingleNode($organization, 'title'))) {
            $title = $title -> nodeValue;
        }
        if (is_null($title) or $title === '') {
            $title = $file;
        }
        $cnt = 0;     // 現在までのアテンプト回数
        $res = FALSE; // 最新のアテンプトに中断データがあるか
        if (!elecoa_session_loggedin()) {
            error();
        }
        $uid = elecoa_session_get_userid();
        $log = getLogModule();
        if (($cnt = $log -> getLastAttempt($uid, $file)) === FALSE) {
            error();
        }
        $res = $log -> existsResumeData($uid, $file, $cnt);
        $attempts[] = "     [$cnt, " . ($res ? 'true' : 'false') . ']';
        $options[] = array($file, $title);
    }
?>
<!DOCTYPE html>
<html lang="ja">
 <head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="./css/default.css">
  <script src="./js/attempt.js"></script>
  <script>
   var attempts = [
<?php
    echo implode(",\n", $attempts) . "\n";
?>
   ];
   var choices = <?php echo json_encode($choices); ?>;
  </script>
  <title>ELECOA Player</title>
 </head>
 <body>
  <h1>ELECOA Player</h1>
  <div id="session">
   You are signed in as <em><?php echo elecoa_session_get_userid(); ?></em>.
   (<a href="./logout.php">Sign Out</a>)
  </div>
 <form action="./startmodule.php" method="post" id="mf">
   <h2>Content Selection</h2>
   <div>
    <label for="cid">Content</label>
    <select name="cid" id="cid">
     <option value="." selected="selected">Please select</option>
<?php
    foreach ($options as $o) {
        echo "     <option value=\"$o[0]\">" . htmlspecialchars($o[1]) . "</option>\n";
    }
?>
    </select>
   </div>
   <div>
    <span class="label">Attempted</span>
    <div id="cnt">-</div>
   </div>
   <div>
    <label for="sid">Start Activity</label>
    <select name="sid" id="sid">
     <option value="" selected="selected">Defalt</option>
    </select>
   </div>
   <div>
    <span class="label">New / Resume</span>
    <label><input type="radio" name="res" value="0" checked="checked" disabled="disabled" />New</label>
    <label><input type="radio" name="res" value="1" disabled="disabled" />Resume</label>
    <label><input type="radio" name="res" value="2" disabled="disabled" />Clear Attempts</label>
   </div>
   <div class="center">
    <button type="submit" disabled="disabled">Start</button>
   </div>
  </form>
  <form action="./contentpkguploader.php" method="post" enctype="multipart/form-data" id="cf">
   <div>Upload SCORM content package</div>
   <input type="file" name="cpf" id="cpf" />
   <div class="center">
    <button type="submit" disabled="disabled">Upload</button>
   </div>
  </form>
<?php
    if (show_log) {
        echo "  <div class=\"center\"><a href=\"./log.php\">View log files</a><div>\n";
    }
?>
 </body>
</html>
