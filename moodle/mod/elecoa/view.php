<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$e  = optional_param('e', 0, PARAM_INT);  // elecoa instance ID - it should be named as the first character of the module

if ($id) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($id);
}
elseif ($e) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_instance_id($e);
}
else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, TRUE, $cm);

$event = \mod_elecoa\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $elecoa);
$event->trigger();

/// Print the page header

$PAGE->set_url('/mod/'.elecoa_get_module_path_name().'/view.php', array('id' => $cm->id));
$PAGE->set_title($elecoa->name);
$PAGE->set_heading($course->shortname);
//$PAGE->set_button(update_module_button($cm->id, $course->id, elecoa_get_string('modulename')));

// other things you may want to set - remove if not needed
$PAGE->set_cacheable(FALSE);
//$PAGE->set_focuscontrol('some-html-id');

// Output starts here
// echo $OUTPUT->header();

if (!isset($elecoa->cmid)) {
    $elecoa->cmid = $cm->id;
}
//$context = get_context_instance(CONTEXT_MODULE, $elecoa->cmid);
$context = context_module::instance($elecoa->cmid);

$fs = get_file_storage();
$manifest = $fs->get_file($context->id, elecoa_get_module_name(), 'content', 0, '/', 'elecoa.xml');

if (!$manifest) {
    // TODO
    elecoa_print_error('incorrectpackage');
}
$uid = $USER->id;
$cid = $elecoa->id;
$log = getLogModule();
$lastattempt = $log->getLastAttempt($uid, $cid);

if ($lastattempt) {
    $res = $log->existsResumeData($uid, $cid, $lastattempt); // restart
} else {
    $res = FALSE;
}

$doc = new DOMDocument();
if (!$doc->loadXML($manifest->get_content())) {
    elecoa_print_error('incorrectmanifest');
}
$root_node = selectSingleDOMNode($doc->documentElement, 'item');
if (is_null($root_node)) {
    elecoa_print_error('incorrectmanifest');
}
$showtoc = ($root_node->getAttribute('showtoc') === 'true');
$ownwindow = ($root_node->getAttribute('ownwindow') === 'true');

if ($ownwindow) {
    $cmi5ext = getCMI5Extension();
    if (!$cmi5ext->getRegistration($cid, $uid)) {
        $cmi5ext->createRegistration($cid, $uid);
    }
}

$CFG->additionalhtmlhead .= '<link rel="stylesheet" href="./css/default.css" />';

// Output starts here
echo $OUTPUT->header();

echo '<h1 class="elecoa-title"><span>' . htmlspecialchars($elecoa->name) . '</span></h1>';

if ($elecoa->intro) {
    echo $OUTPUT->box(format_module_intro(elecoa_get_module_path_name(), $elecoa, $cm->id), 'generalbox boxaligncenter boxwidthwide', 'intro');
}

$available = TRUE;
$timenow = time();
if (!empty($elecoa->timeopen) && $elecoa->timeopen > $timenow) {
    echo $OUTPUT->box(get_string("notopenyet", elecoa_get_module_path_name(), userdate($elecoa->timeopen)), "generalbox boxaligncenter");
    $available = FALSE;
}
if (!empty($elecoa->timeclose) && $timenow > $elecoa->timeclose) {
    echo $OUTPUT->box(get_string("expired", elecoa_get_module_path_name(), userdate($elecoa->timeclose)), "generalbox boxaligncenter");
    $available = FALSE;
}

$in_progress = FALSE;
$session_ctx = elecoa_session_get_context($cid);
if (!is_null($session_ctx)) {
    $in_progress = TRUE;
}

