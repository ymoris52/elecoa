<?php

require_once dirname(__FILE__) . '/core/init_www.php';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$e  = optional_param('e', 0, PARAM_INT);  // elecoa instance ID - it should be named as the first character of the module

if ($id) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($id);
}
elseif ($e) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_instance_id($e);
}
else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, TRUE, $cm);

$cid = $elecoa->id;

echo '<!DOCTYPE html>' . "\n";
echo '<html lang="en">' . "\n";
echo ' <head>' . "\n";
echo '  <meta charset="utf-8">' . "\n";
echo '  <title></title>' . "\n";
echo '  <script>' . "\n";
echo '   document.write(\'<base href="', preg_replace('/exitstart.php.*$/', 'exitstart.php', $_SERVER['REQUEST_URI']), '">\');' . "\n";
echo '   document.write(\'<script>var elecoa_id = ', $id, ', content_id = ', $cid, ';<\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/core.js"><\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/exitstart.js"><\/script>\');' . "\n";
echo '  </script>' . "\n";
echo ' </head>' . "\n";
echo ' <body>' . "\n";
echo ' </body>' . "\n";
echo '</html>' . "\n";
