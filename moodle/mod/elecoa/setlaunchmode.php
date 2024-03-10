<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$lm = @$_GET['lm'];

$cmi5ext = getCMI5Extension();
if ($lm === "0") {
    $cmi5ext->setSessionLaunchMode("Normal");
}
if ($lm === "1") {
    $cmi5ext->setSessionLaunchMode("Browse");
}
if ($lm === "2") {
    $cmi5ext->setSessionLaunchMode("Review");
}
echo $cmi5ext->getSessionLaunchMode();
