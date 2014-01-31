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

<script type="text/javascript">
	$(function () {

	    var data = [];

		var spinner = M.util.add_spinner(Y, Y.one('#faculty-list'));
		spinner.show();

	    YUI().use("node", "io", "dump", "json-parse", function (Y) {
	        var callback = {

	            timeout: 120000,
	            method: "GET",
	            data: {
	                sesskey: M.cfg.sesskey
	            },

	            on: {
	                success: function (x, o) {
	                	spinner.hide();
	                    // Process the JSON data returned from the server
	                    try {
	                        data = Y.JSON.parse(o.responseText);
	                        // set up module list dialog
	                        $('#module-list').dialog({
	                            modal: true,
	                            autoOpen: false,
	                            width: 350,
	                            height: 400,
	                            resizable: false,
	                            draggable: false
	                        });

	                        String.prototype.format = function () {
	                            var formatted = this;
	                            for (arg in arguments) {
	                                formatted = formatted.replace("{" + arg + "}", arguments[arg]);
	                            }
	                            return formatted;
	                        };

	                        var moodleCourseUrl = M.cfg.wwwroot + '/course/view.php?id={0}';
	                        var moodleSchoolUrl = M.cfg.wwwroot + '/course/index.php?categoryid={0}';
	                        var moodleModuleUrl = M.cfg.wwwroot;

	                        var moduleList = [];
	                        for (moduleName in data[0].moduleCount) {
	                            moduleList.push(moduleName);
	                        }
	                        moduleList.sort();

	                        var iterateSchools = function (schools, container, nesting) {

	                            $.each(schools, function (index, school) {

	                                var indicator = '';
	                                for (var i = 0; i < nesting; i++) indicator += '&raquo; ';
	                                var name = indicator + "<a href='" + moodleSchoolUrl.format(school.id) + "'>" + school.name + "</a>";

	                                var dataset = $("<tr class='data-set'><td class='cat'>" + name + "</td></tr>").appendTo(container);

	                                // iterate the module list and display this school's count for
	                                // each module
	                                $.each(moduleList, function (i, moduleName) {

	                                    var moduleCount = school.moduleCount[moduleName];

	                                    if (moduleCount > 0) {

	                                        var cell = $("<td class='data'>" + moduleCount + "</td>").appendTo(dataset);

	                                        cell.addClass('active');

	                                        if (school.courses.length > 0) {

	                                            //var offset = cell.offset();

	                                            // create a pop up box to show the list of courses using this module
	                                            //var courseBox = $("<div class='courseBoxWrapper'><div class='courseBox'><div class='arrow'></div><div class='innerArrow'></div><div class='inner'><table></table></div></div></div>")
	                                            //	.css('top', offset.top-33).css('left', offset.left-34).hide();

	                                            var dialogContent = $("<div class='courseBoxWrapper'><table></table></div>");

	                                            $.each(school.courses, function (i, course) {

	                                                // we only want to stick this course in this box if it's actually used
	                                                // this module, so...
	                                                var useCourse = false;

	                                                $.each(course.moduleCount, function (_moduleName, _count) {

	                                                    if (!useCourse && _moduleName == moduleName && _count > 0) {
	                                                        useCourse = true;
	                                                        console.log(course.name);console.log(_moduleName);console.log(_count);
	                                                    }
	                                                });

	                                                if (useCourse) {

	                                                    $('table', dialogContent).append("<tr><td class='name'><a href='" + moodleCourseUrl.format(course.id) + "'>" + course.name + "</a></td><td class='count'>" + course.moduleCount[moduleName] + "</td></tr>");

	                                                }
	                                            });

	                                            //courseBox.appendTo($('body'));
	                                            // replace content of the module-list dialog with new content
	                                            //$('#module-list').html(dialogContent);

	                                            // add onclick event so when this module count is clicked, the list of
	                                            // courses using this module is displayed
	                                            cell.click(function () {

	                                                var moduleList = $('#module-list');

	                                                if (moduleList.dialog('isOpen')) {
	                                                    moduleList.dialog('close');
	                                                }

	                                                moduleList.html(dialogContent);
	                                                moduleList.dialog('open');

	                                                // hide all other course boxes
	                                                /*if (courseBox.css('display') == 'none') {
														// hide other boxes and display this one
														$('.courseBoxWrapper').stop().fadeOut(100);
														courseBox.stop().fadeIn(350);
													}
													else {
														courseBox.stop().fadeOut(100);
													}*/
	                                            });
	                                        }

	                                    } else {
	                                        var cell = $("<td class = 'data empty'></td>").appendTo(dataset);
	                                    }

	                                });

	                                if (school.children) {
	                                    // has children, so iterate those too
	                                    //console.log('iterating children of [' + school.name + ']');
	                                    iterateSchools(school.children, container, nesting + 1);
	                                }
	                            });
	                        }

	                        var facultyCounter = 1;

	                        $.each(data, function (index, faculty) {

	                            var name = faculty.name;

	                            if (name.toLowerCase().indexOf('faculty of science') != -1 || name.toLowerCase().indexOf('faculty of humanities') != -1 || name.toLowerCase().indexOf('faculty of social') != -1) {

	                                // create the tab bar li item
	                                var ul = $('#faculty-list');
	                                $("<li><a href='#tabs-" + facultyCounter + "'>" + faculty.name + "</a></li>").appendTo(ul);

	                                // create the tab container for this faculty
	                                var tabs = $('#tabs');

	                                var tabContainer = $("<div id='tabs-" + facultyCounter + "'></div>").appendTo(tabs);
	                                var dataTable = $("<table class='data-table'></table>").appendTo(tabContainer);

	                                // create table headings

	                                var labelTable = $("<thead class='labels'><tr></tr></thead>").appendTo(dataTable);
	                                $('<td class="cat">Categories</td>').appendTo(labelTable);

	                                $.each(moduleList, function (index, moduleName) {

	                                    var dataLabels = $("<td>" + moduleName + "</td>").appendTo(labelTable);
	                                });

	                                //$('<div class="clear"></div>').appendTo(dataTable);

	                                // now start iterating the schools within this faculty 
	                                if (faculty.children) {
	                                    //console.log('iterating schools for ' + name);
	                                    iterateSchools(faculty.children, dataTable, 0);
	                                }

	                                facultyCounter++;
	                            }


	                        });

	                        $("#tabs").tabs();

	                    } catch (e) {
	                        return;
	                    }

	                    if (data.error) {
	                        data = [];
	                    }
	                },

	                failure: function (x, o) {
	                    data = '';
	                }
	            }
	        }
	        Y.io(M.cfg.wwwroot + "/report/modulereport/ajax.php", callback);
	    });
	});
</script>

<?php
echo $OUTPUT->footer();
