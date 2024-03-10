<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$request_method = $_SERVER['REQUEST_METHOD'];
$origin = $_SERVER['HTTP_ORIGIN'];

if ($request_method === 'OPTIONS') {
    if (parse_url($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: POST,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

if ($request_method === 'POST') {
    $key = @$_GET['k'];
    $key = preg_replace('/[^A-Za-z0-9_-]/i', '', $key);

    $cmi5ext = getCMI5Extension();
    $result = $cmi5ext->getAuthorizationToken($key);

    if (parse_url($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Content-type: application/json');
    header('Cache-Control: max-age=86400');

    echo json_encode(array('auth-token' => $result['AuthToken']));
}
