<DOCTYPE html>
<html>

    <head>
        <meta http-equiv="Content-Type" content="no-cache" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    </head>
    <style>
        #apps_mySyncLogsContent {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        #apps_mySyncLogsContent li {
            text-decoration: none;
            font-family: 'Courier New', monospace;
            display: block;
        }

        #apps_mySyncLogsSubMenuContainer {
            height: 100%;
            width: 100%;
            overflow-y: scroll;
            overflow-x: hidden;
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        #apps_mySyncLogsSubMenuContainer::-webkit-scrollbar {
            display: none;
        }

        #apps_mySyncLogsSubMenu {
            list-style-type: none;
            line-height: normal;
            display: block;
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #F4F4F4;
        }

        #apps_mySyncLogsSubMenu li a {
            display: block;
            height: 45px;
            padding: 5px;
            width: 214px;
            word-wrap: break-word;
            text-decoration: none;
            color: #4B5A68;
        }

        #apps_mySyncLogsSubMenu li a:hover {
            background-color: #dcdcdc;
            color: #4B5A68;
        }

        #apps_mySyncLogsSubMenu li a.active {
            background-color: #15abff;
            color: white;
        }

        #apps_mySync_notifications {
            position: absolute;
            z-index: +1;
            left: 50%;
            transform: translateX(-50%);
            min-width: 60%;
        }

        .statbox {
            display: inline-block;
            background-color: #dcdcdc;
            color: #4B5A68;
            border-radius: 10px;
            min-width: 40px;
            text-align: left;
            font-size: 10px;
            padding: 3px;
        }

        @media (min-width: 768px) {
            .modal-xl {
                width: 95%;
                max-width: 1200px;
            }
        }
    </style>
    <script type="text/javascript">
        // Add jQuery to allow testing outside of MyCloud
        if (typeof $ != 'function') {
            document.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"><\/script>');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script type="text/javascript">
        var errorLevel = {
            Error: 2,
            Warning: 1,
            Info: 0
        }

        function page_load() {
            $("#apps_mySyncAbout_button").click(function() {
                $("#mySyncAbout").show();
                $("#mySyncShares").hide();
                $("#mySyncFlows").hide();
            });
            $("#apps_mySyncShares_button").click(function() {
                $("#mySyncAbout").hide();
                $("#mySyncShares").show();
                $("#mySyncFlows").hide();
            });
            $("#apps_mySyncFlows_button").click(function() {
                $("#mySyncAbout").hide();
                $("#mySyncShares").hide();
                $("#mySyncFlows").show();
            });
            $('#apps_mySyncLogs_filter').keypress(function(e) {
                if (e.which == 13) {
                    let filter = $("#apps_mySyncLogs_filter").val();
                    list_files(filter, function() {
                        view_file(selectFile(), filter);
                    });
                    return false;
                }
            });

            list_files(null, function() {
                view_file(selectFile());
            });

            $("#apps_mySyncLogs_downloadFile_button").click(function() {
                download_file(selectFile());
            });

            $("#apps_mySyncLogs_deleteFile_button").click(function() {
                delete_file(selectFile());
            });

            $('#apps_mySyncLogs_currentLogModal').on('shown.bs.modal', function () {
                $('#apps_mySyncLogs_filter').focus();
            });
        }

        function page_unload() {
            $("#apps_mySyncLogsSubMenu").empty();
            $("#apps_mySyncLogsContent").empty();
            $("#apps_mySyncAbout_button").unbind('click');
            $("#apps_mySyncShares_button").unbind('click');
            $("#apps_mySyncFlows_button").unbind('click');
            $("#apps_mySyncLogs_filter").unbind('keypress');
            $("#apps_mySyncLogs_downloadFile_button").unbind('click');
            $("#apps_mySyncLogs_deleteFile_button").unbind('click');
            $('#apps_mySyncLogs_currentLogModal').unbind('shown.bs.modal');
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
                } else {
                    displayNotification(jsonData.error.message, errorLevel.Error);
                }
            });
        };

        function delete_file(file) {
            $.post("/apps/mySync/LogViewer.php", {
                action: "del",
                f: file
            }, function(data, status) {
                // TODO
                displayNotification("Operation not supported", errorLevel.Info)
            });
        }

        function selectFile() {
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

        function displayNotification(message, level = errorLevel.Info) {
            var alertClass = (level == errorLevel.Info) ? 'alert-info' : ((level == errorLevel.Warning) ? 'alert-warning' : 'alert-danger');

            $("#apps_mySync_notifications").append(
                '<div class="alert ' + alertClass + '" role="alert" style="width: 100%; text-align: left;">' + message + '</div>'
            );

            $(".alert").delay(2000).slideUp(200, function() {
                $(this).alert('close');
            });
        }
    </script>

    <body onload="page_load()" onunload="page_unload();">
        <div class="h1_content header_2"><span class="_text_app" lang="_menu" datafld="mysync_management">mySync Management Panel</span></div>
        <button type="button" class="field_top" id="apps_mySyncAbout_button"><span class="_text_app" lang="_about" datafld="goto_about">About</span></button>
        <button type="button" class="field_top" id="apps_mySyncShares_button"><span class="_text_app" lang="_shares" datafld="goto_shares">Remote Shares</span></button>
        <button type="button" class="field_top" id="apps_mySyncFlows_button"><span class="_text_app" lang="_flows" datafld="goto_flows">Backup Flows</span></button>
        <button type="button" class="field_top" id="apps_mySyncLogs_button" data-bs-toggle="modal" data-bs-target="#apps_mySyncLogs_currentLogModal"><span class="_text_app" lang="_logs" datafld="goto_logs">Logs</span></button>
        <div class="hr_0_content">
            <div class="hr_1"></div>
        </div>
        <div id="mySyncAbout" class="_text_app">
            <span class="_text_app" lang="_about" datafld="about">
                <p>mySync is a WD myCloud Addon for automatic synchronization with Cloud Storage (using rclone).</p>
                <p>You might use mySync to periodically sync selected directories or backup whole NAS to other cloud storage of your choice.</p>
                <p>Please refer to the <a href="https://github.com/Czuz/mySync-for-myCloud-OS5">Project</a> homepage for additional details.</p>
            </span>
            <span class="_txt_app" lang="_about" dataflg="credits">
                <p class="h1_content header_2">Credits</p>
                <ul>
                    <li><a href="https://github.com/rclone/rclone/blob/master/COPYING">rclone</a> - Copyright (C) 2012 by Nick Craig-Wood http://www.craig-wood.com/nick/</li>
                    <li><a href="https://github.com/twbs/bootstrap/blob/main/LICENSE">bootstrap</a> - Copyright (c) 2011 Twitter, Inc., Copyright (c) 2011 The Bootstrap Authors</li>
                </ul>
            </span>
        </div>
        <div id="mySyncShares" class="_text_app" style="display:none;">
            <span class="_text_app" lang="_shares" datafld="shares">Planned. Contribute @ <a href="https://github.com/Czuz/mySync-for-myCloud-OS5">https://github.com/Czuz/mySync-for-myCloud-OS5</a></span>
        </div>
        <div id="mySyncFlows" class="_text_app" style="display:none;">
            <span class="_text_app" lang="_flows" datafld="flows">Planned. Contribute @ <a href="https://github.com/Czuz/mySync-for-myCloud-OS5">https://github.com/Czuz/mySync-for-myCloud-OS5</a></span>
        </div>
        <div class="modal fade" data-bs-keyboard="true" id="apps_mySyncLogs_currentLogModal" role="dialog">
            <div class="modal-dialog modal-fullscreen" role="dialog">
                <div class="modal-content">
                    <div id="mySyncLogs" class="modal-body">
                        <span id="apps_mySyncLogs_currentLog" style="display:none;"></span>
                        <span id="apps_mySyncLogs_limit" style="display:none;">0</span>
                            <table style="table-layout: fixed; width: 100%; height: 100%;">
                                <tr>
                                    <td style="width: 214px;">
                                        <input type="text" id="apps_mySyncLogs_filter" placeholder="Search logs...">
                                    </td>
                                    <td style="width: 100%; text-align:right;">
                                        <div id="apps_mySync_notifications"></div>
                                        <button type="button" class="button" id="apps_mySyncLogs_downloadFile_button"><i class="bi bi-download"></i></button>
                                        <button type="button" class="button d-none" id="apps_mySyncLogs_deleteFile_button"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                                <tr style="height: 100%;">
                                    <td>
                                        <div id="apps_mySyncLogsSubMenuContainer">
                                            <ul id="apps_mySyncLogsSubMenu">
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="height: 100%; width: 100%; overflow: auto;">
                                            <ul id="apps_mySyncLogsContent">
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="field" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
