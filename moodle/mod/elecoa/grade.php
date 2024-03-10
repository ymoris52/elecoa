<?php

require_once("../../config.php");

$id = required_param('id', PARAM_INT); // Course module ID.

redirect('view.php?id='.$id);
