<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$request_uri = parse_url($_SERVER['REQUEST_URI']);
$request_method = $_SERVER['REQUEST_METHOD'];
$origin = array_key_exists('HTTP_ORIGIN', $_SERVER) ? $_SERVER['HTTP_ORIGIN'] : NULL;

if ($request_method === 'OPTIONS') {
    if (isset($origin) and parse_url($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: GET,POST,PUT,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Experience-API-Version');
    exit;
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header("WWW-Authenticate: Basic realm=\"LRS Listener\"");
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

$auth_user = $_SERVER['PHP_AUTH_USER'];
$auth_pw = $_SERVER['PHP_AUTH_PW'];

$cmi5ext = getCMI5Extension();

$genkey = $cmi5ext->getGenKey($auth_user, $auth_pw);

if (!$genkey) {
    header("WWW-Authenticate: Basic realm=\"LRS Listener\"");
    header("HTTP/1.1 401 Unauthorized");
    exit;
}

$path = $request_uri['path'];
$query = array_key_exists('query', $request_uri) ? $request_uri['query'] : '';

$endpointBaseUrl = $cmi5ext->getEndpointBaseUrl();
$curl = curl_init($endpointBaseUrl);
$header = array(
    'Accept: application/json',
    'Content-Type: application/json',
    'X-Experience-API-Version: 1.0.0',
    'Authorization: Basic ' . $cmi5ext->getLRSAuthorization());
$options = array(
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
);
curl_setopt_array($curl, $options);

if (preg_match('/agents\/profile$/', $path)) {
    //curl_setopt($curl,CURLOPT_URL, $endpointBaseUrl . 'agents/profile?' . $query);
    //curl_setopt($curl,CURLOPT_HTTPGET, true); // GET
    //$result = curl_exec($curl);
    if (isset($origin) and parse_url($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Content-type: application/json');
    //print_r($result);
    print_r("{}");
    curl_close($curl);
    return;
}
if (preg_match('/activities\/profile$/', $path)) {
    curl_setopt($curl,CURLOPT_URL, $endpointBaseUrl . 'activities/profile?' . $query);
    curl_setopt($curl,CURLOPT_HTTPGET, true); // GET
    $result = curl_exec($curl);
    if (isset($origin) and parse_url($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Content-type: application/json');
    print_r($result);
    curl_close($curl);
    return;
}
if (preg_match('/activities\/state$/', $path)) {
    curl_setopt($curl, CURLOPT_URL, $endpointBaseUrl . 'activities/state?' . $query);
    if ($request_method === 'GET') {
        curl_setopt($curl, CURLOPT_HTTPGET, true); // GET
        $result = curl_exec($curl);
        if (isset($origin) and parse_url($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        header('Content-type: application/json');
        print_r($result);
        curl_close($curl);
        return;
    }
    if ($request_method === 'PUT') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        $put_data = file_get_contents('php://input');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $put_data);
        $result = curl_exec($curl);
        if (isset($origin) and parse_url($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        print_r($result);
        curl_close($curl);
        return;
    }
}
if (preg_match('/lrslistener.php\/statements$/', $path)) {
    if ($request_method === 'POST') {
        $post_json = file_get_contents('php://input');
        $post_data = json_decode($post_json, true);
        if (isset($origin) and parse_url($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        $isArray = is_array(json_decode($post_json));
        if ($isArray) {
            foreach ($post_data as $statement) {
                if (!$cmi5ext->checkStatement($statement, $genkey)) {
                    return;
                }
            }
        } else {
            if (!$cmi5ext->checkStatement($post_data, $genkey)) {
                return;
            }
        }
        curl_setopt($curl, CURLOPT_URL, $endpointBaseUrl . 'statements');
        curl_setopt($curl, CURLOPT_POST, true); // POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_json);
        $result = curl_exec($curl);
        print_r($result);
        curl_close($curl);
        if ($isArray) {
            foreach ($post_data as $statement) {
                $cmi5ext->handleStatement($statement, $genkey);
            }
        } else {
            $cmi5ext->handleStatement($post_data, $genkey);
        }
        return;
    }
}

// otherwise
header('HTTP/1.1 400 Bad Request');
