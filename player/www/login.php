<?php
	require_once(dirname(__FILE__) . '/../init_www.php');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		redirect();
	}

	if (!(isset($_POST['uid']) and isset($_POST['pwd']))) {
		error();
	}
	// 簡易的にユーザ管理
	$pwd = array('user1' => 'pass1', 'user2' => 'pass2', 'user3' => 'pass3', 'user4' => 'pass4', 'user5' => 'pass5');
	if (!isset($pwd[$_POST['uid']]) or $pwd[$_POST['uid']] !== $_POST['pwd']) {
		error('Invalid User ID or Password');
	}

	session_start();
	elecoa_session_set_user($_POST['uid'], $_POST['uname']);

	redirect(web_base_path . '/menu.php');
?>
