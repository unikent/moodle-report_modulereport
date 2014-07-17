<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

if (!has_capability('moodle/site:config', \context_system::instance())) {
    print_error("Not allowed!");
}

$cid = optional_param('cid', null, PARAM_INT);
$mid = optional_param('mid', null, PARAM_INT);

$table = new \html_table();

// Are we printing a specific module?
if (!empty($cid) && !empty($mid)) {
    $module = $DB->get_record('modules', array(
        'id' => $mid
    ));

    $list = \report_modulereport\reporting::get_instances_for_category($cid, $mid);

    $table->head = array(get_string('courseshortname', 'hub'), get_string('count', 'tag'));
    if ($module->name == "forum") {
        $table->head[] = "Post Count";
    }

    $table->attributes = array('class' => 'admintable generaltable');
    $table->data = array();

    foreach ($list as $item) {
        $namecell = new \html_table_cell(\html_writer::tag('a', $item->shortname, array(
            'href' => $CFG->wwwroot . '/course/view.php?id=' . $item->cid,
            'target' => '_blank'
        )));

        $row = array(
            $namecell,
            $item->mcount
        );

        if ($module->name == "forum") {
            // Also add a cell for post counts.
            $obj = new \report_studentactivity\data();
            $row[] = $obj->forum_count($item->cid);
        }

        $table->data[] = new \html_table_row($row);
    }
} else {
    // Nope, print them all!
    $categories = \report_modulereport\reporting::get_modules_by_category();
    $dbmodules = \report_modulereport\reporting::get_modules();

    $table->head = array(
        get_string('category')
    );
    foreach ($dbmodules as $id => $module) {
        $table->head[] = get_string('modulename', 'mod_' . $module);
    }

    $table->attributes = array('class' => 'admintable generaltable');
    $table->data = array();

    // Populate table.
    foreach ($categories as $cid => $data) {
        $category = $data['category'];
        $modules = $data['modules'];

        $catcell = new \html_table_cell(\html_writer::tag('a', $category, array(
            'href' => $CFG->wwwroot . '/course/index.php?categoryid=' . $cid,
            'target' => '_blank'
        )));

        $cells = array($catcell);
        foreach ($modules as $mid => $mod) {
            $cell = new \html_table_cell($mod);
            $cell->attributes['class'] = 'module_cell';
            $cell->attributes['cid'] = $cid;
            $cell->attributes['mid'] = $mid;
            $cells[] = $cell;
        }

        $table->data[] = new \html_table_row($cells);
    }
}

echo $OUTPUT->header();
echo json_encode(array(
    "content" => \html_writer::table($table)
));