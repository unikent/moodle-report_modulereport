<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/report/modulereport/index.php');

$PAGE->requires->js_init_call('M.report_modulereport.init', array(), false, array(
    'name' => 'report_modulereport',
    'fullpath' => '/report/modulereport/module.js',
	'requires' => array("node", "io", "dump", "json-parse", "panel")
));

admin_externalpage_setup('reportmodulereport', '', null, '', array('pagelayout' => 'report'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("modulereport", "report_modulereport"));
echo $OUTPUT->box_start('modulereportbox');
	echo '<p class="floatright"><a href="?format=csv" target="_blank">Download CSV</a></p>';
	echo $OUTPUT->box_start('contents');
	echo $OUTPUT->box_end();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
