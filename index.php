<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/report/modulereport/index.php');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_init_call('M.report_modulereport.init', array(), false, array(
    'name' => 'report_modulereport',
    'fullpath' => '/report/modulereport/module.js',
	'requires' => array("node", "io", "dump", "json-parse")
));

admin_externalpage_setup('reportmodulereport', '', null, '', array('pagelayout' => 'report'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("modulereport", "report_modulereport"));
echo $OUTPUT->box_start('overflowbox modulereportbox');
echo $OUTPUT->box_end();
echo '<div id="module-list" title="Module list"></div>';
echo $OUTPUT->footer();
