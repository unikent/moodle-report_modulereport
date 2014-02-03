<?php

define('AJAX_SCRIPT', true);

/** Include config */
require_once(dirname(__FILE__) . '/../../config.php');

if (!has_capability('moodle/site:config', \context_system::instance())) {
	die(json_encode(array("error" => "Not allowed!")));
}

$cid = optional_param('cid', null, PARAM_INT);
$mid = optional_param('mid', null, PARAM_INT);

if (!empty($cid) && !empty($mid)) {
	die;
}

$categories = \report_modulereport\reporting::get_modules_by_category();
$db_modules = \report_modulereport\reporting::get_modules();

$table = new \html_table();
$table->head = array(
    get_string("category")
);
$table->attributes = array('class' => 'admintable generaltable');
$table->data = array();

// Populate table.
foreach ($categories as $cid => $data) {
	$category = $data['category'];
	$modules = $data['modules'];

	$cells = array($category);
	foreach ($modules as $mid => $mod) {
		$cell = new \html_table_cell($mod);
		$cell->attributes['cid'] = $cid;
		$cell->attributes['mid'] = $mid;
		$cells[] = $cell;
	}

	$table->data[] = new \html_table_row($cells);

	foreach ($modules as $module => $count) {
		$str = get_string('modulename', 'mod_' . $db_modules[$module]);
		if (!in_array($str, $table->head)) {
			$table->head[] = $str;
		}
	}
}

echo json_encode(array(
	"content" => \html_writer::table($table)
));