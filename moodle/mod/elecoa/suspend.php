<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$cmid = optional_param('cmid', 0, PARAM_INT); // coursemodule.id

if ($cmid) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($cmid);
} else {
    error('You must specify a coursemodule id');
}

require_login($course, TRUE, $cm);

echo '<!DOCTYPE html>' . "\n";
echo '<html lang="en">' . "\n";
echo ' <head>' . "\n";
echo '  <meta charset="utf-8">' . "\n";
echo '  <title></title>' . "\n";
echo '  <script>' . "\n";
echo '   document.write(\'<base href="' . preg_replace('/suspend.php.*$/', 'exit.php', $_SERVER['REQUEST_URI']) . '">\');' . "\n";
echo '   document.write(\'<script>var elecoa_id = ', $cmid, ', content_id = ', $elecoa->id, ';<\/script>\');' . "\n";
echo '   document.write(\'<script>var baseUrl = "', $CFG->wwwroot, '", cid = ', $course->id, ';<\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/core.js"><\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/suspend.js"><\/script>\');' . "\n";
echo '  </script>' . "\n";
echo ' </head>' . "\n";
echo ' <body>' . "\n";
echo ' </body>' . "\n";
echo '</html>' . "\n";
