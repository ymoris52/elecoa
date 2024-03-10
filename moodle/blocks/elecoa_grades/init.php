<?php

define('ELECOA_BLOCK_NAME', 'block_elecoa_grades');

function elecoa_get_block_file_url( $filename )
{
    global $CFG;
    return $CFG->wwwroot . "/blocks/elecoa_grades/" . $filename;
}
