<?php 
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/filelib.php');

global $PAGE, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/report/modulereport/index.php');

admin_externalpage_setup('reportmodulereport', '', null, '', array('pagelayout'=>'report'));

$categories = \report_modulereport\reporting::get_modules_by_category();
$db_modules = \report_modulereport\reporting::get_modules();

$table = new \html_table();
$table->head = array(
    get_string("category")
);
$table->attributes = array('class' => 'admintable generaltable');
$table->data = array();

// Populate table.
foreach ($categories as $data) {
	$category = $data['category'];
	$modules = $data['modules'];

	$table->data[] = new html_table_row(array_merge(
		array($category),
		$modules
	));

	foreach ($modules as $module => $count) {
		$str = get_string('modulename', 'mod_' . $db_modules[$module]);
		if (!in_array($str, $table->head)) {
			$table->head[] = $str;
		}
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("modulereport", "report_modulereport"));
echo $OUTPUT->box_start('overflowbox');
echo html_writer::table($table);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
