<?php
require_once(dirname(__FILE__) . '/init.php');

class block_elecoa_grades extends block_base {

    public function init()
    {
        $this->title = get_string('blocktitle', ELECOA_BLOCK_NAME);
    }

    public function get_content()
    {
        global $COURSE;

        if ($this->content !== null)
        {
            return $this->content;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $courseid = $COURSE->id;
        $url = elecoa_get_block_file_url('viewstart.php');
        $blocktext = get_string('blocktext', ELECOA_BLOCK_NAME);

        $this->content->text = "<div class='blocktext'><a href='${url}?courseid=${courseid}'>${blocktext}</a></div>";

        return $this->content;
    }

    function applicable_formats()
    {
        return array('site' => false, 'course' => true);
    }
}

?>
