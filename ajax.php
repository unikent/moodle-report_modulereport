<?php

define('AJAX_SCRIPT', true);

/** Include config */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/ajaxlib.php');

if (!has_capability('moodle/site:config', \context_system::instance())) {
	die(json_encode(array("error" => "Not allowed!")));
}

$data = optional_param('sort', 'all', PARAM_ALPHA);
$school = optional_param('school', null, PARAM_INT);

$ajax = new modulereport_ajax();

$content = '';

switch ($data) {
	case 'categories':
		$content = $ajax->get_root_node();
		break;
	case 'course':
		$content = $ajax->get_modules_node($school);
		break;
	case 'all':
	default:
		$content = $ajax->get_content();
		$content = $content->data;
		break;
}

echo json_encode($content, JSON_NUMERIC_CHECK);