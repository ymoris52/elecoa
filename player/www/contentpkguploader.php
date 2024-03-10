<?php

    require_once(dirname(__FILE__) . '/../init_www.php');
    require_once(dirname(__FILE__) . "/../elecoa_manifest_converter.php");

    session_check();

    $error_message = '';

    $result = upload_file();
    if (!is_null($result)) {
        if (extract_zip_file($result['zip_file_path'], $result['content_id'])) {
            make_elecoa_manifest_file($result['content_id']);
        }
    } else {
        add_error_message('failed to upload file.');
    }

    function upload_file() {
        $MAXIMUM_FILESIZE = 5 * 1024 * 1024; //5MB
        $is_file = is_uploaded_file($_FILES['cpf']['tmp_name']);
        $moved = false;
        $safe_filename = '';
        if ($is_file) {
            //  sanitize file name 
            $safe_filename = preg_replace(array("/\s+/", "/[^-\.\w]+/"), array("_", ""), trim($_FILES['cpf']['name']));
            //  check if under MAXIMUM_FILESIZE
            //  check file extension
            if ($_FILES['cpf']['size'] <= $MAXIMUM_FILESIZE && preg_match("/^\.(zip){1}$/i", strrchr($safe_filename, '.'))) {
                $moved = move_uploaded_file($_FILES['cpf']['tmp_name'], content_path . '/' . $safe_filename);
            }
        }
        if ($moved) {
            return array('zip_file_path' => content_path . '/' . $safe_filename, 'file_name' => $safe_filename, 'content_id' => substr($safe_filename, 0, -4));
        } else {
            return NULL;
        }
    }

    function extract_zip_file($zip_file_path, $content_id) {
        $elecoa_xml_filepath = content_path . '/' . $content_id . '/elecoa.xml';
        if (file_exists($elecoa_xml_filepath)) {
            unlink($elecoa_xml_filepath);
        }
        try {
            $zip = new ZipArchive();
            $zip->open($zip_file_path);
            $zip->extractTo(content_path . '/' . $content_id);
            $zip->close();
        } catch (Exception $e) {
            add_error_message('failed to extract file. (' . $e->getMessage() . ')');
            return FALSE;
        }
        return TRUE;
    }

    function make_elecoa_manifest_file($content_id) {
        $converter = new elecoa_manifest_converter();

        $first_item = '';

        $manifest_filepath = content_path . '/' . $content_id . '/imsmanifest.xml';
        $elecoa_xml_filepath = content_path . '/' . $content_id . '/elecoa.xml';

        if (!file_exists($elecoa_xml_filepath)) {
            $error_string = $converter->convert_file($content_id, true, $first_item, $manifest_filepath, $elecoa_xml_filepath);
            if ($error_string) {
                add_error_message($error_string);
            }
        }
    }

    function add_error_message($message) {
        global $error_message;
        $error_message .= '<p>' . htmlentities($message) . '</p>';
    }
?>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="./css/default.css">
 </head>
 <body>
  <h1>ELECOA Player</h1>
  <div id="session">
   You are signed in as <em><?php echo elecoa_session_get_userid(); ?></em>.
   (<a href="./logout.php">Sign Out</a>)
  </div>
<?php
if ($error_message) {
    echo '  <div id="upload_failed">' . $error_message . '<br>Click <a href="./menu.php">here</a> to move back to menu page.</div>' . "\n";
} else {
    echo '  <div id="upload_succeeded">The content package file is successfully uploaded. <br>moving back to menu page...</div><script>setTimeout("location.href=\'./menu.php\'", 3000);</script>' . "\n";
}
?>
 </body>
</html>
