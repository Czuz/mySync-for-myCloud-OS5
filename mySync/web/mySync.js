var errorLevel = {
    Error: 2,
    Warning: 1,
    Info: 0
}

var confType = {
    Remotes: 1,
    Flows: 2
}

function page_load() {
    $("#apps_mySyncAbout_button").click(function() {
        $("#mySyncAbout").show();
        $("#mySyncRemotes").hide();
        $("#mySyncFlows").hide();
    });
    $("#apps_mySyncRemotes_button").click(function() {
        $("#mySyncAbout").hide();
        $("#mySyncRemotes").show();
        $("#mySyncFlows").hide();
        if (!$("#apps_mySyncRemotes_info").val()) {
            get_remotes_conf();
        }
    });
    $("#apps_mySyncFlows_button").click(function() {
        $("#mySyncAbout").hide();
        $("#mySyncRemotes").hide();
        $("#mySyncFlows").show();
        if (!$("#apps_mySyncFlows_input").val()) {
            get_flows_conf();
        }
        if (!$("#apps_mySyncFlows_info").val()) {
            get_remotes_conf();
        }
    });
    $("#apps_mySyncFlows_input").on('input', function () {
        this.style.height = "";
        this.style.height = (5+((this.scrollHeight < 95) ? 95 : (this.scrollHeight > 345) ? 345 : this.scrollHeight)) + "px";
    });
    $("#apps_mySyncFlows_save, #apps_mySyncRemotes_save").click(function() {
        const ONE_HOUR = 60 * 60 * 1000; /* 1 hour */
        const backup_time = $('#' + $(this).attr('id').slice(0,-5) + '_backuptime').val();

        if (!$('#' + $(this).attr('id').slice(0,-5) + '_backup').is(':checked') && (!backup_time || new Date(backup_time) < new Date(new Date() - ONE_HOUR))) {
            $("#apps_mySync_backupModal_message").empty();
            if (backup_time) {
                $("#apps_mySync_backupModal_message").append("Your last backup has been created on ").append(backup_time);
            } else {
                $("#apps_mySync_backupModal_message").append("You don't have a backup yet.");
            }
            $("#apps_mySync_backupModal_nobackup").unbind('click');
            $("#apps_mySync_backupModal_withbackup").unbind('click');
            if ($(this).attr('id') == "apps_mySyncFlows_save") {
                $("#apps_mySync_backupModal_nobackup").click(function() {
                    set_flows_conf()
                });
                $("#apps_mySync_backupModal_withbackup").click(function() {
                    set_flows_conf(force_backup = true)
                });
            } else {
                $("#apps_mySync_backupModal_nobackup").click(function() {
                    set_remotes_conf()
                });
                $("#apps_mySync_backupModal_withbackup").click(function() {
                    set_remotes_conf(force_backup = true)
                });
            }
            $("#apps_mySync_backupModal").modal('show');
        } else {
            if ($(this).attr('id') == "apps_mySyncFlows_save") {
                set_flows_conf();
            } else {
                set_remotes_conf();
            }
        }
    });
    $("#apps_mySyncFlows_restore").click(function() {
        restore_flows_conf();
    });
    $("#apps_mySyncRemotes_restore").click(function() {
        restore_remotes_conf();
    });
    $('#apps_mySyncLogs_filter').keypress(function(e) {
        if (e.which == 13) {
            let filter = $("#apps_mySyncLogs_filter").val();
            list_files(filter, function() {
                view_file(select_file(), filter);
            });
            return false;
        }
    });
    $("#apps_mySyncLogs_downloadFile_button").click(function() {
        download_file(select_file());
    });
    $("#apps_mySyncLogs_refresh_button").click(function() {
        let filter = $("#apps_mySyncLogs_filter").val();
        list_files(filter, function() {
            view_file(select_file(), filter);
        });
    });
    $("#apps_mySyncLogs_deleteFile_button").click(function() {
        delete_file(select_file());
    });
    $('#apps_mySyncLogs_currentLogModal').on('shown.bs.modal', function () {
        $('#apps_mySync_notifications').detach().appendTo('#apps_mySync_notifications_loc2');
        $('#apps_mySyncLogs_filter').focus();
        list_files(null, function() {
            view_file(select_file());
        });
    });
    $('#apps_mySyncLogs_currentLogModal').on('hidden.bs.modal', function () {
        $('#apps_mySync_notifications').detach().appendTo('#apps_mySync_notifications_loc1');
    });
}

function page_unload() {
    $("#apps_mySyncLogsSubMenu").empty();
    $("#apps_mySyncLogsContent").empty();
    $("#apps_mySyncAbout_button").unbind('click');
    $("#apps_mySyncRemotes_button").unbind('click');
    $("#apps_mySyncFlows_button").unbind('click');
    $("#apps_mySyncFlows_save, #apps_mySyncRemotes_save").unbind('click');
    $("#apps_mySyncFlows_restore, #apps_mySyncRemotes_restore").unbind('click');
    $("#apps_mySyncFlows_input").unbind('input');
    $("#apps_mySyncLogs_filter").unbind('keypress');
    $("#apps_mySyncLogs_downloadFile_button").unbind('click');
    $("#apps_mySyncLogs_deleteFile_button").unbind('click');
    $('#apps_mySyncLogs_currentLogModal').unbind('shown.bs.modal');
    $('#apps_mySyncLogs_currentLogModal').unbind('hidden.bs.modal');
}

