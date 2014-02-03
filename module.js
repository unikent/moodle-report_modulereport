M.report_modulereport = {
    Y : null,
    transaction : [],

	init: function (Y) {
        var box = Y.one('.modulereportbox .contents');
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

                    try {
                        var data = Y.JSON.parse(o.responseText);
                        if (data.error) {
                            box.setHTML(data.error);
                        }
                        else {
                            box.setHTML(data.content);

                            Y.all('.module_cell').on('click', function (e) {
                                M.report_modulereport.cellClick(Y, e.target);
                            });
                        }
                    } catch (e) {
                        box.setHTML('Error');
                    }
                }
            }
        });
	},

    cellClick : function(Y, cell) {
        var mid = cell.getAttribute("mid");
        var cid = cell.getAttribute("cid");

        if (!mid || !cid || cell.getHTML() == "0") {
            return;
        }

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
                sesskey: M.cfg.sesskey,
                mid: mid,
                cid: cid
            },
            on: {
                success: function (x, o) {
                    spinner.hide();

                    try {
                        var data = Y.JSON.parse(o.responseText);
                        box.setHTML(data.content);
                    } catch (e) {
                        box.setHTML('Error');
                    }
                }
            }
        });
    }
};