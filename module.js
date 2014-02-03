M.report_modulereport = {
    Y : null,
    transaction : [],

	init: function (Y) {
        var box = Y.one('.modulereportbox');
		var spinner = M.util.add_spinner(Y, box);
		spinner.show();

        $('#module-list').dialog({
            modal: true,
            autoOpen: false,
            width: 350,
            height: 400,
            resizable: false,
            draggable: false
        });

        Y.io(M.cfg.wwwroot + "/report/modulereport/ajax.php", {
            timeout: 120000,
            method: "GET",
            data: {
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (x, o) {
                    spinner.hide();

                    try {
                        var data = Y.JSON.parse(o.responseText);
                        if (data.error) {
                            box.setHTML(data.error);
                        }
                        else {
                            box.setHTML(data.content);
                        }
                    } catch (e) {
                        box.setHTML('Error.');
                    }
                }
            }
        });
	}
};