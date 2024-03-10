<?php

require_once(dirname(__FILE__) . '/xmlLib.php');
require_once(dirname(__FILE__) . '/init_www.php');
require_once(dirname(__FILE__) . '/elecoa_manifest_converter.php');

// コマンドライン引数
if (count($argv) == 1) {
//	fwrite(STDERR, "Usage: php $argv[0] [--simple] /path/to/imsmanifest.xml\n");
	fwrite(STDERR, "Usage: php ContentID [--simple]\n");
	exit(0);
}

$ContentID = $argv[1];

$isSCORM = !in_array('--simple', $argv);

$firstItem = '';
if(count($argv) >= 3){
	if($argv[2] != '--simple'){
		$firstItem = $argv[2];
	}
}

$M_file = content_path . "/" . $ContentID . "/imsmanifest.xml";
$E_file = content_path . "/" . $ContentID . "/elecoa.xml";

$converter = new elecoa_manifest_converter();

$errstring = $converter->convert_file($ContentID, $isSCORM, $firstItem, $M_file, $E_file);

if($errstring) {
	fwrite(STDERR, $errstring."\n");
	exit(0);
} else {
	echo "FIN";
}

