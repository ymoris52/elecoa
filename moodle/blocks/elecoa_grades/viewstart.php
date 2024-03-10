<?php

require_once dirname(__FILE__) . '/../../config.php';

$courseid = required_param('courseid', PARAM_INT);

echo '<!DOCTYPE html>' . "\n";
echo '<html>' . "\n";
echo '<head>' . "\n";
echo '<script>document.write(\'<base href="', preg_replace('/viewstart.php.*$/', 'viewstart.php', $_SERVER['REQUEST_URI']), '"><script>location.href = "view.php?courseid=', $courseid, '";<\/script>\');</script>';
echo '</head>' . "\n";
echo '<body></body>' . "\n";
echo '</html>' . "\n";

?>
