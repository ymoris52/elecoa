<?php

/**
 * Internal library of functions for module elecoa
 *
 * All the elecoa specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 */

defined('MOODLE_INTERNAL') || die();

define('ELECOA_HIGHESTATTEMPT', '0');
define('ELECOA_AVERAGEATTEMPT', '1');
define('ELECOA_FIRSTATTEMPT', '2');
define('ELECOA_LASTATTEMPT', '3');


/**
 * 配列をコピーする。
 * PHPは自動的にコピー扱いになる。
 * @param array $source_array
 * @return array
 */
function array_clone(&$source_array) {
    return $source_array;
}


/**
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 */
//function elecoa_do_something_useful(array $things) {
//    return new stdClass();
//}

/**
 * Extracts elecoa/scrom package, sets up all variables.
 * Called whenever elecoa/scorm changes
 * @param object $elecoa instance - fields are updated and changes saved into database
 * @param bool $full force full update if true
 * @return void
 */
function elecoa_parse($elecoa, $full) {
    global $CFG, $DB;

    require_once(dirname(__FILE__) . '/core/xmlLib.php');

    if (!isset($elecoa->cmid)) {
        $cm = get_coursemodule_from_instance(ELECOA_MODULE, $elecoa->id);
        $elecoa->cmid = $cm->id;
    }
//    $context = get_context_instance(CONTEXT_MODULE, $elecoa->cmid);
    $context = context_module::instance($elecoa->cmid);
  
    $newhash = $elecoa->sha1hash;

    $fs = get_file_storage();
    $packagefile = FALSE;

    if ($packagefile = $fs->get_file($context->id, MOD_ELECOA_MODULE, 'package', 0, '/', $elecoa->reference)) {
        $newhash = $packagefile->get_contenthash();
    } else {
        $newhash = null;
    }

    if ($packagefile) {
        if (!$full and $packagefile and $elecoa->sha1hash === $newhash) {
            if (strpos($elecoa->version, 'ELECOA') !== FALSE) {
                if ($fs->get_file($context->id, MOD_ELECOA_MODULE, 'content', 0, '/', 'elecoa.xml')) {
                    // no need to update
                    return;
                }
            } else if (strpos($elecoa->version, 'SCORM') !== FALSE) {
                if ($fs->get_file($context->id, MOD_ELECOA_MODULE, 'content', 0, '/', 'imsmanifest.xml')) {
                    // no need to update
                    return;
                }
            }
        }

        // now extract files
        $fs->delete_area_files($context->id, MOD_ELECOA_MODULE, 'content');

        $packer = get_file_packer('application/zip');
        $packagefile->extract_to_storage($packer, $context->id, MOD_ELECOA_MODULE, 'content', 0, '/');

    } else if (!$full) {
        return;
    }

    if ($manifest = $fs->get_file($context->id, MOD_ELECOA_MODULE, 'content', 0, '/', 'elecoa.xml')) {
        // ELECOA
        $elecoa->version = 'ELECOA_0.1';
    } else if ($manifest = $fs->get_file($context->id, MOD_ELECOA_MODULE, 'content', 0, '/', 'imsmanifest.xml')) {
        // convert ismanifest.xml to elecoa.xml
        require_once(dirname(__FILE__) . '/core/elecoa_manifest_converter.php');
        $converter = new elecoa_manifest_converter();
        $ContentID = "";
        $isSCORM = TRUE;
        $firstItem = "";
        $M_xml = $manifest->get_content();
        $E_xml = "";
        $error = $converter->convert_xml($ContentID, $isSCORM, $firstItem, $M_xml, $E_xml);
        if (!$error) {
            $fileinfo = array(
                'contextid' => $context->id, // ID of context
                'component' => MOD_ELECOA_MODULE,     // usually = table name
                'filearea' => 'content',     // usually = table name
                'itemid' => 0,               // usually = ID of row in table
                'filepath' => '/',           // any path beginning and ending in /
                'filename' => 'elecoa.xml'); // any filename

            $fs->create_file_from_string($fileinfo, $E_xml);
            // SCORM
            $elecoa->version = 'ELECOA_0.1';
        } else {
            $elecoa->version = 'ERROR';
        }
    } else if ($manifest = $fs->get_file($context->id, MOD_ELECOA_MODULE, 'content', 0, '/', 'cmi5.xml')) {
        // convert cmi5_xml to elecoa_xml
        require_once(dirname(__FILE__) . '/core/cmi5_manifest_converter.php');
        $converter = new cmi5_manifest_converter();
        $cmi5_xml = $manifest->get_content();
        $elecoa_xml = "";
        $error = $converter->convert_xml($cmi5_xml, $elecoa_xml);
        if (!$error) {
            // create elecoa.xml
            $fileinfo = array(
                'contextid' => $context->id, // ID of context
                'component' => MOD_ELECOA_MODULE,     // usually = table name
                'filearea' => 'content',     // usually = table name
                'itemid' => 0,               // usually = ID of row in table
                'filepath' => '/',           // any path beginning and ending in /
                'filename' => 'elecoa.xml'); // any filename
            $fs->create_file_from_string($fileinfo, $elecoa_xml);

            // create redirector.html
            $redirector_html = $converter->get_redirector_html();
            $fileinfo = array(
                'contextid' => $context->id, // ID of context
                'component' => MOD_ELECOA_MODULE,     // usually = table name
                'filearea' => 'content',     // usually = table name
                'itemid' => 0,               // usually = ID of row in table
                'filepath' => '/',           // any path beginning and ending in /
                'filename' => 'redirector.html'); // any filename
            $fs->create_file_from_string($fileinfo, $redirector_html);

            // set elecoa version
            $elecoa->version = 'ELECOA_0.1';
        } else {
            $elecoa->version = 'ERROR';
        }
    } else {
        $elecoa->version = 'ERROR';
    }

    if ($manifest = $fs->get_file($context->id, MOD_ELECOA_MODULE, 'content', 0, '/', 'elecoa.xml')) {
        $xmlstring = $manifest->get_content();
        $doc = new DOMDocument();
        if ($doc->loadXML($xmlstring)) {
            $root = selectSingleNode($doc, 'manifest');
            if ($root) {
                $item = selectSingleNode($root, 'item');
                if ($item) {
                    elecoa_get_items($elecoa->id, null, $item);
                }
            }
        }
    }

    $elecoa->sha1hash = $newhash;
    $DB->update_record(ELECOA_TABLE, $elecoa);
}