if ($available) {
    $startUrl = './startmodule.php?' . ($id ? ('id=' . urlencode($id)) : ('e=' . urlencode($e)));
    $startNewUrl =  $startUrl . '&mode=new';
    $startResumeUrl = $startUrl . '&mode=resume';
    if ($ownwindow and $showtoc) {
        $startNewUrl .= '&toc=1';
        $startResumeUrl .= '&toc=1';
    }
    if ($in_progress) {
        if ($ownwindow) {
            echo '<script>';
            echo 'top.location.href = "./return.php?cmid=' . $id . '";';
            echo '</script>';
        }
        echo '<script>';
        echo 'function elecoa_on_click_radiobutton() {';
        echo     'if (document.getElementById("elecoa-continue-radiobutton").checked) {';
        echo         'document.getElementById("elecoa-continue-button").setAttribute("style", "display:inline;");';
        echo         'document.getElementById("elecoa-exitnew-button").setAttribute("style", "display:none;");';
        echo     '}';
        echo     'else {';
        echo         'document.getElementById("elecoa-continue-button").setAttribute("style", "display:none;");';
        echo         'document.getElementById("elecoa-exitnew-button").setAttribute("style", "display:inline;");';
        echo     '}';
        echo '}';
        echo '</script>';
        echo '<div class="elecoa-choices">';
        echo '<input type="radio" id="elecoa-exitnew-radiobutton" name="elecoa-exitnew-or-continue" value="1" onclick="javascript:elecoa_on_click_radiobutton();" /><label for="elecoa-exitnew-radiobutton">' . htmlspecialchars(elecoa_get_string('exitstartactivity')) . '</label>';
        echo '<input type="radio" id="elecoa-continue-radiobutton" name="elecoa-exitnew-or-continue" value="2" onclick="javascript:elecoa_on_click_radiobutton();" checked /><label for="elecoa-continue-radiobutton">' . htmlspecialchars(elecoa_get_string('continueactivity')) . '</label>';
        echo '</div>';
        echo '<div class="elecoa-buttons"><a id="elecoa-exitnew-button" class="elecoa-nav-button" style="display:none;" href="./exitstart.php?' . ($id ? ('id=' . urlencode($id)) : ('e=' . urlencode($e))) . '"><span class="elecoa-nav-button-mark">&#x25B6;</span><span class="elecoa-nav-button-space"></span>' . htmlspecialchars(elecoa_get_string('start')) . '</a><a id="elecoa-continue-button" class="elecoa-nav-button" href="./continue.php?' . ($id ? ('id=' . urlencode($id)) : ('e=' . urlencode($e))) . '"><span class="elecoa-nav-button-mark"><img src="./images/resume.png" alt="continue" /></span><span class="elecoa-nav-button-space"></span>' . htmlspecialchars(elecoa_get_string('continue')) . '</a></div>';
    }
    else if ($res) {
        echo '<script>';
        echo 'function elecoa_on_click_radiobutton() {';
        echo     'if (document.getElementById("elecoa-new-radiobutton").checked) {';
        echo         'document.getElementById("elecoa-new-button").setAttribute("style", "display:inline;");';
        echo         'document.getElementById("elecoa-resume-button").setAttribute("style", "display:none;");';
        echo     '}';
        echo     'else {';
        echo         'document.getElementById("elecoa-new-button").setAttribute("style", "display:none;");';
        echo         'document.getElementById("elecoa-resume-button").setAttribute("style", "display:inline;");';
        echo     '}';
        echo '}';
        echo '</script>';
        echo '<div class="elecoa-choices">';
        echo '<input type="radio" id="elecoa-new-radiobutton" name="elecoa-new-or-resume" value="1" onclick="javascript:elecoa_on_click_radiobutton();" /><label for="elecoa-new-radiobutton">' . htmlspecialchars(elecoa_get_string('startactivity')) . '</label>';
        echo '<input type="radio" id="elecoa-resume-radiobutton" name="elecoa-new-or-resume" value="2" onclick="javascript:elecoa_on_click_radiobutton();" checked /><label for="elecoa-resume-radiobutton">' . htmlspecialchars(elecoa_get_string('resumeactivity')) . '</label>';
        echo '</div>';
        echo '<div class="elecoa-buttons"><a id="elecoa-new-button" class="elecoa-nav-button" style="display:none;" href="' . $startNewUrl . '"><span class="elecoa-nav-button-mark">&#x25B6;</span><span class="elecoa-nav-button-space"></span>' . htmlspecialchars(elecoa_get_string('start')) . '</a><a id="elecoa-resume-button" class="elecoa-nav-button" href="' . $startResumeUrl . '"><span class="elecoa-nav-button-mark"><img src="./images/resume.png" alt="resume" /></span><span class="elecoa-nav-button-space"></span>' . htmlspecialchars(elecoa_get_string('resume')) . '</a></div>';
    }
    else {
        if (!$showtoc) {
            echo '<div class="elecoa-buttons"><a id="elecoa-new-button" class="elecoa-nav-button" href="' . $startNewUrl . '"><span class="elecoa-nav-button-mark">&#x25B6;</span><span class="elecoa-nav-button-space"></span>' . htmlspecialchars(elecoa_get_string('start')) . '</a></div>';
        } else {
            echo '<script>';
            echo 'top.location.href = "'. $startNewUrl . '";';
            echo '</script>';
        }
    }
}

// Finish the page
echo $OUTPUT->footer();
