<?php
require_once(dirname(__FILE__) . '/../init_www.php');

$type = @$_GET['type'];
$type = preg_replace('/[^A-Za-z0-9_-]/i', '', $type);

$name = @$_GET['name'];
$src = @$_GET['src'];

if (is_null($type)) error('Type parameter is required.');

$provider = createAPIAdapterProvider($type);
$content = $provider->getContent($name, $src, is_mobile());

if ($content === FALSE) {
    header('HTTP/1.1 404 Not Found');
} else {
    if (preg_match('/\.js$/', $src)) {
        header('Content-type: application/javascript');
    }
    header('Cache-Control: max-age=86400');

    echo $content;
}
