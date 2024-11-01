<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: WP User Tracker
 * Plugin URI: http://sympies.com
 * Description: Track online users and the viewed parts of your website
 * Author: Pantherius
 * Version: 1.0
 * Author URI: http://codecanyon.net/user/pantherius?ref=pantherius
 */

if(!class_exists('pantherius_wpusertracker'))
{
	class pantherius_wpusertracker
	{
		protected static $instance = null;
		/**
		 * Construct the plugin object
		 */
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
			//include required files
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			if (!isset($_COOKIE['wpusertracker_test'])) setcookie("wpusertracker_test", 'test', time()+2592000, COOKIEPATH, COOKIE_DOMAIN, false);
			// installation and uninstallation hooks
			register_activation_hook(__FILE__, array('pantherius_wpusertracker', 'activate'));
			register_deactivation_hook(__FILE__, array('pantherius_wpusertracker', 'deactivate'));
			register_uninstall_hook(__FILE__, array('pantherius_wpusertracker', 'uninstall'));
			add_action('plugins_loaded',array(&$this, 'pantherius_wpusertracker_widget_counter'));
			wp_register_sidebar_widget('pantherius_wpusertracker_counter', 'WP UTPro - Counter Widget', array(&$this, 'pantherius_wpusertracker_widget_counter_show'), array('description' => 'Display the latest jobs feed'));
			wp_register_widget_control('pantherius_wpusertracker_counter', 'WP UTPro - Counter Widget', array(&$this, 'pantherius_wpusertracker_widget_counter_control'), array('description' => 'Display visitor counter'));
			add_action('plugins_loaded',array(&$this, 'pantherius_wpusertracker_widget_topcategories'));
			wp_register_sidebar_widget('pantherius_wpusertracker_topcategories', 'WP UTPro - Top Categories Widget', array(&$this, 'pantherius_wpusertracker_widget_topcategories_show'), array('description' => 'Display the most viewed categories'));
			wp_register_widget_control('pantherius_wpusertracker_topcategories', 'WP UTPro - Top Categories Widget', array(&$this, 'pantherius_wpusertracker_widget_topcategories_control'), array('description' => 'Display the most viewed categories'));
			add_action('plugins_loaded',array(&$this, 'pantherius_wpusertracker_widget_topposts'));
			wp_register_sidebar_widget('pantherius_wpusertracker_topposts', 'WP UTPro - Top Posts Widget', array(&$this, 'pantherius_wpusertracker_widget_topposts_show'), array('description' => 'Display the most viewed posts'));
			wp_register_widget_control('pantherius_wpusertracker_topposts', 'WP UTPro - Top Posts Widget', array(&$this, 'pantherius_wpusertracker_widget_topposts_control'), array('description' => 'Display the most viewed posts'));
			add_action('plugins_loaded',array(&$this, 'pantherius_wpusertracker_widget_targetedad'));
			wp_register_sidebar_widget('pantherius_wpusertracker_targetedad', 'WP UTPro - Targeted AD', array(&$this, 'pantherius_wpusertracker_widget_targetedad_show'), array('description' => 'Display targeted advertisement by the most viewed categories'));
			wp_register_widget_control('pantherius_wpusertracker_targetedad', 'WP UTPro - Targeted AD', array(&$this, 'pantherius_wpusertracker_widget_targetedad_control'), array('description' => 'Display targeted advertisement by the most viewed categories'));
			//get the saved widget and plugin datas
			$this->wpusertracker_widget_datas = get_option('wpusertracker_widget_datas');
			$this->wpusertracker_track_datas = get_option('wpusertracker_track_datas');
			$timezone = get_option('timezone_string');
			if (!empty($timezone))
			{
				date_default_timezone_set(get_option('timezone_string'));
				$this->wpdb->query("SET `time_zone` = '".date('P')."'");
			}
			add_action('wp_ajax_ajax_wpusertracker_update', array(&$this, 'ajax_wpusertracker_update'));
			add_action('wp_ajax_nopriv_ajax_wpusertracker_update', array(&$this, 'ajax_wpusertracker_update'));
			if (is_admin())
			{
				require_once(sprintf("%s/settings.php", dirname(__FILE__)));
				$pantherius_wpusertracker_settings = new pantherius_wpusertracker_settings();
				$plugin = plugin_basename(__FILE__);
				add_filter("plugin_action_links_$plugin", array(&$this, 'plugin_settings_link'));
			}
			else
			{
				if (!isset($_REQUEST['ajax_wpusertracker_update'])) 
				{
					//integrate the content modifier function 
					add_filter('the_content',array(&$this, 'extend_content'));
					add_action('init', array(&$this, 'enqueue_custom_scripts_and_styles'));
				}
			}
		}

		public static function getInstance()
		{
			if (!isset($instance)) 
			{
				$instance = new pantherius_wpusertracker;
			}
		return $instance;
		}
		/**
		* Activate the plugin
		**/
		public static function activate()
		{
			global $wpdb;
			$table_names = array(
									"categories"=>$wpdb->prefix.'wpusertracker_categories',
									"users"=>$wpdb->prefix.'wpusertracker_users',
									"posts"=>$wpdb->prefix.'wpusertracker_posts',
									"adverts"=>$wpdb->prefix.'wpusertracker_adverts'
									);

		//creating custom tables
			$sql = "CREATE TABLE IF NOT EXISTS ".$table_names['users']." (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  userid mediumint(9) NULL,
			  last_visited_time datetime NULL,
			  last_visited_post mediumint(9) NULL,
			  visited_categories text NULL,
			  UNIQUE KEY id (id)
			);";
			dbDelta( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS ".$table_names['categories']." (
			  catid mediumint(9) NOT NULL,
			  alltime mediumint(9) NULL,
			  lastmonth mediumint(9) NULL,
			  thismonth mediumint(9) NULL,
			  lastweek mediumint(9) NULL,
			  thisweek mediumint(9) NULL,
			  yesterday mediumint(9) NULL,
			  today mediumint(9) NULL,
			  UNIQUE KEY catid (catid)
			);";
			dbDelta( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS ".$table_names['posts']." (
			  postid mediumint(9) NOT NULL,
			  alltime mediumint(9) NULL,
			  lastmonth mediumint(9) NULL,
			  thismonth mediumint(9) NULL,
			  lastweek mediumint(9) NULL,
			  thisweek mediumint(9) NULL,
			  yesterday mediumint(9) NULL,
			  today mediumint(9) NULL,
			  UNIQUE KEY postid (postid)
			);";
			dbDelta( $sql );
			$sql = "CREATE TABLE IF NOT EXISTS ".$table_names['adverts']." (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  catid mediumint(9) NOT NULL,
			  adv text NOT NULL,
			  UNIQUE KEY id (id)
			);";
			dbDelta( $sql );
			//check the widget's datas and insert if not exists
			$wpusertracker_track_datas = array(	'wpusertracker_track_alltime' => '0',
							'wpusertracker_track_today' => '0',
							'wpusertracker_track_yesterday' => '0',
							'wpusertracker_track_thisweek' => '0',
							'wpusertracker_track_lastweek' => '0',
							'wpusertracker_track_thismonth' => '0',
							'wpusertracker_track_lastmonth' => '0',
							'wpusertracker_track_lastupdate' => date("Y-m-d H:i:s")
						);
			$wpusertracker_widget_datas = array(
									'wpusertracker_widget_counter_title' => 'Counter',
									'wpusertracker_widget_counter_show_alltime_visitors' => 'on',
									'wpusertracker_widget_counter_show_thismonth_visitors' => 'on',
									'wpusertracker_widget_counter_show_thisweek_visitors' => 'on',
									'wpusertracker_widget_counter_show_today_visitors' => 'on',
									'wpusertracker_widget_counter_show_changes' => 'on',
									'wpusertracker_widget_topposts_title' => 'Top Posts',
									'wpusertracker_widget_topposts_count' => '5',
									'wpusertracker_widget_topcategories_title' => 'Top Categories',
									'wpusertracker_widget_topcategories_count' => '5',
									'wpusertracker_widget_targetedad_title' => 'The best choice for you'
								);
			$default_cat_exists = $wpdb->get_var("SELECT COUNT(id) FROM ".$table_names['adverts']." WHERE `id` = '1'");
			if ($default_cat_exists==0) $wpdb->insert( $table_names['adverts'], array( 'id' => '1', 'catid' => '0', 'adv' => '<div>General Example Ad<br /><br /><a target="_blank" href="http://codecanyon.net/user/pantherius?ref=pantherius"><img src="http://3.s3.envato.com/files/60381131/job_board_thumbnail.png"><br /><br />WP Job Hunter</a></div>' ) );
			if ( ! get_option('wpusertracker_track_datas'))
			{
				add_option('wpusertracker_track_datas' , $wpusertracker_track_datas);
			}
			else
			{
				update_option('wpusertracker_track_datas' , $wpusertracker_track_datas);
			}
			if ( ! get_option('wpusertracker_widget_datas'))
			{
				add_option('wpusertracker_widget_datas' , $wpusertracker_widget_datas);
			}
			else
			{
				update_option('wpusertracker_widget_datas' , $wpusertracker_widget_datas);
			}
			register_setting('pantherius_wpusertracker-group', 'wpusertracker_track_datas');
			register_setting('pantherius_wpusertracker-group', 'wpusertracker_widget_datas');
		}
		/**
		* Deactivate the plugin
		**/
		public static function deactivate()
		{
			wp_unregister_sidebar_widget('pantherius_wpusertracker_counter');
			wp_unregister_sidebar_widget('pantherius_wpusertracker_topcategories');
			wp_unregister_sidebar_widget('pantherius_wpusertracker_topposts');
			wp_unregister_sidebar_widget('pantherius_wpusertracker_targetedad');
			wp_unregister_sidebar_widget('pantherius_wpjobhunter');

			unregister_setting('pantherius_wpusertracker-group', 'wpusertracker_track_datas');
			unregister_setting('pantherius_wpusertracker-group', 'wpusertracker_widget_datas');
			
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_refresh_time');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_track_users');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_enable_on_homeposts');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_enable_on_postviews');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_position_of_current_readers');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_enable_number_of_views');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_tooltip_text');
			unregister_setting('pantherius_wpusertracker_mainsettings-group', 'setting_style_of_display_readers');

			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_alltime_visitors');
			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_thismonth_visitors');
			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_thisweek_visitors');
			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_today_visitors');
			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_lastmonth_visitors');
			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_lastweek_visitors');
			unregister_setting('pantherius_wpusertracker_counter-group', 'setting_set_yesterday_visitors');

			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_enable_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_text_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_position_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_direction_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_fadeout_time_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_redisplay_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_style_notification');
			unregister_setting('pantherius_wpusertracker_notification-group', 'setting_style_notification_custom');
			}
		
		/**
		* Uninstall the plugin
		**/
		public static function uninstall()
		{
			global $wpdb;
			$table_names = array(
									"categories"=>$wpdb->prefix.'wpusertracker_categories',
									"users"=>$wpdb->prefix.'wpusertracker_users',
									"posts"=>$wpdb->prefix.'wpusertracker_posts',
									"adverts"=>$wpdb->prefix.'wpusertracker_adverts'
									);
			delete_option('wpusertracker_track_datas');
			delete_option('wpusertracker_widget_datas');
			delete_option('setting_refresh_time');
			delete_option('setting_track_users');
			delete_option('setting_enable_on_homeposts');
			delete_option('setting_enable_on_postviews');
			delete_option('setting_position_of_current_readers');
			delete_option('setting_enable_number_of_views');
			delete_option('setting_tooltip_text');
			delete_option('setting_style_of_display_readers');
			delete_option('setting_enable_notification');
			delete_option('setting_text_notification');
			delete_option('setting_position_notification');
			delete_option('setting_direction_notification');
			delete_option('setting_fadeout_time_notification');
			delete_option('setting_redisplay_notification');
			delete_option('setting_style_notification');
			delete_option('setting_style_notification_custom');
			delete_option('setting_set_alltime_visitors');
			delete_option('setting_set_thismonth_visitors');
			delete_option('setting_set_thisweek_visitors');
			delete_option('setting_set_today_visitors');
			delete_option('setting_set_lastmonth_visitors');
			delete_option('setting_set_lastweek_visitors');
			delete_option('setting_set_yesterday_visitors');
			//drop custom data tables
			$wpdb->query("DROP TABLE IF EXISTS {$table_names['users']}");
			$wpdb->query("DROP TABLE IF EXISTS {$table_names['posts']}");
			$wpdb->query("DROP TABLE IF EXISTS {$table_names['categories']}");
			$wpdb->query("DROP TABLE IF EXISTS {$table_names['adverts']}");
		}
		
		function enqueue_custom_scripts_and_styles() 
		{
			wp_enqueue_style('wpjobhunter_style', plugins_url( '/templates/assets/css/wpusertracker.css' , __FILE__ ));
			wp_enqueue_style('wpjobhunter_ui_style', 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');
			wp_enqueue_script('jquery191','http://code.jquery.com/jquery-1.8.3.js',array(),'1.8.3');
			wp_enqueue_script('jquery-ui1103','http://code.jquery.com/ui/1.10.3/jquery-ui.js',array('jquery'),'1.10.3');
			wp_register_script( "wpusertracker", plugins_url('/templates/assets/js/wpusertracker.js' , __FILE__ ), array('jquery') );
			add_action('save_cookies', array(&$this, 'writecookies'));
		}
		
		function update_categories($cats,$single=false)
		{
		$catlist = '';
			if ($single) 
			{
			unset($cats[0]);
				foreach($cats as $key=>$cs) 
				{
					$cat_exists = $this->wpdb->get_var("SELECT COUNT(catid) FROM ".$this->table_names['categories']." WHERE `catid` = '".$key."'");
					if (!$cat_exists) $this->wpdb->insert($this->table_names['categories'], array( "catid" =>$key ,"alltime" => 0,"lastmonth" => 0, "thismonth" => 0, "lastweek" => 0, "thisweek" => 0, "yesterday" =>0, "today" => 0));
				$catlist .= $key;
				if ($key<count($cats)) $catlist .= ",";
				}
				$sql = "UPDATE ".$this->table_names['categories']." SET `today` = `today`+1,`thisweek` = `thisweek`+1,`thismonth` = `thismonth`+1,`alltime` = `alltime`+1 WHERE `catid` IN('".$catlist."')";
				dbDelta( $sql );
			}
		}
		
		function update_posts($postid,$single=false)
		{
			if ($single) 
			{
				$post_exists = $this->wpdb->get_row("SELECT today,thisweek,thismonth,alltime FROM ".$this->table_names['posts']." WHERE `postid` = ".$postid, ARRAY_A);
				if ($post_exists>0) $this->wpdb->update($this->table_names['posts'], array( "today" => $post_exists['today']+1, "thisweek" => $post_exists['thisweek']+1, "thismonth" => $post_exists['thismonth']+1, "alltime" => $post_exists['alltime']+1),array('postid' => $postid));
				else $this->wpdb->insert($this->table_names['posts'], array( "postid" => $postid, "today" =>1, "thisweek" => 1, "thismonth" => 1, "alltime" => 1));
			}
		}
		
		function ajax_wpusertracker_update() 
		{
			global $current_user;
			$check_trackid = false;
			if (!isset($_COOKIE['wpusertracker_test'])) setcookie("wpusertracker_test", 'test', time()+2592000, COOKIEPATH, COOKIE_DOMAIN, false);
			$update_res = false;$noti_options = false;$store = false;
			if (isset($_REQUEST['single'])) $single = $_REQUEST['single'];
			if (isset($_REQUEST['post_datas'])) $npost = unserialize(stripslashes($_REQUEST['post_datas']));
			if (isset($_COOKIE['wpusertracker_test']))
			{
				$cats = array("0"=>0);
				$cat_id = get_the_category($npost['id']);
				if ($cat_id) {
					foreach($cat_id as $ci)
					{
						$cid = $ci->cat_ID;
						$cats[$cid] = 1;
					}
				}

					if ( is_user_logged_in() ) $loggedin = $current_user->ID;
					else $loggedin = false;
					if (isset($_COOKIE['wpusertracker'])) 
					{
						$user_track_params = base64_decode($_COOKIE['wpusertracker']);
						if ($user_track_params) $user_trackid = $user_track_params;
					}
						if ($loggedin AND !isset($user_trackid)) $user_trackid = $this->wpdb->get_var("SELECT id FROM ".$this->table_names['users']." WHERE `userid` = '".$loggedin."'");
						if (isset($user_trackid) AND !$loggedin) $loggedin = $this->wpdb->get_var("SELECT userid FROM ".$this->table_names['users']." WHERE `id` = '".$user_trackid."'");
						if (isset($user_trackid))
						{
							$user_params = $this->wpdb->get_row("SELECT * FROM ".$this->table_names['users']." WHERE id = ".$user_trackid, ARRAY_A);
							$lastcats = unserialize($user_params['visited_categories']);
							if ($user_params['visited_categories']=='b:0;') $lastcats = array("0"=>0);
							if ($user_params['last_visited_post']!=$npost['id']) 
							{
								if (!empty($lastcats))
								{
									foreach($cats as $key=>$lc)
									{
										if (!$lastcats[$key]) $lastcats[$key]=$lc;
										else $lastcats[$key]++;
									}
								}
							$this->update_categories($cats,$single);
							if ($npost['id']) $this->update_posts($npost['id'],$single);
							}
							$cats = $lastcats;
							$update_res = $this->wpdb->update($this->table_names['users'], array( "userid" => $loggedin, "last_visited_time"=>date('Y-m-d H:i:s'),"last_visited_post"=>$npost['id'],"visited_categories"=>serialize($cats)),array('id' => $user_trackid));
							if ($update_res>0) {}
							else
							{
								$check_trackid = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE `id` = '".$user_trackid."'");
								if ($check_trackid==0) $this->wpdb->insert($this->table_names['users'], array( "id" =>$user_trackid ,"userid" => $loggedin, "last_visited_time" => date('Y-m-d H:i:s'), "last_visited_post" => $npost['id'], "visited_categories" => serialize($cats)));
								$this->update_categories($cats,$single);
								if ($npost['id']) $this->update_posts($npost['id'],$single);
							}
						}
						else 
						{
							$this->wpdb->insert($this->table_names['users'], array( "userid" => $loggedin, "last_visited_time" => date('Y-m-d H:i:s'), "last_visited_post" => $npost['id'], "visited_categories" => serialize($cats)));
							$user_trackid = $this->wpdb->insert_id;
							$this->increase_counter('wpusertracker_track_alltime');
							$this->increase_counter('wpusertracker_track_today');
							$this->increase_counter('wpusertracker_track_thisweek');
							$this->increase_counter('wpusertracker_track_thismonth');
							$this->update_categories($cats,$single);
							if ($npost['id']) $this->update_posts($npost['id'],$single);
						}
							// store the tracking id of the user in the browser's cookie
						if (!isset($_COOKIE['wpusertracker'])) 
						{		
							if (!get_option('setting_track_users')) $trackingdays = 30;
							else $trackingdays = get_option('setting_track_users');
							if ($user_trackid) $store = base64_encode(base64_encode($user_trackid).'|'.$trackingdays);
						}
			}
			$postview = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE `last_visited_post` = '".$npost['id']."' AND NOW() <= DATE_ADD(last_visited_time, INTERVAL 5 MINUTE)");
			$allview = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE NOW() <= DATE_ADD(last_visited_time, INTERVAL 5 MINUTE)");
			$slidediv = false;
			if (!isset($_COOKIE['wpusertracker_notification']) AND get_option('setting_enable_notification')=='on') 
			{
			}
			$tracker_datas = $postview.'|'.$allview.'|'.$this->wpusertracker_track_datas['wpusertracker_track_today'].'|'.$this->wpusertracker_track_datas['wpusertracker_track_thisweek'].'|'.$this->wpusertracker_track_datas['wpusertracker_track_thismonth'].'|'.$this->wpusertracker_track_datas['wpusertracker_track_alltime'].'|'.$this->wpusertracker_track_datas['wpusertracker_track_yesterday'].'|'.$this->wpusertracker_track_datas['wpusertracker_track_lastweek'].'|'.$this->wpusertracker_track_datas['wpusertracker_track_lastmonth'].'|'.$slidediv.'|'.$noti_options.'|'.$store;
			die(print($tracker_datas));
		}
		
		function increase_counter($type) 
		{
			$stored_data = $this->wpusertracker_track_datas;
			foreach($stored_data as $key=>$dt)
			{
				if ($dt>=0) {}
				else
				{
					$stored_data[$key] = '0';
				}
			}
			if ((strtotime($stored_data['wpusertracker_track_lastupdate'])<strtotime(date('Y-m-d 00:00:00', strtotime('first day of this month')))) AND (strtotime(date('Y-m-d H:i:s'))>strtotime(date('Y-m-d 00:00:00', strtotime('first day of this month')))))
			{
				$stored_data['wpusertracker_track_lastmonth'] = $stored_data['wpusertracker_track_thismonth'];
				$stored_data['wpusertracker_track_thismonth'] = 0;
				$sql = "UPDATE ".$this->table_names['categories']." SET `lastmonth` = `thismonth`,`thismonth` = 0";
				dbDelta( $sql );
				$sql = "UPDATE ".$this->table_names['posts']." SET `lastmonth` = `thismonth`,`thismonth` = 0";
				dbDelta( $sql );
			}
			if ((strtotime($stored_data['wpusertracker_track_lastupdate'])<strtotime(date('Y-m-d 00:00:00', strtotime('monday this week')))) AND (strtotime(date('Y-m-d H:i:s'))>strtotime(date('Y-m-d 00:00:00', strtotime('monday this week')))))
			{
				$stored_data['wpusertracker_track_lastweek'] = $stored_data['wpusertracker_track_thisweek'];
				$stored_data['wpusertracker_track_thisweek'] = 0;
				$sql = "UPDATE ".$this->table_names['categories']." SET `lastweek` = `thisweek`,`thisweek` = 0";
				dbDelta( $sql );
				$sql = "UPDATE ".$this->table_names['posts']." SET `lastweek` = `thisweek`,`thisweek` = 0";
				dbDelta( $sql );
			}
			if ((strtotime($stored_data['wpusertracker_track_lastupdate'])<strtotime(date('Y-m-d 00:00:00'))) AND (strtotime(date('Y-m-d H:i:s'))>strtotime(date('Y-m-d 00:00:00'))))
			{
				$stored_data['wpusertracker_track_yesterday'] = $stored_data['wpusertracker_track_today'];
				$stored_data['wpusertracker_track_today'] = 0;
				$sql = "UPDATE ".$this->table_names['categories']." SET `yesterday` = `today`,`today` = 0";
				dbDelta( $sql );
				$sql = "UPDATE ".$this->table_names['posts']." SET `yesterday` = `today`,`today` = 0";
				dbDelta( $sql );
				$this->wpdb->query($this->wpdb->delete("DELETE FROM ".$this->table_names['users']." WHERE `visited_categories` = 'a:1:{i:0;i:0;}' OR NOW() >= DATE_ADD(last_visited_time, INTERVAL 30 DAY)"),"");
			}
			if ($type) $stored_data[$type] = $stored_data[$type] + 1;
			$this->wpusertracker_track_datas = array( 'wpusertracker_track_alltime' => $stored_data['wpusertracker_track_alltime'] ,'wpusertracker_track_today' => $stored_data['wpusertracker_track_today'],'wpusertracker_track_yesterday' => $stored_data['wpusertracker_track_yesterday'],'wpusertracker_track_thisweek' => $stored_data['wpusertracker_track_thisweek'],'wpusertracker_track_lastweek' => $stored_data['wpusertracker_track_lastweek'],'wpusertracker_track_thismonth' => $stored_data['wpusertracker_track_thismonth'],'wpusertracker_track_lastmonth' => $stored_data['wpusertracker_track_lastmonth'],'wpusertracker_track_lastupdate' => date("Y-m-d H:i:s"));
			if ( ! get_option('wpusertracker_track_datas'))
			{
				add_option('wpusertracker_track_datas' , $this->wpusertracker_track_datas);
			}
			else
			{
				update_option('wpusertracker_track_datas' , $this->wpusertracker_track_datas);
			}
		}

		/**
		* Adding extra content to the main content - online reader counter and viewed counter
		**/
		function extend_content($content)
		{
		global $post,$slider;
		$post_id = $post->ID;
		if (!is_admin())
		{
			if (!get_option('setting_tooltip_text')) $tooltip_text = 'Online readers of this post currently';
			else $tooltip_text = get_option('setting_tooltip_text');
			if (!get_option('setting_refresh_time')) $refresh_time = 1000*20;
			else $refresh_time = get_option('setting_refresh_time')*1000;
			if (is_singular()) $single = true;
			else $single = false;
			$after_content = false;$before_content = true;$positioner = 'wpusertracker_righter';
			if (get_option('setting_position_of_current_readers')==3) {$after_content = true;$before_content = false;$positioner = 'wpusertracker_lefter';}
			if (get_option('setting_position_of_current_readers')==2) {$after_content = true;$before_content = false;$positioner = 'wpusertracker_righter';}
			if (get_option('setting_position_of_current_readers')==1) {$after_content = false;$before_content = true;$positioner = 'wpusertracker_lefter';}
			if (!get_option('setting_style_of_display_readers') OR get_option('setting_style_of_display_readers')==0) $iconstyle = '';
			else $iconstyle = get_option('setting_style_of_display_readers');
			if (!isset($post)) $post = array();
			else {
				$npost['id'] = $post->ID;
			}
			if (is_home()) 
			{
			if (get_option('setting_enable_on_homeposts')=='on') 
			{
				$currently_view = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE `last_visited_post` = '".$post_id."' AND NOW() <= DATE_ADD(last_visited_time, INTERVAL 5 MINUTE)");
				if ($before_content==true) $content = '<div class="wpusertracker_readers_block wpusertrackertooltip '.$positioner.'" title="'.$tooltip_text.'"><span class="usercounts">'.$currently_view.'</span><span class="userimg"><img src="'.plugins_url( 'templates/assets/img/online'.$iconstyle.'.png' , __FILE__ ).'"></span></div><div style="clear: both;"></div>'.$content;
				else $content = $content.'<div class="wpusertracker_readers_block wpusertrackertooltip '.$positioner.'" title="'.$tooltip_text.'"><span class="usercounts">'.$currently_view.'</span><span class="userimg"><img src="'.plugins_url( 'templates/assets/img/online'.$iconstyle.'.png' , __FILE__ ).'"></span></div><div style="clear: both;"></div>';
			}
			wp_localize_script( 'wpusertracker', 'ajax_wpusertracker_update', array( 'ajaxurl' => admin_url( 'admin-ajax.php'), 'post_datas'=>'', 'single'=>$single, 'slider'=>$slider, 'refresh_time'=>$refresh_time));
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wpusertracker' );
			}
			else 
			{
			if (get_option('setting_enable_on_postviews')=='on')
			{
				$post_id = $post->ID;
				$currently_view = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE `last_visited_post` = '".$post_id."' AND NOW() <= DATE_ADD(last_visited_time, INTERVAL 5 MINUTE)");
				if ($before_content==true) $content = '<div class="wpusertracker_readers_block wpusertrackertooltip '.$positioner.'" title="'.$tooltip_text.'"><span class="usercounts">'.$currently_view.'</span><span class="userimg"><img src="'.plugins_url( 'templates/assets/img/online'.$iconstyle.'.png' , __FILE__ ).'"></span></div><div style="clear: both;"></div>'.$content;
				else $content = $content.'<div class="wpusertracker_readers_block wpusertrackertooltip '.$positioner.'" title="'.$tooltip_text.'"><span class="usercounts">'.$currently_view.'</span><span class="userimg"><img src="'.plugins_url( 'templates/assets/img/online'.$iconstyle.'.png' , __FILE__ ).'"></span></div><div style="clear: both;"></div>';
			}
				$number_of_reds = $this->wpdb->get_var("SELECT alltime FROM ".$this->table_names['posts']." WHERE `postid` = '".$post_id."'");
				if ($number_of_reds>=1) {}
				else $number_of_reds = '1';
				if (get_option('setting_enable_number_of_views')=='on') $content .= '<br />Viewed '.$number_of_reds.' times';
			wp_localize_script( 'wpusertracker', 'ajax_wpusertracker_update', array( 'ajaxurl' => admin_url( 'admin-ajax.php'), 'post_datas'=>serialize($npost), 'single'=>$single, 'slider'=>$slider, 'refresh_time'=>$refresh_time));        
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wpusertracker' );
			}
		}
		return $content;
		}

		public function pantherius_wpusertracker_widget_counter($args) 
		{
		}
		
		public function pantherius_wpusertracker_widget_counter_control() 
		{
				$wpusertracker_widget_datas = get_option('wpusertracker_widget_datas');
		  $wpusertracker_widget_datas_save = false;
		   if (isset($_POST['wpusertracker_widget_counter_title'])){
			$wpusertracker_widget_datas['wpusertracker_widget_counter_title'] = esc_attr($_POST['wpusertracker_widget_counter_title']);
			$wpusertracker_widget_datas_save = true;
		  }
		   if (isset($_POST['wpusertracker_widget_counter_show_alltime_visitors'])){
			$wpusertracker_widget_datas['wpusertracker_widget_counter_show_alltime_visitors'] = esc_attr($_POST['wpusertracker_widget_counter_show_alltime_visitors']);
			$wpusertracker_widget_datas_save = true;
		  }
		   if (isset($_POST['wpusertracker_widget_counter_show_thismonth_visitors'])){
			$wpusertracker_widget_datas['wpusertracker_widget_counter_show_thismonth_visitors'] = esc_attr($_POST['wpusertracker_widget_counter_show_thismonth_visitors']);
			$wpusertracker_widget_datas_save = true;
		  }
		   if (isset($_POST['wpusertracker_widget_counter_show_thisweek_visitors'])){
			$wpusertracker_widget_datas['wpusertracker_widget_counter_show_thisweek_visitors'] = esc_attr($_POST['wpusertracker_widget_counter_show_thisweek_visitors']);
			$wpusertracker_widget_datas_save = true;
		  }
		   if (isset($_POST['wpusertracker_widget_counter_show_today_visitors'])){
			$wpusertracker_widget_datas['wpusertracker_widget_counter_show_today_visitors'] = esc_attr($_POST['wpusertracker_widget_counter_show_today_visitors']);
			$wpusertracker_widget_datas_save = true;
		  }
		   if (isset($_POST['wpusertracker_widget_counter_show_changes'])){
			$wpusertracker_widget_datas['wpusertracker_widget_counter_show_changes'] = esc_attr($_POST['wpusertracker_widget_counter_show_changes']);
			$wpusertracker_widget_datas_save = true;
		  }
			if ($wpusertracker_widget_datas_save == true) update_option('wpusertracker_widget_datas', $wpusertracker_widget_datas);

		add_settings_section('pantherius_wpusertracker_widget_counter_datas-section', '', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_widget_counter_datas');

		add_settings_field('pantherius_wpusertracker_widget_counter_datas-wpusertracker_widget_counter_title', 'Title', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_widget_counter_datas', 'pantherius_wpusertracker_widget_counter_datas-section', array('field' => 'wpusertracker_widget_counter_title', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_counter_title'], 'other' => 'MAXLENGTH = "30" size="18"'));
			
		add_settings_field('pantherius_wpusertracker-wpusertracker_widget_counter_show_alltime_visitors', 'Display all time visitors', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_widget_counter_datas', 'pantherius_wpusertracker_widget_counter_datas-section', array('field' => 'wpusertracker_widget_counter_show_alltime_visitors', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_counter_show_alltime_visitors'], 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			
		add_settings_field('pantherius_wpusertracker-wpusertracker_widget_counter_show_thismonth_visitors', 'Display this month visitors', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_widget_counter_datas', 'pantherius_wpusertracker_widget_counter_datas-section', array('field' => 'wpusertracker_widget_counter_show_thismonth_visitors', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_counter_show_thismonth_visitors'], 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			
		add_settings_field('pantherius_wpusertracker-wpusertracker_widget_counter_show_thisweek_visitors', 'Display this week visitors', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_widget_counter_datas', 'pantherius_wpusertracker_widget_counter_datas-section', array('field' => 'wpusertracker_widget_counter_show_thisweek_visitors', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_counter_show_thisweek_visitors'], 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			
		add_settings_field('pantherius_wpusertracker-wpusertracker_widget_counter_show_today_visitors', 'Display today visitors', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_widget_counter_datas', 'pantherius_wpusertracker_widget_counter_datas-section', array('field' => 'wpusertracker_widget_counter_show_today_visitors', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_counter_show_today_visitors'], 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
			
		//add_settings_field('pantherius_wpusertracker-wpusertracker_widget_counter_show_changes', 'Display changes icons', array(&$this, 'settings_field_input_radio'), 'pantherius_wpusertracker_widget_counter_datas', 'pantherius_wpusertracker_widget_counter_datas-section', array('field' => 'wpusertracker_widget_counter_show_changes', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_counter_show_changes'], 'options' => array("On"=>"on","Off"=>"off"), 'other' => ''));
		
		  ?>

		<?php @settings_fields('wpusertracker_widget_datas-group'); ?>
        <?php @do_settings_fields('wpusertracker_widget_datas-group'); ?>
        <?php do_settings_sections('pantherius_wpusertracker_widget_counter_datas'); ?>

		  <?php
		}
		
		/**
		* Show the counter sidebar widget
		**/
		public function pantherius_wpusertracker_widget_counter_show($args=array()) 
		{
			if (isset($args)) extract($args);
			else 
			{
				print('couldn\'t extract args');$before_widget = '';$before_title = '';$after_title = '';$after_widget = '';
			}
			$timezone = get_option('timezone_string');
			echo $before_widget;
			echo $before_title . $this->wpusertracker_widget_datas['wpusertracker_widget_counter_title'] . $after_title;
			if (empty($timezone)) print('<ul><li>Error: You have to set the timezone.</li></ul>');
			else
			{
				$allview = $this->wpdb->get_var("SELECT COUNT(id) FROM ".$this->table_names['users']." WHERE NOW() <= DATE_ADD(last_visited_time, INTERVAL 5 MINUTE)");
				print('<ul>');
				if ($this->wpusertracker_widget_datas['wpusertracker_widget_counter_show_alltime_visitors']=='on') print('<li class="wpusertrackertooltip" title="All time unique visitors"><span id="wpusertracker_alltime_text" class="wpusertracker_larger_size">All time visitors:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_alltime">'.$this->wpusertracker_track_datas['wpusertracker_track_alltime'].'</span></li>');
				if ($this->wpusertracker_widget_datas['wpusertracker_widget_counter_show_thismonth_visitors']=='on') print('<li class="wpusertrackertooltip" title="Unique visitors in this month"><span id="wpusertracker_thismonth_text" class="wpusertracker_larger_size">This month:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_thismonth">'.$this->wpusertracker_track_datas['wpusertracker_track_thismonth'].'</span></li>');
				if ($this->wpusertracker_widget_datas['wpusertracker_widget_counter_show_thisweek_visitors']=='on') print('<li class="wpusertrackertooltip" title="Unique visitors on this week"><span id="wpusertracker_thisweek_text" class="wpusertracker_larger_size">This week:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_thisweek">'.$this->wpusertracker_track_datas['wpusertracker_track_thisweek'].'</span></li>');
				if ($this->wpusertracker_widget_datas['wpusertracker_widget_counter_show_today_visitors']=='on') print('<li class="wpusertrackertooltip" title="Total number of unique visitors today"><span id="wpusertracker_today_text" class="wpusertracker_larger_size">Today:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_track_today">'.$this->wpusertracker_track_datas['wpusertracker_track_today'].'</span></li>');
				print('<li class="wpusertrackertooltip" title="Unique visitors currently online"><span id="wpusertracker_online_text" class="wpusertracker_larger_size">Now Online:</span> <span class="wpusertracker_smaller_size wpusertracker_righter" id="wpusertracker_now_online">'.$allview.'</span></li></ul>');
			}
			echo $after_widget;
		}
		
		public function pantherius_wpusertracker_widget_topcategories($args) 
		{
		}
		
		public function pantherius_wpusertracker_widget_topcategories_control() 
		{
			$wpusertracker_widget_datas = get_option('wpusertracker_widget_datas');
			$wpusertracker_widget_datas_save = false;
			if (isset($_POST['wpusertracker_widget_topcategories_title']))
			{
				$wpusertracker_widget_datas['wpusertracker_widget_topcategories_title'] = esc_attr($_POST['wpusertracker_widget_topcategories_title']);
				$wpusertracker_widget_datas_save = true;
			}
			if (isset($_POST['wpusertracker_widget_topcategories_count']))
			{
				$wpusertracker_widget_datas['wpusertracker_widget_topcategories_count'] = esc_attr($_POST['wpusertracker_widget_topcategories_count']);
				$wpusertracker_widget_datas_save = true;
			}
			if ($wpusertracker_widget_datas_save == true) update_option('wpusertracker_widget_datas', $wpusertracker_widget_datas);

		add_settings_section('pantherius_wpusertracker_widget_topcategories_datas-section', '', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_widget_topcategories_datas');

		add_settings_field('pantherius_wpusertracker_widget_topcategories_datas-wpusertracker_widget_topcategories_title', 'Title', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_widget_topcategories_datas', 'pantherius_wpusertracker_widget_topcategories_datas-section', array('field' => 'wpusertracker_widget_topcategories_title', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_topcategories_title'], 'other' => 'MAXLENGTH = "30" size="18"'));
			
		add_settings_field('pantherius_wpusertracker_widget_topcategories_datas-wpusertracker_widget_topcategories_count', 'Number of categories', array(&$this, 'settings_field_input_select'), 'pantherius_wpusertracker_widget_topcategories_datas', 'pantherius_wpusertracker_widget_topcategories_datas-section', array('field' => 'wpusertracker_widget_topcategories_count', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_topcategories_count'], 'min' => 1, 'max' => 20, 'default' => 5, 'other' => ''));
			
			?>
		<?php @settings_fields('pantherius_wpusertracker_widget_topcategories_datas-group'); ?>
		<?php @do_settings_fields('pantherius_wpusertracker_widget_topcategories_datas-group'); ?>
		<?php do_settings_sections('pantherius_wpusertracker_widget_topcategories_datas'); ?>
			<?php
		}
		
		/**
		* Show the counter sidebar widget
		**/
		public function pantherius_wpusertracker_widget_topcategories_show($args=array()) 
		{
			if (isset($args)) extract($args);
			else 
			{
				print('couldn\'t extract args');$before_widget = '';$before_title = '';$after_title = '';$after_widget = '';
			}
			$timezone = get_option('timezone_string');
			echo $before_widget;
			echo $before_title . $this->wpusertracker_widget_datas['wpusertracker_widget_topcategories_title'] . $after_title;
			if (empty($timezone)) print('<ul><li>Error: You have to set the timezone.</li></ul>');
			else
			{
				$topcategories = $this->wpdb->get_results("SELECT ct.name,ct.term_id FROM ".$this->table_names['categories']." tc LEFT JOIN ".$this->wpdb->prefix."terms ct on tc.catid=ct.term_id ORDER BY tc.alltime DESC LIMIT ".$this->wpusertracker_widget_datas['wpusertracker_widget_topcategories_count']);
				print('<ul>');
				foreach($topcategories as $tc)
				{
					print('<li><a href="'.get_category_link($tc->term_id).'">'.$tc->name.'</a></li>');
				}
				print('</ul>');
			}
			echo $after_widget;
		}
		
		public function pantherius_wpusertracker_widget_topposts($args) 
		{
		}
		
		public function pantherius_wpusertracker_widget_topposts_control() 
		{
			$wpusertracker_widget_datas = get_option('wpusertracker_widget_datas');
			$wpusertracker_widget_datas_save = false;
			if (isset($_POST['wpusertracker_widget_topposts_title']))
			{
				$wpusertracker_widget_datas['wpusertracker_widget_topposts_title'] = esc_attr($_POST['wpusertracker_widget_topposts_title']);
				$wpusertracker_widget_datas_save = true;
			}
			if (isset($_POST['wpusertracker_widget_topposts_count']))
			{
				$wpusertracker_widget_datas['wpusertracker_widget_topposts_count'] = esc_attr($_POST['wpusertracker_widget_topposts_count']);
				$wpusertracker_widget_datas_save = true;
			}
			if ($wpusertracker_widget_datas_save == true) update_option('wpusertracker_widget_datas', $wpusertracker_widget_datas);

		add_settings_section('pantherius_wpusertracker_widget_topposts_datas-section', '', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_widget_topposts_datas');

		add_settings_field('pantherius_wpusertracker_widget_topposts_datas-wpusertracker_widget_topposts_title', 'Title', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_widget_topposts_datas', 'pantherius_wpusertracker_widget_topposts_datas-section', array('field' => 'wpusertracker_widget_topposts_title', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_topposts_title'], 'other' => 'MAXLENGTH = "30" size="18"'));
			
		add_settings_field('pantherius_wpusertracker_widget_topposts_datas-wpusertracker_widget_topposts_count', 'Number of posts', array(&$this, 'settings_field_input_select'), 'pantherius_wpusertracker_widget_topposts_datas', 'pantherius_wpusertracker_widget_topposts_datas-section', array('field' => 'wpusertracker_widget_topposts_count', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_topposts_count'], 'min' => 1, 'max' => 20, 'default' => 5, 'other' => ''));
			
			?>
		<?php @settings_fields('pantherius_wpusertracker_widget_topposts_datas-group'); ?>
		<?php @do_settings_fields('pantherius_wpusertracker_widget_topposts_datas-group'); ?>
		<?php do_settings_sections('pantherius_wpusertracker_widget_topposts_datas'); ?>
			<?php
		}
		
		/**
		* Show the counter sidebar widget
		**/
		public function pantherius_wpusertracker_widget_topposts_show($args=array()) 
		{
			if (isset($args)) extract($args);
			else 
			{
				print('couldn\'t extract args');$before_widget = '';$before_title = '';$after_title = '';$after_widget = '';
			}
			$timezone = get_option('timezone_string');
			echo $before_widget;
			echo $before_title . $this->wpusertracker_widget_datas['wpusertracker_widget_topposts_title'] . $after_title;
			if (empty($timezone)) print('<ul><li>Error: You have to set the timezone.</li></ul>');
			else
			{
				$topposts = $this->wpdb->get_results("SELECT pp.post_title,pp.id FROM ".$this->table_names['posts']." tp LEFT JOIN ".$this->wpdb->prefix."posts pp on tp.postid=pp.id ORDER BY tp.alltime DESC LIMIT ".$this->wpusertracker_widget_datas['wpusertracker_widget_topposts_count']);
				print('<ul>');
				foreach($topposts as $tp)
				{
					print('<li><a href="'.get_permalink($tp->id).'">'.$tp->post_title.'</a></li>');
				}
				print('</ul>');
			}
			echo $after_widget;
		}
		
		public function pantherius_wpusertracker_widget_targetedad($args) 
		{
		}
		
		public function pantherius_wpusertracker_widget_targetedad_control() 
		{
			$wpusertracker_widget_datas = get_option('wpusertracker_widget_datas');
			$wpusertracker_widget_datas_save = false;
			if (isset($_POST['wpusertracker_widget_targetedad_title']))
			{
				$wpusertracker_widget_datas['wpusertracker_widget_targetedad_title'] = esc_attr($_POST['wpusertracker_widget_targetedad_title']);
				$wpusertracker_widget_datas_save = true;
			}
			if ($wpusertracker_widget_datas_save == true) update_option('wpusertracker_widget_datas', $wpusertracker_widget_datas);

		add_settings_section('pantherius_wpusertracker_widget_targetedad_datas-section', '', array(&$this, 'settings_section_pantherius_wpusertracker'), 'pantherius_wpusertracker_widget_targetedad_datas');

		add_settings_field('pantherius_wpusertracker_widget_targetedad_datas-wpusertracker_widget_targetedad_title', 'Title', array(&$this, 'settings_field_input_text'), 'pantherius_wpusertracker_widget_targetedad_datas', 'pantherius_wpusertracker_widget_targetedad_datas-section', array('field' => 'wpusertracker_widget_targetedad_title', 'field_value' => $wpusertracker_widget_datas['wpusertracker_widget_targetedad_title'], 'other' => 'MAXLENGTH = "30" size="18"'));
			?>
		<?php @settings_fields('pantherius_wpusertracker_widget_targetedad_datas-group'); ?>
		<?php @do_settings_fields('pantherius_wpusertracker_widget_targetedad_datas-group'); ?>
		<?php do_settings_sections('pantherius_wpusertracker_widget_targetedad_datas'); ?>
			<?php
		}
		
		/**
		* Show the counter sidebar widget
		**/
		public function pantherius_wpusertracker_widget_targetedad_show($args=array()) 
		{
			if (isset($args)) extract($args);
			else 
			{
				print('couldn\'t extract args');$before_widget = '';$before_title = '';$after_title = '';$after_widget = '';
			}
			echo $before_widget;
			echo $before_title . $this->wpusertracker_widget_datas['wpusertracker_widget_targetedad_title'] . $after_title;
			$usercats = $this->wpdb->get_var("SELECT visited_categories FROM ".$this->table_names['users']." WHERE `id`='".base64_decode($_COOKIE['wpusertracker'])."'");
			$category_list = unserialize($usercats);
			if (is_array($category_list))
			{
				arsort($category_list);
				reset($category_list);
				$topcategory = key($category_list);
					$getadv = $this->wpdb->get_var("SELECT adv FROM ".$this->table_names['adverts']." WHERE `catid`='".$topcategory."' ORDER BY RAND()");
					if (!$getadv) $getadv = $this->wpdb->get_var("SELECT adv FROM ".$this->table_names['adverts']." WHERE `catid`='0' ORDER BY RAND()");
			}
			if (!$getadv) $getadv = "Didn't find any category advert to fit, please set up a general advert to display.";
			print('<ul><li>'.stripslashes($getadv).'</li></ul>');
			echo $after_widget;
		}
		/**
		* Add the settings link to the plugins page
		**/
		function plugin_settings_link($links)
		{ 
			$settings_link = '<a href="options-general.php?page=pantherius_wpusertracker">Settings</a>';
			array_unshift($links, $settings_link); 
			return $links; 
		}
		public function settings_section_pantherius_wpusertracker()
		{
		
		}
		
		public function settings_field_input_text($args) 
		{
			if (!isset($pantherius_wpusertracker_settings)) 
			{
					$pantherius_wpusertracker_settings = new pantherius_wpusertracker_settings();
			}
			return $pantherius_wpusertracker_settings->settings_field_input_text($args);
		}
		public function settings_field_input_radio($args) 
		{
			if (!isset($pantherius_wpusertracker_settings)) 
			{
					$pantherius_wpusertracker_settings = new pantherius_wpusertracker_settings();
			}
			return $pantherius_wpusertracker_settings->settings_field_input_radio($args);
		}
		public function settings_field_input_select($args) 
		{
			if (!isset($pantherius_wpusertracker_settings)) 
			{
					$pantherius_wpusertracker_settings = new pantherius_wpusertracker_settings();
			}
			return $pantherius_wpusertracker_settings->settings_field_input_select($args);
		}
	}
}
if(class_exists('pantherius_wpusertracker'))
{
	// call the main class
	$pantherius_wpusertracker = pantherius_wpusertracker::getInstance();
}
?>