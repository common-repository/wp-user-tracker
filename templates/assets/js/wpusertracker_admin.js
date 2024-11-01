	jQuery(document).ready(function($) {
		var pusertracker = $.noConflict();
		pusertracker(function() {
		pusertracker("#wpusertracker_tabs").tabs();
		});
		 $('#wpusertracker_mvp').dataTable({"aaSorting": [[ 7, "desc" ]]});
		 $('#wpusertracker_mvc').dataTable({"aaSorting": [[ 7, "desc" ]]});
	});
	jQuery(function() {
		jQuery(".wpusertrackertooltip").tooltip();
	});
