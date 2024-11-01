    jQuery(document).ready(function() {
	ajaxcaller_s();
        setInterval(ajaxcaller_s, ajax_wpusertracker_update.refresh_time);
    });
	jQuery(function() {
		jQuery(".wpusertrackertooltip").tooltip();
	});
    function ajaxcaller_s() {
	if (ajax_wpusertracker_update!=undefined)
	{
		var data = {
			action: 'ajax_wpusertracker_update',
			update: 1,
			post_datas: ajax_wpusertracker_update.post_datas,
			single: ajax_wpusertracker_update.single,
			slider: ajax_wpusertracker_update.slider,
		};
		jQuery.post(ajax_wpusertracker_update.ajaxurl, data, function(response) {
		var slider = false;
			var n=response.split("|");
			if (n[11]) 
			{
				var store_params = decode64s(n[11]);
				var store = store_params.split("|");
				setCookies("wpusertracker",store[0],store[1],'days');
			}
			if ((n[9])&&(getCookies("wpusertracker_notification")==null)) 
			{
				var nots = decode64s(n[10]);
				var notopts = nots.split("|");
				var sliderdiv = decode64s(n[9]);
				jQuery("body").append(sliderdiv);
				divscroller_s(notopts[0],notopts[1]);
				slider = true;
				setCookies("wpusertracker_notification",1,notopts[2],'minutes');
			}
			else slider = false;
			if (jQuery('#wpusertracker_usercount').length) jQuery("#wpusertracker_usercount").html(n[0]);
			if (jQuery('#wpusertracker_now_online').length) jQuery("#wpusertracker_now_online").html(n[1]);
			if (jQuery('#wpusertracker_track_today').length)
			{
				if (n[6]>n[2]) jQuery("#wpusertracker_track_today").html('<span class="lower">'+n[2]+'</span>');
				if (n[6]<n[2]) jQuery("#wpusertracker_track_today").html('<span class="higher">'+n[2]+'</span>');
				if (n[6]==n[2]) jQuery("#wpusertracker_track_today").html('<span>'+n[2]+'</span>');
			}
			if (jQuery('#wpusertracker_track_thisweek').length){
				if (n[7]>n[3]) jQuery("#wpusertracker_track_thisweek").html('<span class="lower">'+n[3]+'</span>');
				if (n[7]<n[3]) jQuery("#wpusertracker_track_thisweek").html('<span class="higher">'+n[3]+'</span>');
				if (n[7]==n[3]) jQuery("#wpusertracker_track_thisweek").html('<span>'+n[3]+'</span>');
			}
			if (jQuery('#wpusertracker_track_thismonth').length){
				if (n[8]>n[4]) jQuery("#wpusertracker_track_thismonth").html('<span class="lower">'+n[4]+'</span>');
				if (n[8]<n[4]) jQuery("#wpusertracker_track_thismonth").html('<span class="higher">'+n[4]+'</span>');
				if (n[8]==n[4]) jQuery("#wpusertracker_track_thismonth").html('<span>'+n[4]+'</span>');
			}
			if (jQuery('#wpusertracker_track_alltime').length) jQuery("#wpusertracker_track_alltime").html(n[5]);

		function divscroller_s(direction,fadeouttime) {
				var screen_width = jQuery(window).width();
				if (direction=='left') jQuery('#wpusertracker_notification').css("left",0+'px');
				if (direction=='right') jQuery('#wpusertracker_notification').css("left",screen_width-jQuery("#wpusertracker_notification").width()+'px');
				jQuery('#wpusertracker_notification').show('slide', {direction: ''+direction+''}, 1000);
				setTimeout(function(){remove_notification_s()},parseInt(fadeouttime));
		}
		function remove_notification_s()
		{
				jQuery("#wpusertracker_notification").fadeOut(3000, function() 
				{
					jQuery("#wpusertracker_notification").remove()
				})
		}
		
		function decode64s (data) {
		  var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
			ac = 0,
			dec = "",
			tmp_arr = [];

		  if (!data) {
			return data;
		  }

		  data += '';

		  do {
			h1 = b64.indexOf(data.charAt(i++));
			h2 = b64.indexOf(data.charAt(i++));
			h3 = b64.indexOf(data.charAt(i++));
			h4 = b64.indexOf(data.charAt(i++));
			bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;
			o1 = bits >> 16 & 0xff;
			o2 = bits >> 8 & 0xff;
			o3 = bits & 0xff;
			if (h3 == 64) {
			  tmp_arr[ac++] = String.fromCharCode(o1);
			} else if (h4 == 64) {
			  tmp_arr[ac++] = String.fromCharCode(o1, o2);
			} else {
			  tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
			}
		  } while (i < data.length);
		  dec = tmp_arr.join('');
		  return dec;
		}
		
		function setCookies(c_name,value,dduntil,mode)
		{
		if (mode=='days')
		{
			var exdate=new Date();
			exdate.setDate(exdate.getDate() + parseInt(dduntil));
			var c_value=escape(value) + ((dduntil==null) ? "" : "; expires="+exdate.toUTCString());
			document.cookie=c_name + "=" + c_value;		
		}
		if (mode=='minutes')
		{
			var now=new Date();
			var time = now.getTime();
			time += parseInt(dduntil);
			now.setTime(time);
			var c_value=escape(value) + ((dduntil==null) ? "" : "; expires="+now.toUTCString());
			document.cookie=c_name + "=" + c_value;
		}
		}

		function getCookies(c_name)
		{
		var c_value = document.cookie;
		var c_start = c_value.indexOf(" " + c_name + "=");
		if (c_start == -1)
		  {
		  c_start = c_value.indexOf(c_name + "=");
		  }
		if (c_start == -1)
		  {
		  c_value = null;
		  }
		else
		  {
		  c_start = c_value.indexOf("=", c_start) + 1;
		  var c_end = c_value.indexOf(";", c_start);
		  if (c_end == -1)
		  {
		c_end = c_value.length;
		}
		c_value = unescape(c_value.substring(c_start,c_end));
		}
		return c_value;
		}
			});
    }
	}

