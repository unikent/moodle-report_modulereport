<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());

$PAGE->set_url('/report/modulereport/index.php');
$PAGE->set_pagelayout('datool');

require_login();

/**
 * jQuery
 */
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_init_call('M.report_modulereport.init', array(), false, array(
    'name' => 'report_modulereport',
    'fullpath' => '/report/modulereport/module.js',
	'requires' => array("node", "io", "dump", "json-parse")
));

/**
 * Our CSS
 */
$PAGE->requires->css('/report/modulereport/styles/styles.css');

echo $OUTPUT->header();
?>

<h1 class="main_title">Moodle module usage report</h1>

<div id='module-list' title='Module list'>
	
</div>

<div id="tabs">
	<ul id='faculty-list'>
		<!--<li><a href="#tabs-1">Faculty of Science. Technology and Medical Studies</a></li>
		<li><a href="#tabs-2">Faculty of Humanities</a></li>
		<li><a href="#tabs-3">Faculty of Scocial Sciences</a></li>-->
	</ul>
</div>

<?php
echo $OUTPUT->footer();