function get_remotes_conf() {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "get",
        type: confType.Remotes,
    }, function(data, status) {
        var jsonData = $.parseJSON(data);

        if (jsonData && jsonData.status) {
            $("#apps_mySyncRemotes_info").val(jsonData.data.configuration.join('\n'));
            $("#apps_mySyncFlows_info").val(jsonData.data.configuration.join('\n'));
            if (jsonData.data.hasbackup) {
                $("#apps_mySyncRemotes_restore").removeClass("gray_out").addClass("button").prop( "disabled", false );
                $("#apps_mySyncRemotes_backuptime").val(jsonData.data.backuptime);
            }
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
    });
}

function set_remotes_conf(force_backup = false) {
    var form_data = new FormData();
    form_data.append('remotes', $('#apps_mySyncRemotes_input').prop('files')[0]);
    form_data.append('action', 'set');
    form_data.append('type', confType.Remotes);
    form_data.append('override', ($('input[name="Override"]:checked', '#mySyncRemotes').val() === 'true'));
    form_data.append('restart', $("#apps_mySyncRemotes_restart").is(':checked'));
    form_data.append('backup', force_backup || $("#apps_mySyncRemotes_backup").is(':checked'));

    $.ajax({
        url: '/apps/mySync/ConfigurationManager.php',
        dataType: 'text',
        cache: false,
        contentType: false,
        processData: false,
        data: form_data,
        type: 'post',
        success: function(data, status) {
            var jsonData = $.parseJSON(data);
            $("#apps_mySyncRemotes_backup").prop('checked', false);

            if (jsonData && jsonData.status) {
                display_notification('Configuration has been updated.', errorLevel.Info);
            } else if (jsonData && !jsonData.status) {
                display_notification(jsonData.error.message, errorLevel.Error);
            } else {
                display_notification("Operation has failed from unknown reason.", errorLevel.Error);
            }
            get_remotes_conf();
        }
    });
}

function restore_remotes_conf() {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "restore",
        type: confType.Remotes,
        restart: $("#apps_mySyncRemotes_restart").is(':checked'),
    }, function(data, status) {
        var jsonData = $.parseJSON(data);
        $("#apps_mySyncRemotes_backup").prop('checked', false);

        if (jsonData && jsonData.status) {
            display_notification('Configuration has been restored from backup.', errorLevel.Info);
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
        get_remotes_conf();
    });
}

function get_flows_conf() {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "get",
        type: confType.Flows,
    }, function(data, status) {
        var jsonData = $.parseJSON(data);

        if (jsonData && jsonData.status) {
            $("#apps_mySyncFlows_input").val(jsonData.data.configuration).trigger("input");
            if (jsonData.data.hasbackup) {
                $("#apps_mySyncFlows_restore").removeClass("gray_out").addClass("button").prop( "disabled", false );
                $("#apps_mySyncFlows_backuptime").val(jsonData.data.backuptime);
            }
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
    });
}

function set_flows_conf(force_backup = false) {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "set",
        type: confType.Flows,
        input: $("#apps_mySyncFlows_input").val(),
        restart: $("#apps_mySyncFlows_restart").is(':checked'),
        backup: force_backup || $("#apps_mySyncFlows_backup").is(':checked'),
    }, function(data, status) {
        var jsonData = $.parseJSON(data);
        $("#apps_mySyncFlows_backup").prop('checked', false);

        if (jsonData && jsonData.status) {
            display_notification('Configuration has been updated.', errorLevel.Info);
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
        get_flows_conf();
    });
}

function restore_flows_conf() {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "restore",
        type: confType.Flows,
        restart: $("#apps_mySyncFlows_restart").is(':checked'),
    }, function(data, status) {
        var jsonData = $.parseJSON(data);
        $("#apps_mySyncFlows_backup").prop('checked', false);

        if (jsonData && jsonData.status) {
            display_notification('Configuration has been restored from backup.', errorLevel.Info);
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
        get_flows_conf();
    });
}

