<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/report/modulereport/index.php');

admin_externalpage_setup('reportmodulereport', '', null, '', array('pagelayout'=>'report'));

$counts = \report_modulereport\reporting::get_modules_by_category();

$table = new \html_table();
$table->head = array(
    get_string("category")
);
$table->attributes = array('class' => 'admintable generaltable');
$table->data = array();

// Add each module to the header
$modules = array();
foreach ($counts as $data) {
	if (!in_array($data['module'], $modules)) {
		$table->head[] = $data['module'];
		$modules[] = $data['module'];
	}
}

foreach ($modules as $module) {
	foreach ($counts as $data) {
		$cells = array(
			$data['category'],
			$data['module'],
			$data['count']
		);
		$table->data[] = new html_table_row($cells);
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("modulereport", "report_modulereport"));
echo $OUTPUT->box_start();
echo html_writer::table($table);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