function elecoa_get_items($elecoaid, $parentid, $node)
{
    GLOBAL $DB;

    require_once(dirname(__FILE__) . '/core/xmlLib.php');

    $record = new stdClass();
    $record->elecoaid = $elecoaid;
    $record->parentid = $parentid;
    $record->identifier = (string)$node->getAttribute('identifier');
    $record->title = (string)selectSingleNode($node, 'title')->nodeValue;
    $record->cotype = (string)$node->getAttribute('coType');

    $id = $DB->insert_record(ELECOA_ITEMS_TABLE, $record);
    if ($id) {
        foreach (selectNodes($node, 'item') as $n) {
            elecoa_get_items( $elecoaid, $id, $n );
        }
    }
}

function elecoa_rmdir_r($dir) {
    if (!(is_dir($dir) and $files = scandir($dir))) {
        return;
    }
    foreach ($files as $f) {
        if ($f === '.' or $f === '..') {
            continue;
        }
        $f = "$dir/$f";
        if (is_dir($f)) {
            elecoa_rmdir_r($f);
        }
        else {
            unlink($f);
        }
    }
    rmdir($dir);
}

/**
 * Returns an array of the array of what grade options
 *
 * @return array an array of what grade options
 */
function elecoa_get_what_grade_array(){
    return array (ELECOA_HIGHESTATTEMPT => get_string('highestattempt', ELECOA_MODULE),
                  ELECOA_AVERAGEATTEMPT => get_string('averageattempt', ELECOA_MODULE),
                  ELECOA_FIRSTATTEMPT => get_string('firstattempt', ELECOA_MODULE),
                  ELECOA_LASTATTEMPT => get_string('lastattempt', ELECOA_MODULE));
}


/**
 * モジュール名を返す。
 */
function elecoa_get_module_name() {
    return MOD_ELECOA_MODULE;
}


/**
 * モジュールのパス名を返す。
 */
function elecoa_get_module_path_name() {
    return ELECOA_MODULE;
}


/**
 * get_stringのelecoa版。文字列を自動的にモジュール内の定義から読み込む。
 * @param string $string 文字列
 */
function elecoa_get_string($string) {
    return get_string($string, ELECOA_MODULE);
}


/**
 * get_stringsのelecoa版。文字列を自動的にモジュール内の定義から読み込む。
 * @param array $strings 文字列配列
 */
function elecoa_get_strings($strings) {
    return get_strings($strings, ELECOA_MODULE);
}


/**
 * print_errorのelecoa版。文字列を自動的にモジュール内の定義から読み込む。
 * @param string $error エラー文字列
 */
function elecoa_print_error($error) {
    return print_error($error, ELECOA_MODULE);
}


/**
 * コースモジュールIDからコースモジュールオブジェクト、コースレコード、elecoaレコードを取得して返す。
 * @param integer $coursemodule_id
 * @return array [0]=>コースモジュールオブジェクト、[1]=>コースレコード、[2]=>elecoaレコード
 */
function elecoa_get_courses_array_from_coursemodule_id($coursemodule_id) {
    global $DB;
    $coursemodule = get_coursemodule_from_id(ELECOA_MODULE, $coursemodule_id, 0, FALSE, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $coursemodule->course), '*', MUST_EXIST);
    $elecoa  = $DB->get_record(ELECOA_TABLE, array('id' => $coursemodule->instance), '*', MUST_EXIST);
    
    return array($coursemodule, $course, $elecoa);
}


/**
 * インスタンスIDからコースモジュールオブジェクト、コースレコード、elecoaレコードを取得して返す。
 * @param integer $instance_id
 * @return array [0]=>コースモジュールオブジェクト、[1]=>コースレコード、[2]=>elecoaレコード
 */
function elecoa_get_courses_array_from_instance_id($instance_id) {
    global $DB;
    $elecoa = $DB->get_record(ELECOA_TABLE, array('id' => $instance_id), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $elecoa->course), '*', MUST_EXIST);
    $coursemodule = get_coursemodule_from_instance(ELECOA_MODULE, $elecoa->id, $course->id, FALSE, MUST_EXIST);
    
    return array($coursemodule, $course, $elecoa);
}