function list_files(query = null, callback = null) {
    $.post("/apps/mySync/LogViewer.php", {
        action: "list",
        s: query
    }, function(data, status) {
        $("#apps_mySyncLogsSubMenu").empty();
        var jsonData = $.parseJSON(data);
        let count = 0;
        let limit = $("#apps_mySyncLogs_limit").text();
        let selected = $("#apps_mySyncLogs_currentLog").text();
        let found = false;

        if (jsonData && jsonData.status && jsonData.log_files.length > 0) {
            for (var i = 0; (limit == 0 || count < limit) && i < jsonData.log_files.length; i++) {
                var log_file = jsonData.log_files[i];
                if (log_file.matchno !== 0) {
                    $("#apps_mySyncLogsSubMenu").append(
                        '<li><span style="display: none;">' +
                        log_file.basename +
                        '</span><a id="' +
                        log_file.basename +
                        '" href="javascript:view_file(\'' +
                        log_file.basename +
                        '\');"><span class="_text">' +
                        log_file.basename.substr(18) +
                        '</span><br/></a></li>'
                    );
                    if (log_file.errno) {
                        $("#" + log_file.basename.replace(/\./g, '\\.')).append(
                            '<span class="statbox"><i class="bi bi-exclamation-circle-fill text-danger"></i> ' + log_file.errno + '</span> '
                        );
                    }
                    if (log_file.warnno) {
                        $("#" + log_file.basename.replace(/\./g, '\\.')).append(
                            '<span class="statbox"><i class="bi bi-exclamation-triangle-fill text-warning"></i> ' + log_file.warnno + '</span> '
                        );
                    }
                    if (log_file.matchno) {
                        $("#" + log_file.basename.replace(/\./g, '\\.')).append(
                            '<span class="statbox"><i class="bi bi-search"></i> ' + log_file.matchno + '</span>'
                        );
                    }
                    if (selected === log_file.basename) {
                        found = true;
                    }
                    count++;
                }
            }
        } else if (jsonData && jsonData.status && jsonData.log_files.length == 0) {
            $("#apps_mySyncLogsContent").append('<li><span class="_text">No logfiles</span></li>');
        } else if (jsonData && !jsonData.status) {
            $("#apps_mySyncLogsContent").append('<li><span class="_text">' + jsonData.error.message + '</span></li>');
        } else {
            $("#apps_mySyncLogsContent").append('<li><span class="_text">Unable to fetch the logfiles</span></li>');
        }

        // Reset selection - selected LogFile is no longer on the list
        if (!found) {
            $("#apps_mySyncLogs_currentLog").text('');
        }
    }).done(callback);
}

function view_file(file, query = null) {
    $("#apps_mySyncLogs_currentLog").text(file);
    if (!query) {
        query = $("#apps_mySyncLogs_filter").val();
    }
    if (file) {
        $(".active").removeClass("active");
        $("#" + file.replace(/\./g, '\\.')).addClass("active");
    }
    $.post("/apps/mySync/LogViewer.php", {
        action: "view",
        f: file,
        s: query
    }, function(data, status) {
        $("#apps_mySyncLogsContent").empty();
        var jsonData = $.parseJSON(data);

        if (jsonData && jsonData.status && jsonData.log_content.length > 0) {
            for (var i = 0; i < jsonData.log_content.length; i++) {
                var log_lineno = jsonData.log_content[i].lineno;
                var log_line = jsonData.log_content[i].line;
                if (log_line) {
                    $("#apps_mySyncLogsContent").append('<li id="' + log_lineno + '"><span style="white-space: pre" class="_text">' + (log_lineno).pad(4) + ': ' + log_line + '</span></li>');
                }
                if (jsonData.log_content[i].haserror) {
                    $("#apps_mySyncLogsContent #" + log_lineno).addClass('bg-danger');
                } else if (jsonData.log_content[i].haswarning) {
                    $("#apps_mySyncLogsContent #" + log_lineno).addClass('bg-warning');
                }
            }
        } else if (jsonData && jsonData.status && jsonData.log_content.length == 0) {
            $("#apps_mySyncLogsContent").append('<li><span class="_text">The logfile is empty</span></li>');
        } else if (jsonData && !jsonData.status) {
            $("#apps_mySyncLogsContent").append('<li><span class="_text">' + jsonData.error.message + '</span></li>');
        } else {
            $("#apps_mySyncLogsContent").append('<li><span class="_text">Unable to fetch the logfile</span></li>');
        }
    });
}

function download_file(file) {
    $.post("/apps/mySync/LogViewer.php", {
        action: "get",
        f: file
    }, function(data, status) {
        var jsonData = $.parseJSON(data);

        if (jsonData && jsonData.status) {
            var a = document.createElement('a');
            var url = window.URL.createObjectURL(new Blob([jsonData.file], {type: "application/octet-stream"}));
            a.href = url;
            a.download = file;
            document.body.append(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
    });
};

function delete_file(file) {
    $.post("/apps/mySync/LogViewer.php", {
        action: "delete",
        f: file
    }, function(data, status) {
        // TODO
        display_notification("Operation not supported", errorLevel.Info)
    });
}

function select_file() {
    file = $("#apps_mySyncLogs_currentLog").text();
    if (!file) {
        file = $("#apps_mySyncLogsSubMenu").find("span").first().text();
    }
    return file;
}

Number.prototype.pad = function(size) {
    var s = String(this);
    while (s.length < (size || 2)) {
        s = " " + s;
    }
    return s;
}

function display_notification(message, level = errorLevel.Info) {
    const alertClass = (level == errorLevel.Info) ? 'alert-info' : ((level == errorLevel.Warning) ? 'alert-warning' : 'alert-danger');

    $("#apps_mySync_notifications").append(
        '<div class="alert ' + alertClass + '" role="alert" style="width: 100%; text-align: left;">' + message + '</div>'
    );

    $(".alert").delay(3000).slideUp(200, function() {
        $(this).alert('close');
    });
}
