<?php
if(!class_exists('pantherius_wpusertracker_settings'))
{
	class pantherius_wpusertracker_settings extends pantherius_wpusertracker
	{
	var $table_names = array();
	var $nonce = false;
	var $messages = array();
	var $cat_exist = 0;
	var $notice = '';
	var $message = '';
	/**
	* Construct the plugin object
	**/
	public function __construct()
	{
			global $wpdb;
			$this->wpdb =& $wpdb;
			//define custom data tables
			$this->table_names = array(
									"categories"=>$this->wpdb->prefix.'wpusertracker_categories',
									"users"=>$this->wpdb->prefix.'wpusertracker_users',
									"posts"=>$this->wpdb->prefix.'wpusertracker_posts',
									"adverts"=>$this->wpdb->prefix.'wpusertracker_adverts'
									);
		/**
		* include required files
		**/
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		require_once(ABSPATH.'wp-includes/pluggable.php');
		/**
		* register actions, hook into WP's admin_init action hook
		**/
		add_action('admin_init', array(&$this, 'admin_init'));
		add_action('admin_menu', array(&$this, 'add_menu'));
		}
		/**
		* include custom scripts and style to the admin page
		**/
		function enqueue_admin_custom_scripts_and_styles() {
			wp_enqueue_style('wpusertracker_font_style', 'http://fonts.googleapis.com/css?family=Tangerine' );
			wp_enqueue_style('wpusertracker_style', plugins_url( '/templates/assets/css/wpusertracker_settings.css' , __FILE__ ));
			wp_enqueue_style('wpusertracker_table_style', plugins_url( '/templates/assets/css/wpusertracker_table.css' , __FILE__ ));
			wp_enqueue_style('wpusertracker_ui_style', 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');
			wp_enqueue_script('jquery191','http://code.jquery.com/jquery-1.9.1.js',array(),'1.9.1');
			wp_enqueue_script('jquery-ui1103','http://code.jquery.com/ui/1.10.3/jquery-ui.js',array('jquery'),'1.10.3');
			wp_enqueue_script('jquery-datatables194',plugins_url( '/templates/assets/js/jquery.dataTables.min.js' , __FILE__ ),array('jquery'),'1.9.4');
			wp_enqueue_script('wpusertracker_admin', plugins_url( '/templates/assets/js/wpusertracker_admin.js' , __FILE__ ) , array('jquery-ui-core'),'100018', true);
		}
		/**
		* initialize datas on wp admin
		**/
		public function admin_init()
		{
			$settings_page = '';
			if (isset($_REQUEST['page'])) $settings_page = $_REQUEST['page'];
			if ($settings_page=='pantherius_wpusertracker') add_action('admin_head', array(&$this, 'enqueue_admin_custom_scripts_and_styles'));
			$wpusertracker_track_datas = get_option('wpusertracker_track_datas');
			$wpusertracker_track_datas_save = false;
			if (isset($_POST['setting_set_alltime_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_alltime'] = esc_attr($_POST['setting_set_alltime_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if (isset($_POST['setting_set_thismonth_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_thismonth'] = esc_attr($_POST['setting_set_thismonth_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if (isset($_POST['setting_set_thisweek_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_thisweek'] = esc_attr($_POST['setting_set_thisweek_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if (isset($_POST['setting_set_today_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_today'] = esc_attr($_POST['setting_set_today_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if (isset($_POST['setting_set_lastmonth_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_lastmonth'] = esc_attr($_POST['setting_set_lastmonth_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if (isset($_POST['setting_set_lastweek_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_lastweek'] = esc_attr($_POST['setting_set_lastweek_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if (isset($_POST['setting_set_yesterday_visitors'])){
			$wpusertracker_track_datas['wpusertracker_track_yesterday'] = esc_attr($_POST['setting_set_yesterday_visitors']);
			$wpusertracker_track_datas_save = true;
			}
			if ($wpusertracker_track_datas_save == true) update_option('wpusertracker_track_datas', $wpusertracker_track_datas);

			$this->wpusertracker_track_datas = get_option('wpusertracker_track_datas');
			// register your custom settings
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_refresh_time');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_track_users');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_enable_on_homeposts');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_enable_on_postviews');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_position_of_current_readers');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_enable_number_of_views');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_tooltip_text');
			register_setting('pantherius_wpusertracker_mainsettings-group', 'setting_style_of_display_readers');
			
			// add your settings section
			add_settings_section('pantherius_wpusertracker_mainsettings-section', 'Main settings<br /><hr />', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_mainsettings');

			// add your setting's fields
			add_settings_field('pantherius_wpusertracker-setting_refresh_time', 'Ajax refresh time (sec)', array(&$this, 'settings_field_input_select'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_refresh_time', 'field_value' => '', 'min' => 10, 'max' => 120, 'default' => 20, 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_track_users', 'Track users\' day(s) to decide unique user or not (days)', array(&$this, 'settings_field_input_select'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_track_users', 'field_value' => '', 'min' => 1, 'max' => 120, 'default' => 30, 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_enable_on_homeposts', 'Display current readers on homepage posts', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_enable_on_homeposts', 'field_value' => '', 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_enable_on_postviews', 'Display current readers in post and page view', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_enable_on_postviews', 'field_value' => '', 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_position_of_current_readers', 'Position of current readers block', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_position_of_current_readers', 'field_value' => '', 'options' => array("Top right"=>"0","Top left"=>"1","Bottom right"=>"2","Bottom left"=>"3"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_enable_number_of_views', 'Enable number of views on posts and pages', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_enable_number_of_views', 'field_value' => '', 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_tooltip_text', 'Tooltip\'s text', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_tooltip_text', 'field_value' => '', 'other' => 'MAXLENGTH="70" size="70"'));
			add_settings_field('pantherius_wpusertracker-setting_style_of_display_readers', 'Style of current readers block', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_mainsettings', 'pantherius_wpusertracker_mainsettings-section', array('field' => 'setting_style_of_display_readers', 'field_value' => '', 'options' => array(
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"0",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online2.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"2",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online3.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"3",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online4.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"4",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online5.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"5",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online6.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"6",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online7.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"7",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online8.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"8",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online9.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"9",
			"<div class='wpusertracker_readers_block wpusertrackertooltip' title='Online readers of this post currently'><span class='usercounts'>7</span><span class='userimg'><img src='".plugins_url( 'templates/assets/img/online10.png' , __FILE__ )."'></span></div><div style='clear: both;'></div>"=>"10"),
			'other' => ''));

			// register your custom settings
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_alltime_visitors');
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_thismonth_visitors');
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_thisweek_visitors');
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_today_visitors');
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_lastmonth_visitors');
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_lastweek_visitors');
			register_setting('pantherius_wpusertracker_counter-group', 'setting_set_yesterday_visitors');

			// add your settings section
			add_settings_section('pantherius_wpusertracker_counter-section', 'Counter options<br /><hr />', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_counter');

			// add your setting's fields
			add_settings_field('pantherius_wpusertracker-setting_set_alltime_visitors', 'Set all time visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_alltime_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_alltime'], 'other' => 'MAXLENGTH="9" size="9"'));
			add_settings_field('pantherius_wpusertracker-setting_set_thismonth_visitors', 'Set this month visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_thismonth_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_thismonth'], 'other' => 'MAXLENGTH="9" size="9"'));
			add_settings_field('pantherius_wpusertracker-setting_set_thisweek_visitors', 'Set this week visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_thisweek_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_thisweek'], 'other' => 'MAXLENGTH="9" size="9"'));
			add_settings_field('pantherius_wpusertracker-setting_set_today_visitors', 'Set today visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_today_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_today'], 'other' => 'MAXLENGTH="9" size="9"'));
			add_settings_field('pantherius_wpusertracker-setting_set_lastmonth_visitors', 'Set last month visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_lastmonth_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_lastmonth'], 'other' => 'MAXLENGTH="9" size="9"'));
			add_settings_field('pantherius_wpusertracker-setting_set_lastweek_visitors', 'Set last week visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_lastweek_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_lastweek'], 'other' => 'MAXLENGTH="9" size="9"'));
			add_settings_field('pantherius_wpusertracker-setting_set_yesterday_visitors', 'Set yesterday visitors', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_counter', 'pantherius_wpusertracker_counter-section', array('field' => 'setting_set_yesterday_visitors', 'field_value' => $this->wpusertracker_track_datas['wpusertracker_track_yesterday'], 'other' => 'MAXLENGTH="9" size="9"'));

			// register your custom settings
			register_setting('pantherius_wpusertracker_notification-group', 'setting_enable_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_text_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_position_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_direction_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_fadeout_time_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_redisplay_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_style_notification');
			register_setting('pantherius_wpusertracker_notification-group', 'setting_style_notification_custom');

			// add your settings section
			add_settings_section('pantherius_wpusertracker_notification-section', 'Notifications settings<br /><hr />', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_notification');

			// add your setting's fields
			add_settings_field('pantherius_wpusertracker-setting_enable_notification', 'Enable notification', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_enable_notification', 'field_value' => '', 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_text_notification', 'Notifications\' text (only for the default style)', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_text_notification', 'field_value' => '', 'other' => 'MAXLENGTH="70" size="70"'));
			add_settings_field('pantherius_wpusertracker-setting_position_notification', 'Position', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_position_notification', 'field_value' => '', 'options' => array("Top"=>"top","Bottom"=>"bottom"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_direction_notification', 'Direction', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_direction_notification', 'field_value' => '', 'options' => array("Left"=>"left","Right"=>"right"), 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_fadeout_time_notification', 'Wait before fadeout (sec)', array(&$this, 'settings_field_input_select'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_fadeout_time_notification', 'field_value' => '', 'min' => 1, 'max' => 60, 'default' => 5, 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_redisplay_notification', 'Don\'t show notifications again to the same user (min)', array(&$this, 'settings_field_input_select'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_redisplay_notification', 'field_value' => '', 'min' => 1, 'max' => 60, 'default' => 5, 'other' => ''));
			add_settings_field('pantherius_wpusertracker-setting_style_notification', 'Customize notification style', array(&$this, 'settings_field_input_special'), 'pantherius_wpusertracker_notification', 'pantherius_wpusertracker_notification-section', array('field' => 'setting_style_notification', 'field_value' => '', 'options' => array("Default"=>"0","Custom"=>"1"), 'other' => ''));
			// Possibly do additional admin_init tasks
		if (isset($_POST['nonce'])) $this->action_result = $this->category_handler($_POST['nonce']);
		}
		/**
		* validate category's post values on update and insert
		**/
		 function validate_category_handler($item)
		{
			if (empty($item['adv'])) $this->messages[] = __('Advert for category field is required', 'pantherius_wpusertracker');
			if (empty($this->messages)) return 'true';
			return implode('<br />', $this->messages);
		}
		/**
		* handle incoming requests on category's operations
		**/
		private function category_handler($nonce) {
		// define default item to compare with incoming request
			$default = array(
				'id' => null,
				'catid' => null,
				'adv' => '',
				'delete_category' => null
			);
			if (wp_verify_nonce($nonce, basename(__FILE__))) 
			{
				$item = shortcode_atts($default, $_REQUEST);
				$item2db = $item;
				unset($item2db['id']);unset($item2db['delete_category']);
				// validate data, and if all ok save item to database
				// if id is zero insert otherwise update
				if ($item['delete_category']!=1) $item_valid = $this->validate_category_handler($item);
				else $item_valid = 'true';
				if ($item_valid!='true') $this->notice = __($item_valid, 'pantherius_wpusertracker');
				if ($item_valid == 'true') 
				{
					if ($item['id'] == null AND $item['delete_category']!=1) 
					{
						$result = $this->wpdb->insert($this->table_names['adverts'], $item2db);
						$item['tid'] = $this->wpdb->insert_id;
						if ($result) {
							$this->message = __('Advert was successfully saved', 'pantherius_wpusertracker');
						} 
						else 
						{
							$this->notice = __('There was an error while saving category\'s advert', 'pantherius_wpusertracker');
						}
					} 
					else 
					{
						if ($item['delete_category']==1) 
						{
							$result = $this->wpdb->query($this->wpdb->prepare("DELETE FROM ".$this->table_names['adverts']." WHERE id = %d",$item['id']));
							if ($result) {
								$this->message = __('Advert was successfully deleted', 'pantherius_wpusertracker');
							} 
							else 
							{
								$this->notice = __('There was an error while deleting advert', 'pantherius_wpusertracker');
							}
						}
						else 
						{
							$result = $this->wpdb->update($this->table_names['adverts'], $item2db, array('id' => $item['id']));
							if ($result) 
							{
								$this->message = __('Category was successfully updated', 'pantherius_wpusertracker');
							} 
							else 
							{
								//$this->notice = __('There was an error while updating category', 'pantherius_wpusertracker');
							}
						}
					}
				}
			if ($this->message) return $this->message;
			elseif ($this->notice) return $this->notice;
			}
		} 
		/**
		* This function provides special inputs for settings fields
		**/
		public function settings_field_input_special($args)
			{		
			$other = $args['other'];
			$options = $args['options'];
			$key = '';	
			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
			foreach($options as $key=>$opt) {
			if ($value==$opt OR (!$value AND $opt=="3")) $selected = 'checked="true"';
			else $selected = "";
				if ($key=="Custom") {
				echo sprintf('<input type="radio" name="%s" id="%s%s" '.$selected.' value="%s" /><label for="%s%s"> '.$key.'</label><br />', $field, $field, $opt, $opt, $field, $opt);
				$custom = get_option('setting_style_notification_custom');
				if (!$custom) $custom = '[div onclick="document.location=\'{permalink}\'" id="wpusertracker_notification" class="wpusertracker_notification_{position}" style="color:#FC0303;padding: 10px;"]someone had just start to read: [br /][center][a href="{permalink}"]{post_title}[/a][/center][/div]';
				echo sprintf('<textarea rows="7" cols="60" name="%s_custom" id="%s_custom" />%s</textarea>', $field, $field, $custom);
				}
				else {
				echo sprintf('<input type="radio" name="%s" id="%s%s" '.$selected.' value="%s" /><label for="%s%s"> '.$key.'</label><br />', $field, $field, $opt, $opt, $field, $opt);
				}
			}
		}
		/**
		* This function provides radio inputs for settings fields
		**/
        public function settings_field_input_radio($args)
        {
			$key = '';
             $other = $args['other'];
            $options = $args['options'];
 			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
            // echo a proper input type="radio"
			foreach($options as $key=>$opt) 
			{
				if ($value==$opt OR (!$value AND $opt=="off")) $selected = 'checked="true"';
				else $selected = "";
				echo sprintf('<input type="radio" name="%s" id="%s%s" '.$selected.' value="%s" /> <label for="%s%s"> '.$key.'</label> ', $field, $field, $opt, $opt, $field, $opt);
			}
		}
		/**
		* This function provides text inputs for settings fields
		**/
		public function settings_field_input_text($args)
		{
			$other = $args['other'];
			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
			// echo a proper input type="text"
			if (!empty($other)) echo sprintf('<input type="text" name="%s" id="%s" value="%s" %s />', $field, $field, $value, $other);
			else echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
		}
		/**
		* This function provides textarea inputs for settings fields
		**/
		public function settings_field_input_textarea($args)
		{
			$other = $args['other'];
			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
			// echo a proper input type="textarea"
			if (!empty($other)) echo sprintf('<textarea name="%s" id="%s" %s />%s</textarea>', $field, $field, $other, $value);
			else echo sprintf('<textarea name="%s" id="%s" />%s</textarea>', $field, $field, $value);
		}
		/**
		* This function provides select inputs for settings fields
		**/
		public function settings_field_input_select($args)
		{
			$field_min = $args['min'];
			$field_max = $args['max'];
			$field_default = $args['default'];
			// Get the field name from the $args array or get the value of this setting
			$field = $args['field'];
			if ($args['field_value']) $value = $args['field_value'];
			else $value = get_option($field);
				if (!$field_min) $field_min = 1;
				if (!$field_max) $field_max = 10;
				if (!$field_default) $field_default = 5;
			// echo a proper select element
				echo sprintf('<select name="%s" id="%s">', $field, $field);
				for($i=$field_min;$i<=$field_max;$i++) {
					$selected = '';
					if ($value==$i) $selected = 'selected = "true"';
					if (!$value AND $i==$field_default) $selected = 'selected = "true"';
					echo('<option value="'.$i.'" '.$selected.'>'.$i.'</option>');
				}
				echo('</select>');
		}
		/**
		* add a menu
		**/		
		public function add_menu()
		{
			// Add a page to manage this plugin's settings
			add_options_page('WP User Tracker Pro', 'WP User Tracker Pro', 'manage_options', 'pantherius_wpusertracker', array(&$this, 'plugin_settings_page'));
		}
		/**
		* Menu Callback
		**/		
		public function plugin_settings_page()
		{
			if(!current_user_can('manage_options'))
			{
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			// Render the settings template
			include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
		}
		public function settings_section_pantherius_wpusertracker()
		{
		
		}
	}
}
?>