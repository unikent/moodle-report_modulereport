<?php 

global $PAGE, $OUTPUT, $CFG;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/report/modulereport/index.php');

admin_externalpage_setup('reportmodulereport', '', null, '', array('pagelayout' => 'report'));

$format = optional_param('format', null, PARAM_ALPHA);

// We exporting a CSV?
if (isset($format) && $format === "csv") {
	require_once($CFG->libdir . '/csvlib.class.php');

	$categories = \report_modulereport\reporting::get_modules_by_category();
	$db_modules = \report_modulereport\reporting::get_modules();

	// Build Heading
	$heading = array(
		get_string("category")
	);
	foreach ($db_modules as $id => $module) {
		$heading[] = get_string('modulename', 'mod_' . $module);
	}

    $csv = array($heading);

	foreach ($categories as $cid => $data) {
		$line = array($data['category']);
		foreach ($data['modules'] as $mid => $mod) {
			$line[] = $mod;
		}
		$csv[] = $line;
	}

	\csv_export_writer::download_array("Module_Report", $csv, "comma");
	die;
}


$PAGE->requires->js_init_call('M.report_modulereport.init', array(), false, array(
    'name' => 'report_modulereport',
    'fullpath' => '/report/modulereport/module.js',
	'requires' => array("node", "io", "dump", "json-parse", "panel")
));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("modulereport", "report_modulereport"));
echo $OUTPUT->box_start('modulereportbox');
	echo '<p class="floatright"><a href="?format=csv" target="_blank">Download CSV</a></p>';
	echo $OUTPUT->box_start('contents');
	echo $OUTPUT->box_end();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
