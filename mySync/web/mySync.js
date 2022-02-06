var errorLevel = {
    Error: 2,
    Warning: 1,
    Info: 0
}

var confType = {
    Remotes: {
        id: 1,
        prefix: "apps_mySyncRemotes"
    },
    Flows: {
        id: 2,
        prefix: "apps_mySyncFlows"
    }
}

// Validator
function init_validation() {
    if (typeof $.validator == "undefined") {
        console.log("[mySync] waiting for $.validator...");
        setTimeout(init_validation, 500);
        return false;
    }

    $.validator.setDefaults({
        errorElement: "div",
        errorPlacement: function (error, element) {
            // Add the `invalid-feedback` class to the error element
            error.addClass("invalid-feedback");

            // Add `was-validated` class to the parent form
            // in order to add icons to inputs
            // element.parents(".form-horizontal").addClass("was-validated");

            if (element.prop("type") === "checkbox") {
                error.insertAfter(element.parent("label"));
            } else {
                error.insertAfter(element);
            }
        },
        highlight: function (element, errorClass, validClass) {
            $(element).removeClass("is-valid").addClass("is-invalid");
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        },
    });
    $.validator.addMethod(
        "noUnfinishedLines",
        function(value) {
            return value
                .split('\n')                    // Split by line
                .filter(x => x.length > 0)      // Exclude empty lines
                .map(x => x.split('|'))         // Split by pipe (|)
                // Test if there are no unfinished lines
                .filter(x => x.length < 2).length == 0;
        },
        "Provide at least source and target path separated by pipe ('|')"
    );
    $.validator.addMethod(
        "noUnescapedCharacters",
        function(value) {
            return value
                .split('\n')                    // Split by line
                .filter(x => x.length > 0)      // Exclude empty lines
                .map(x => x.split('|'))         // Split by pipe (|)
                // Test if there are no unescaped characters
                .filter(x => x.length >= 2)
                // Search for lines with unescaped special characters in paths
                .map(x =>
                    (x[0].search(/"/gm) == -1 && (x[0].search(/^'[^']*'$/gm) > -1 || x[0].search(/([^\\\\])([ *?$'])/gm) == -1)) &&
                    (x[1].search(/"/gm) == -1 && (x[1].search(/^'[^']*'$/gm) > -1 || x[1].search(/([^\\\\])([ *?$'])/gm) == -1)))
                // Agregate result to single true or false
                .reduce((p,n) => p && n);
        },
        "If your paths have spaces or shell metacharacters (e.g. *, ?, $, ', etc.) then you must escape them with '\\' (e.g. \\?). Don't use \" character."
    );
    $("#apps_mySyncFlows_form").validate({
        rules: {
            apps_mySyncFlows_input: {
                noUnfinishedLines: true,
                noUnescapedCharacters: true,
            }
        },
        submitHandler: function () {
            submit_configuration(confType.Flows);
        }
    });
    $("#apps_mySyncRemotes_form").validate({
        rules: {
            apps_mySyncRemotes_input: "required",
        },
        messages: {
            apps_mySyncRemotes_input: "Please select a rclone.conf to upload",
        },
        submitHandler: function () {
            submit_configuration(confType.Remotes);
        }
    });
}

// Page controler
function page_load() {
    init_validation();
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
            get_conf(confType.Remotes);
        }
    });
    $("#apps_mySyncFlows_button").click(function() {
        $("#mySyncAbout").hide();
        $("#mySyncRemotes").hide();
        $("#mySyncFlows").show();
        if (!$("#apps_mySyncFlows_input").val()) {
            get_conf(confType.Flows);
        }
        if (!$("#apps_mySyncFlows_info").val()) {
            get_conf(confType.Remotes);
        }
    });
    $("#apps_mySyncFlows_input").on('input', function () {
        this.style.height = "";
        this.style.height = (5+((this.scrollHeight < 95) ? 95 : (this.scrollHeight > 345) ? 345 : this.scrollHeight)) + "px";
    });
    $("#apps_mySyncFlows_restore").click(function() {
        restore_conf(confType.Flows);
    });
    $("#apps_mySyncRemotes_restore").click(function() {
        restore_conf(confType.Remotes);
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
    $("#apps_mySyncFlows_restore, #apps_mySyncRemotes_restore").unbind('click');
    $("#apps_mySyncFlows_input").unbind('input');
    $("#apps_mySyncLogs_filter").unbind('keypress');
    $("#apps_mySyncLogs_downloadFile_button").unbind('click');
    $("#apps_mySyncLogs_refresh_button").unbind('click');
    $("#apps_mySyncLogs_deleteFile_button").unbind('click');
    $('#apps_mySyncLogs_currentLogModal').unbind('shown.bs.modal');
    $('#apps_mySyncLogs_currentLogModal').unbind('hidden.bs.modal');
    $("#apps_mySync_backupModal_nobackup").unbind('click');
    $("#apps_mySync_backupModal_withbackup").unbind('click');
}

function submit_configuration(type) {
    // Check if backup is recomended
    const ONE_HOUR = 60 * 60 * 1000; /* 1 hour */
    const backup_time = $('#' + type.prefix + '_backuptime').val();

    if (!$('#' + type.prefix + '_backup').is(':checked') && (!backup_time || new Date(backup_time) < new Date(new Date() - ONE_HOUR))) {
        // If yes - display modal
        $("#apps_mySync_backupModal_message").empty();
        if (backup_time) {
            $("#apps_mySync_backupModal_message").append("Your last backup has been created on ").append(backup_time);
        } else {
            $("#apps_mySync_backupModal_message").append("You don't have a backup yet.");
        }
        $("#apps_mySync_backupModal_nobackup").unbind('click');
        $("#apps_mySync_backupModal_withbackup").unbind('click');
        $("#apps_mySync_backupModal_nobackup").click(function() {
            set_conf(type)
        });
        $("#apps_mySync_backupModal_withbackup").click(function() {
            set_conf(type, force_backup = true)
        });
        $("#apps_mySync_backupModal").modal('show');
    } else {
        // Otherwise continue without backup notification
        set_conf(type);
    }
}

function get_conf(type) {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "get",
        type: type.id,
    }, function(data, status) {
        var jsonData = $.parseJSON(data);

        if (jsonData && jsonData.status) {
            if (type === confType.Remotes) {
                $("#apps_mySyncRemotes_info").val(jsonData.data.configuration.join('\n'));
                $("#apps_mySyncFlows_info").val(jsonData.data.configuration.join('\n'));
            } else if (type === confType.Flows) {
                $("#apps_mySyncFlows_input").val(jsonData.data.configuration).trigger("input");
            }
            if (jsonData.data.hasbackup) {
                $("#" + type.prefix + "_restore").removeClass("gray_out").addClass("button").prop( "disabled", false );
                $("#" + type.prefix + "_backuptime").val(jsonData.data.backuptime);
            }
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
    });
}

function set_conf(type, force_backup = false) {
    var form_data = new FormData();
    // Action and configuration type
    form_data.append('action', 'set');
    form_data.append('type', type.id);
    // Common parameters
    form_data.append('restart', $("#" + type.prefix + "_restart").is(':checked'));
    form_data.append('backup', force_backup || $("#" + type.prefix + "_backup").is(':checked'));
    // Type specific parameters
    if (type === confType.Remotes) {
        form_data.append('remotes', $('#apps_mySyncRemotes_input').prop('files')[0]);
        form_data.append('override', ($('input[name="Override"]:checked', '#mySyncRemotes').val() === 'true'));
    } else if (type === confType.Flows) {
        form_data.append('input', $("#apps_mySyncFlows_input").val());
    }

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
            $("#" + type.prefix + "_backup").prop('checked', false);

            if (jsonData && jsonData.status) {
                display_notification('Configuration has been updated.', errorLevel.Info);
            } else if (jsonData && !jsonData.status) {
                display_notification(jsonData.error.message, errorLevel.Error);
            } else {
                display_notification("Operation has failed from unknown reason.", errorLevel.Error);
            }
            get_conf(type);
        }
    });
}

function restore_conf(type) {
    $.post("/apps/mySync/ConfigurationManager.php", {
        action: "restore",
        type: type.id,
        restart: $("#" + type.prefix + "_restart").is(':checked'),
    }, function(data, status) {
        var jsonData = $.parseJSON(data);
        $("#" + type.prefix + "_backup").prop('checked', false);

        if (jsonData && jsonData.status) {
            display_notification('Configuration has been restored from backup.', errorLevel.Info);
        } else if (jsonData && !jsonData.status) {
            display_notification(jsonData.error.message, errorLevel.Error);
        } else {
            display_notification("Operation has failed from unknown reason.", errorLevel.Error);
        }
        get_conf(type);
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
