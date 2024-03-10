<?php
	require_once(dirname(__FILE__) . '/../init_www.php');

	session_start();
	elecoa_session_clear();
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time() - 86400, web_base_path . '/');
	}
	session_destroy();

	redirect();
?>
