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

                            Y.all('.cell').on('click', function (e) {
                                M.report_modulereport.cellClick(Y, e.target);
                            });
                        }
                    } catch (e) {
                        box.setHTML('Error.');
                    }
                }
            }
        });
	},

    cellClick : function(Y, cell) {
        var dialog = new Y.Panel({
            contentBox : Y.Node.create('<div id="dialog" />'),
            bodyContent: '<div id="modalmessage"></div>',
            width      : 410,
            zIndex     : 6,
            centered   : true,
            modal      : true,
            render     : '.moduleDialog',
            visible    : true,
            buttons    : {
                footer: [
                    {
                        name     : 'close',
                        label    : 'Close',
                        action   : 'onOK'
                    }
                ]
            }
        });

        dialog.onOK = function (e) {
            e.preventDefault();
            this.hide();
        }

        var box = Y.one('#modalmessage');
        var spinner = M.util.add_spinner(Y, box);
        spinner.show();

        Y.io(M.cfg.wwwroot + "/report/modulereport/ajax.php", {
            timeout: 120000,
            method: "GET",
            data: {
                sesskey: M.cfg.sesskey
            },
            on: {
                success: function (x, o) {
                    spinner.hide();
                }
            }
        });
    }
};