M.report_modulereport = {
    Y : null,
    transaction : [],
    spinner : null,

	init: function (Y) {
		M.report_modulereport.spinner = M.util.add_spinner(Y, Y.one('#faculty-list'));
		M.report_modulereport.spinner.show();

		String.prototype.format = function () {
		    var formatted = this;
		    for (arg in arguments) {
		        formatted = formatted.replace("{" + arg + "}", arguments[arg]);
		    }
		    return formatted;
		};

        $('#module-list').dialog({
            modal: true,
            autoOpen: false,
            width: 350,
            height: 400,
            resizable: false,
            draggable: false
        });

		M.report_modulereport.grabRoot(Y);
	},

	grabRoot: function (Y) {
		Y.io(M.cfg.wwwroot + "/report/modulereport/ajax.php?data=categories", {
			timeout: 120000,
            method: "GET",
            data: {
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (x, o) {
                	M.report_modulereport.spinner.hide();

                    try {
                        var data = Y.JSON.parse(o.responseText);

                        // Sort the module list.
                        var moduleList = [];
                        for (moduleName in data[0].moduleCount) {
                            moduleList.push(moduleName);
                        }
                        moduleList.sort();

                        // Go through all faculties.
                        var facultyCounter = 1;
                        $.each(data, function (index, faculty) {
                            var name = faculty.name;
                            if (name.toLowerCase().indexOf('faculty of science') != -1 || name.toLowerCase().indexOf('faculty of humanities') != -1 || name.toLowerCase().indexOf('faculty of social') != -1) {
                                // Create the tab bar li item
                                var ul = $('#faculty-list');
                                $("<li><a href='#tabs-" + facultyCounter + "'>" + faculty.name + "</a></li>").appendTo(ul);

                                // Create the tab container for this faculty
                                var tabs = $('#tabs');

                                var tabContainer = $("<div id='tabs-" + facultyCounter + "'></div>").appendTo(tabs);
                                var dataTable = $("<table class='data-table'></table>").appendTo(tabContainer);

                                // Create table headings
                                var labelTable = $("<thead class='labels'><tr></tr></thead>").appendTo(dataTable);
                                $('<td class="cat">Categories</td>').appendTo(labelTable);

                                $.each(moduleList, function (index, moduleName) {
                                    $("<td>" + moduleName + "</td>").appendTo(labelTable);
                                });

                                // Now start iterating the schools within this faculty 
                                if (faculty.children) {
                                    M.report_modulereport.iterateSchools(Y, moduleList, faculty.children, dataTable, 0);
                                }

                                facultyCounter++;
                            }
                        });

                        $("#tabs").tabs();
                    } catch (e) {
                    	console.log(e);
                        return;
                    }
                }
            }
		});
	},

    iterateSchools: function (Y, moduleList, schools, container, nesting) {

        var moodleSchoolUrl = M.cfg.wwwroot + '/course/index.php?categoryid={0}';
        var moodleModuleUrl = M.cfg.wwwroot;

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
                        // add onclick event so when this module count is clicked, the list of
                        // courses using this module is displayed
                        cell.click(function () {
                        	M.report_modulereport.grabCourse(Y, school, moduleName);
                        });
                    }

                } else {
                    var cell = $("<td class='data empty'></td>").appendTo(dataset);
                }

            });

            if (school.children) {
                // has children, so iterate those too
                M.report_modulereport.iterateSchools(Y, moduleList, school.children, container, nesting + 1);
            }
        });
    },

	grabCourse: function (Y, school, module) {
        var moodleCourseUrl = M.cfg.wwwroot + '/course/view.php?id={0}';
    	/* Skylar -  TODO (ajax)
		Y.io(M.cfg.wwwroot + "/report/modulereport/ajax.php?data=course", {
			timeout: 120000,
            method: "GET",
            data: {
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (x, o) {
                	try {
                        var data = Y.JSON.parse(o.responseText);
                        console.log(data);
                    } catch (e) {
                    	console.log(e);
                        return;
                    }
                }
            }
        });*/
	    var dialogContent = $("<div class='courseBoxWrapper'><table></table></div>");

	    $.each(school.courses, function (i, course) {

	        // we only want to stick this course in this box if it's actually used
	        // this module, so...
	        var useCourse = false;
	        $.each(course.moduleCount, function (_moduleName, _count) {

	            if (!useCourse && _moduleName == moduleName && _count > 0) {
	                useCourse = true;
	            }
	        });

	        if (useCourse) {
	            $('table', dialogContent).append("<tr><td class='name'><a href='" + moodleCourseUrl.format(course.id) + "'>" + course.name + "</a></td><td class='count'>" + moduleCount + "</td></tr>")
	        }
	    });

        var moduleList = $('#module-list');

        if (moduleList.dialog('isOpen')) {
            moduleList.dialog('close');
        }

        moduleList.html(dialogContent);
        moduleList.dialog('open');
	}
};