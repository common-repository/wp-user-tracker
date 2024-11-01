<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
?>
<div class="wrap">
	<br /><br />
	<h2>WP User Tracker Pro Settings</h2>
	<h4>Visit the plugin website to get more: <a target="_blank" href="http://sympies.com">Innovative Plugins</a></h4>
	<?php
	if (isset($this->action_result)) {?>
	<div class="updated settings-error" id="setting-error-settings_updated"> 
		<p><strong><?php print($this->action_result);?></strong></p>
	</div>
	<?php }?>
	<br /><br />
	<div id="wpusertracker_tabs">
		<ul>
			<li><a href="#settings">Settings</a></li>
			<li><a href="#counter_options">Counter</a></li>
			<li><a href="#notification_settings">Notification</a></li>
			<li><a href="#targeted_advertisements">Targeted Advertisements</a></li>
			<li><a href="#counter_stats">Counter Stats</a></li>
			<li><a href="#most_viewed_categories">Category stats</a></li>
			<li><a href="#most_viewed_posts">Posts stats</a></li>
			<li><a href="#plugin_directory">Plugin Directory</a></li>
			<li><a href="#help">Help</a></li>
		</ul>
		<div id="settings">
			<p>    
				<form method="post" action="options.php#settings"> 
					<?php @settings_fields('pantherius_wpusertracker_mainsettings-group'); ?>
					<?php @do_settings_fields('pantherius_wpusertracker_mainsettings-group'); ?>
					<?php do_settings_sections('pantherius_wpusertracker_mainsettings'); ?>
					<?php @submit_button(); ?>
				</form>
			</p>
		</div>
		<div id="counter_options">
			<p>    
				<form method="post" action="options.php#counter_options"> 
					<?php @settings_fields('pantherius_wpusertracker_counter-group'); ?>
					<?php @do_settings_fields('pantherius_wpusertracker_counter-group'); ?>
					<?php do_settings_sections('pantherius_wpusertracker_counter'); ?>
					<?php @submit_button(); ?>
				</form>
			</p>
		</div>
		<div id="notification_settings">
			<p>    
				<form method="post" action="options.php#notification_settings"> 
					<?php @settings_fields('pantherius_wpusertracker_notification-group'); ?>
					<?php @do_settings_fields('pantherius_wpusertracker_notification-group'); ?>
					<?php do_settings_sections('pantherius_wpusertracker_notification'); ?>
					<?php @submit_button(); ?>
				</form>
			</p>
		</div>
		<div id="targeted_advertisements">
			<p>    
			<h3>Set up Targeted Advertisements<br /><hr /></h3>
		<?php
			$result = $this->wpdb->get_results("SELECT * FROM ".$this->table_names['adverts']);
			print('<h3 class="ui-widget-header" style="padding:5px;">Update existing adverts</h3>');
				$categories = get_categories();$catselect = '';
					$catselect .= '<option value="0">General</option>';
				foreach($categories as $cts)
				{
					$catselect .= '<option value="'.$cts->term_id.'">'.$cts->name.'</option>';
				}
			if (empty($result)) print("<br /><h4><i>You don't have any adverts yet</i></h4><br />");
			else 
			{
				foreach($result as $key=>$rs) 
				{
					print('<form method="post" action="'.admin_url( 'options-general.php?page=pantherius_wpusertracker#targeted_advertisements', 'http' ).'"><input type="hidden" name="nonce" value="'.wp_create_nonce(basename(__FILE__)).'"/>
					<input type="hidden" name="id" value="'.$rs->id.'" /><table><tr><td>Select category:</td><td><select name="catid">');
					$selected = false;
					if ($rs->catid==0) print('<option value="0" selected="selected">General</option>');
					else print('<option value="0">General</option>');
					foreach($categories as $cts)
					{
						if ($cts->term_id==$rs->catid) $selected = ' selected="selected"';
						else $selected = false;
						?>
						<option value="<?php print($cts->term_id);?>" <?php selected( $cts->term_id, $rs->catid ); ?>><?php print($cts->name);?></option>
						<?
					}
					print('</select></td><td></td></tr>');
					print('<tr><td>Advert HTML code:<td colspan="2"><textarea name="adv" rows="5" cols="80" >'.stripslashes($rs->adv).'</textarea></td></tr>');
					print('<tr><td colspan="2" align="right"><label for="ch'.$key.'">delete this advert <input id="ch'.$key.'" type="checkbox" name="delete_category" value="1" /></td><td align="right">');@submit_button();print('</td></tr></table>');
					if ($key<count($result)-1) print("<hr /><br />");
					print('</form>');
				}
			}
					print('<h3 class="ui-widget-header" style="padding:5px;">Create new advertisement</h3>');
					print('<form method="post" action="'.admin_url( 'options-general.php?page=pantherius_wpusertracker#targeted_advertisements', 'http' ).'"><table><tr><td>Select category:</td><td><input type="hidden" name="nonce" value="'.wp_create_nonce(basename(__FILE__)).'"/>
					<select name="catid">'.$catselect.'</select></td><td></td></tr>');
					print('<tr><td>Advert HTML code:<td colspan="2"><textarea name="adv" rows="5" cols="80" ></textarea></td></tr>');
					print('<tr><td colspan="3" align="right">');@submit_button();print('</td></tr></table>');
					print('</form>');
		?>

			</p>
		</div>
		<div id="counter_stats">
			<p>
			<h3>Counter Stats<br /><hr /></h3>
			<?php
				$allview = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE NOW() <= DATE_ADD(last_visited_time, INTERVAL 5 MINUTE)");
				$today = '';$thisweek = '';$thismonth = '';
				if ($this->wpusertracker_track_datas['wpusertracker_track_thismonth']<$this->wpusertracker_track_datas['wpusertracker_track_lastmonth']) $thismonth = '<span class="lower wpusertracker_righter"></span>';
				if ($this->wpusertracker_track_datas['wpusertracker_track_thismonth']>$this->wpusertracker_track_datas['wpusertracker_track_lastmonth']) $thismonth = '<span class="higher wpusertracker_righter"></span>';
				if ($this->wpusertracker_track_datas['wpusertracker_track_thisweek']<$this->wpusertracker_track_datas['wpusertracker_track_lastweek']) $thisweek = '<span class="lower wpusertracker_righter"></span>';
				if ($this->wpusertracker_track_datas['wpusertracker_track_thisweek']>$this->wpusertracker_track_datas['wpusertracker_track_lastweek']) $thisweek = '<span class="higher wpusertracker_righter"></span>';
				if ($this->wpusertracker_track_datas['wpusertracker_track_today']<$this->wpusertracker_track_datas['wpusertracker_track_yesterday']) $today = '<span class="lower wpusertracker_righter"></span>';
				if ($this->wpusertracker_track_datas['wpusertracker_track_today']>$this->wpusertracker_track_datas['wpusertracker_track_yesterday']) $today = '<span class="higher wpusertracker_righter"></span>';
				print('<table width="90%" cellpadding="50"><tr><td width="20%"><ul><li class="wpusertrackertooltip" title="All time unique visitors"><span id="wpusertracker_alltime_text" class="wpusertracker_larger_size">All time visitors:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_alltime">'.$this->wpusertracker_track_datas['wpusertracker_track_alltime'].'</span></li>');
				print('<li class="wpusertrackertooltip" title="Unique visitors in this month"><span id="wpusertracker_thismonth_text" class="wpusertracker_larger_size">This month:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_thismonth">'.$this->wpusertracker_track_datas['wpusertracker_track_thismonth'].'</span>'.$thismonth.'</li>');
				print('<li class="wpusertrackertooltip" title="Unique visitors on this week"><span id="wpusertracker_thisweek_text" class="wpusertracker_larger_size">This week:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_thisweek">'.$this->wpusertracker_track_datas['wpusertracker_track_thisweek'].'</span>'.$thisweek.'</li>');
				print('<li class="wpusertrackertooltip" title="Total number of unique visitors today"><span id="wpusertracker_today_text" class="wpusertracker_larger_size">Today:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_today">'.$this->wpusertracker_track_datas['wpusertracker_track_today'].'</span>'.$today.'</li>');
				print('<li class="wpusertrackertooltip" title="Unique visitors currently online"><span id="wpusertracker_online_text" class="wpusertracker_larger_size">Now Online:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_now_online">'.$allview.'</span></li></ul></td>');
				print('<td width="20%"><ul><li></li>');
				print('<li class="wpusertrackertooltip" title="Unique visitors in last month"><span id="wpusertracker_thismonth_text" class="wpusertracker_larger_size">Last month:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_thismonth">'.$this->wpusertracker_track_datas['wpusertracker_track_lastmonth'].'</span></li>');
				print('<li class="wpusertrackertooltip" title="Unique visitors on last week"><span id="wpusertracker_thisweek_text" class="wpusertracker_larger_size">Last week:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_thisweek">'.$this->wpusertracker_track_datas['wpusertracker_track_lastweek'].'</span></li>');
				print('<li class="wpusertrackertooltip" title="Total number of unique visitors yesterday"><span id="wpusertracker_today_text" class="wpusertracker_larger_size">Yesterday:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_today">'.$this->wpusertracker_track_datas['wpusertracker_track_yesterday'].'</span></li>');
				print('<li></li></ul></td></tr></table>');
			?>
			</p>
		</div>
		<div id="most_viewed_categories">
			<p>
			<h3>Most viewed categories<br /><hr /></h3>
<?php
			$topcategories = $this->wpdb->get_results("SELECT ct.name,ct.term_id,tc.* FROM ".$this->table_names['categories']." tc LEFT JOIN ".$this->wpdb->prefix."terms ct on tc.catid=ct.term_id ORDER BY tc.alltime DESC LIMIT 5");
?>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="wpusertracker_mvc" width="100%">
	<thead>
		<tr>
			<th>Category name</th>
			<th>Last month</th>
			<th>This month</th>
			<th>Last week</th>
			<th>This week</th>
			<th>Yesterday</th>
			<th>Today</th>
			<th>All time</th>
		</tr>
	</thead>
	<tbody>
	<?php
		foreach($topcategories as $key=>$tc) 
		{
			print('<tr class="odd gradeX">
			<td><a target="_blank" href="'.get_category_link($tc->term_id).'">'.$tc->name.'</a></td>
			<td>'.$tc->lastmonth.'</td>
			<td>'.$tc->thismonth.'</td>
			<td>'.$tc->lastweek.'</td>
			<td>'.$tc->thisweek.'</td>
			<td>'.$tc->yesterday.'</td>
			<td>'.$tc->today.'</td>
			<td>'.$tc->alltime.'</td>
			</tr>');
		}
	?>
	</tbody>
	<tfoot>
		<tr>
			<th>Post/Page name</th>
			<th>Last month</th>
			<th>This month</th>
			<th>Last week</th>
			<th>This week</th>
			<th>Yesterday</th>
			<th>Today</th>
			<th>All time</th>
		</tr>
	</tfoot>
</table>
			</p>

		</div>
		<div id="most_viewed_posts">
			<p>
			<h3>Most viewed posts and pages<br /><hr /></h3>
<?php
			$topposts = $this->wpdb->get_results("SELECT pp.post_title,pp.id,tp.* FROM ".$this->wpdb->prefix."posts pp INNER JOIN ".$this->table_names['posts']." tp on tp.postid=pp.id ORDER BY tp.alltime DESC");
?>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="wpusertracker_mvp" width="100%">
	<thead>
		<tr>
			<th>Post/Page name</th>
			<th>Last month</th>
			<th>This month</th>
			<th>Last week</th>
			<th>This week</th>
			<th>Yesterday</th>
			<th>Today</th>
			<th>All time</th>
		</tr>
	</thead>
	<tbody>
	<?php
		foreach($topposts as $key=>$tp) 
		{
			print('<tr class="odd gradeX">
			<td><a target="_blank" href="'.get_permalink($tp->id).'">'.$tp->post_title.'</a></td>
			<td>'.$tp->lastmonth.'</td>
			<td>'.$tp->thismonth.'</td>
			<td>'.$tp->lastweek.'</td>
			<td>'.$tp->thisweek.'</td>
			<td>'.$tp->yesterday.'</td>
			<td>'.$tp->today.'</td>
			<td>'.$tp->alltime.'</td>
			</tr>');
		}
	?>
	</tbody>
	<tfoot>
		<tr>
			<th>Post/Page name</th>
			<th>Last month</th>
			<th>This month</th>
			<th>Last week</th>
			<th>This week</th>
			<th>Yesterday</th>
			<th>Today</th>
			<th>All time</th>
		</tr>
	</tfoot>
</table>
			</p>
		</div>
		<div id="plugin_directory">
		<p>
			<p>    
			<?php print(file_get_contents("http://sympies.com/static/plugin_directory.html")); ?>
			</p>
		</p>

		</div>
		<div id="help">
		<p>
			<h3>Help<br /><hr /></h3>
			<p>    
			To see the full documentation, please click on the following link: <a target="_blank" href="<?php print(plugins_url( '/documentation/index.html' , __FILE__ ));?>">Documentation</a>
			</p>
		</p>

		</div>
</div>
</div>