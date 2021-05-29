<DOCTYPE html>
<html>

<head>
<meta http-equiv="Content-Type" content="no-cache">
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title></title>
</head>
<script type="text/javascript">

function page_load()
{
        $("#apps_mySyncAbout_button").click(function(){
	        $("#mySyncAbout").show();
	        $("#mySyncShares").hide();
	        $("#mySyncFlows").hide();
	        $("#mySyncLogs").hide();
	});
        $("#apps_mySyncShares_button").click(function(){
                $("#mySyncAbout").hide();
                $("#mySyncShares").show();
                $("#mySyncFlows").hide();
                $("#mySyncLogs").hide();
	});
        $("#apps_mySyncFlows_button").click(function(){
                $("#mySyncAbout").hide();
                $("#mySyncShares").hide();
                $("#mySyncFlows").show();
                $("#mySyncLogs").hide();
	});
        $("#apps_mySyncLogs_button").click(function(){
                $("#mySyncAbout").hide();
                $("#mySyncShares").hide();
                $("#mySyncFlows").hide();
                $("#mySyncLogs").show();
	});
}

function page_unload()
{
	$("#apps_mySyncAbout_button").unbind('click');
	$("#apps_mySyncShares_button").unbind('click');
	$("#apps_mySyncFlows_button").unbind('click');
	$("#apps_mySyncLogs_button").unbind('click');

//        $('.Tooltip').remove();
//        unbind_dialog_buttons();

//        clearTimeout(timeoutId);
}
</script>
<body onload="page_load()" onunload="page_unload();">
<div class="h1_content header_2"><span class="_text_app" lang="_menu" datafld="mysync_management">mySync Management Panel</span></div>
<button type="button" class="field_top" id="apps_mySyncAbout_button"><span class="_text_app" lang="_about" datafld="goto_about">About</span></button>
<button type="button" class="field_top" id="apps_mySyncShares_button"><span class="_text_app" lang="_shares" datafld="goto_shares">Remote Shares</span></button>
<button type="button" class="field_top" id="apps_mySyncFlows_button"><span class="_text_app" lang="_flows" datafld="goto_flows">Backup Flows</span></button>
<button type="button" class="field_top" id="apps_mySyncLogs_button"><span class="_text_app" lang="_logs" datafld="goto_logs">Logs</span></button>
<div class="hr_0_content"><div class="hr_1"></div></div>
<div id="mySyncAbout" class="_text_app">
	<span class="_text_app" lang="_about" datafld="about">
		<p>mySync is a WD myCloud Addon for automatic synchronization with Cloud Storage (using rcloud).</p>
		<p><a href="https://github.com/Czuz/mySync-for-myCloud-OS5">https://github.com/Czuz/mySync-for-myCloud-OS5</a></p>
	</span>
</div>
<div id="mySyncShares" class="_text_app" style="display:none;">
	<span class="_text_app" lang="_shares" datafld="shares">Shares</span>
</div>
<div id="mySyncFlows" class="_text_app" style="display:none;">
	<span class="_text_app" lang="_flows" datafld="flows">Flows</span>
</div>
<div id="mySyncLogs" class="_text_app" style="display:none;">
	<span class="_text_app" lang="_logs" datafld="logs">Logs</span>
</div>

</body>
</html>
