<?php
/*** Plugin Name: Dynamic User Directory Exclude User Filter
* Plugin URI: http://sgcustomwebsolutions.com 
* Description: Extends the Dynamic User Directory plugin by allowing you to configure rules for filtering users out of the directory. 
*  
* Version: 1.3 
* Author: Sarah Giles 
* Author URI: http://sgcustomwebsolutions.com 
* License: GPL2 
*/

require 'plugin-update-checker-4.2/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'http://sgcustomwebsolutions.com/wp-content/uploads/dynamic-user-directory-exclude-user-filter.json',
    __FILE__,
    'dynamic-user-directory-exclude-user-filter'
);

define('DUD_EXCLUDE_USER_FILTER_URL', plugin_dir_url(__FILE__));

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'dud_exclude_user_filter_action_links' );

function dud_exclude_user_filter_action_links( $links ) {
  
   $links[] = '<a href="https://sgcustomwebsolutions.com/exclude-user-filter-change-log/" target="_blank">View Changelog</a>';
   return $links;
}

global $wpdb;

if(!defined("DUD_WPDB_PREFIX"))	define('DUD_WPDB_PREFIX', $wpdb->base_prefix);

/*** Cimy User Extra Fields Constants *********************************************/

$dud_Cimy_Table_Name1 = DUD_WPDB_PREFIX . 'cimy_uef_data';	
$dud_Cimy_Table_Name2 = DUD_WPDB_PREFIX . 'cimy_uef_fields';
			
$dud_Cimy_Table_1 = $wpdb->get_results("SHOW TABLES LIKE '" . $dud_Cimy_Table_Name1 . "'");	
$dud_Cimy_Table_2 = $wpdb->get_results("SHOW TABLES LIKE '" . $dud_Cimy_Table_Name2 . "'");

if(!defined("DUD_CIMY_DATA_TABLE"))		
	define('DUD_CIMY_DATA_TABLE', $dud_Cimy_Table_Name1);

if(!defined("DUD_CIMY_FIELDS_TABLE"))		
	define('DUD_CIMY_FIELDS_TABLE', $dud_Cimy_Table_Name2);	

/*** BuddyPress Constants *********************************************************/

$dud_BP_Table_Name1 = DUD_WPDB_PREFIX . 'bp_xprofile_data';	
$dud_BP_Table_Name2 = DUD_WPDB_PREFIX . 'bp_xprofile_fields';
			
$dud_BP_Table_1  = $wpdb->get_results("SHOW TABLES LIKE '" . $dud_BP_Table_Name1 . "'");	
$dud_BP_Table_2  = $wpdb->get_results("SHOW TABLES LIKE '" . $dud_BP_Table_Name2 . "'");
	

if(!defined("DUD_BP_PLUGIN_DATA_TABLE"))		
	define('DUD_BP_PLUGIN_DATA_TABLE', $dud_BP_Table_Name1);	

if(!defined("DUD_BP_PLUGIN_FIELDS_TABLE"))		
	define('DUD_BP_PLUGIN_FIELDS_TABLE', $dud_BP_Table_Name2);	

/**
 * Filters users out of the directory based on the criteria specified in the settings.
 *
 * @return filtered list of user ids 
 **/

function dud_filter_users($uids, $dud_options) {
		
	$dud_filter_fld   			= !empty($dud_options['ud_filter_fld_key']) ? $dud_options['ud_filter_fld_key'] : "";
	$dud_membership_plugin      = !empty($dud_options['ud_filter_fld_type']) ? $dud_options['ud_filter_fld_type'] : "";
	$dud_conditional            = !empty($dud_options ["ud_filter_fld_cond"]) ? $dud_options["ud_filter_fld_cond"] : ""; 
	$dud_compare_value          = !empty($dud_options ["ud_filter_fld_value"]) ? $dud_options["ud_filter_fld_value"] : "";
	$dud_woocommerce_active     = !empty($dud_options ["ud_woocommerce_active"]) ? $dud_options["ud_woocommerce_active"] : "";	
    $user_directory_sort        = !empty($dud_options['user_directory_sort']) ? $dud_options['user_directory_sort'] : null;

	/*** Turn debug on if debug mode is set to "on" ***/
	global $dynamic_ud_debug; 
	$dynamic_ud_debug = false;

	if(current_user_can('administrator'))
		if($dud_options['ud_debug_mode'] === "on")
			$dynamic_ud_debug = true;

    $user_id = 0;
	
	try
	{
		if($dynamic_ud_debug)
		{
			echo "<PRE>Exclude User Filter:<BR><BR>";
		}

		foreach($uids as $dud_key => $uid)
		{
			if($user_directory_sort === "last_name")
				$user_id = $uid->user_id;
		    else
			    $user_id = $uid->ID; 
			
			if($dynamic_ud_debug)
			{
				if($user_directory_sort === "last_name")
					$user_last_name = get_user_meta($user_id, 'last_name', true);
				else
					$user_last_name = $uid->display_name;
				
				echo "Checking user " . $user_last_name . "<BR>";
			}
			
			//*** Custom Meta Field **************************************************/
			
			if(!empty($dud_filter_fld))
			{
				$dud_filter_fld_val = dud_get_filter_meta_val($user_id, $dud_filter_fld, $dud_membership_plugin); 
							    
				if($dynamic_ud_debug)
				{					
					echo "--> User: " . $user_last_name . "<BR>";
					echo "--> Filter Field Name: " . $dud_filter_fld . "<BR>";
					echo "--> Filter Field State Must (Be): " . $dud_conditional . "<BR>";
					if(!empty($dud_compare_value)) echo "--> Compare Val: " . $dud_compare_value . "<BR>";
					echo "--> Filter Field Val: ";
					
					if(is_array($dud_filter_fld_val))
					{
						echo "<BR>";
						var_dump($dud_filter_fld_val);
					}
					else
						echo $dud_filter_fld_val . "<BR>";
						
					
					echo "<BR>";
				}
				
				if($dud_conditional === "selected" || $dud_conditional === "not_empty")
				{
					//if a standard array
					if(is_array($dud_filter_fld_val))
					{
						foreach ($dud_filter_fld_val as $key => $value) 
						{
							if(!empty($value)) unset($uids[$dud_key]);
						}
					}
					//if a serialized array
					else if(!is_array($dud_filter_fld_val) && substr($dud_filter_fld_val, 0, 2) === "a:")
					{
						if(substr($dud_filter_fld_val, 0, 3) !== "a:0")
							unset($uids[$dud_key]);
					}
					else if(!empty($dud_filter_fld_val)) unset($uids[$dud_key]);	
				}
				else if($dud_conditional === "not_selected" || $dud_conditional === "empty")
				{
					//if a serialized array
					if(!is_array($dud_filter_fld_val) && substr($dud_filter_fld_val, 0, 2) === "a:")
					{				
						if(substr($dud_filter_fld_val, 0, 3) === "a:0")
							unset($uids[$dud_key]);
					}
					else if(empty($dud_filter_fld_val)) unset($uids[$dud_key]);	
				}
				else if($dud_conditional === "equal_to")
				{
					if($dud_filter_fld_val === $dud_compare_value) unset($uids[$dud_key]);	
				}
				else if($dud_conditional === "not_equal_to")
				{
					if($dud_filter_fld_val !== $dud_compare_value) unset($uids[$dud_key]);	
				}
				else if($dud_conditional === "contain")
				{
					if(!empty($dud_compare_value)) {
						if(strpos($dud_filter_fld_val, $dud_compare_value) !== false) unset($uids[$dud_key]);
					}					
				}
				else if($dud_conditional === "not_contain")
				{
					if(!empty($dud_compare_value)) {
						if(strpos($dud_filter_fld_val, $dud_compare_value) == false) unset($uids[$dud_key]);
					}
				}
			}
			
			//*** WooCommerce ****************************************************/
			
			/*if(function_exists( 'wc_memberships_get_user_active_memberships' ) && !empty($dud_woocommerce_active))
			{
				// WooCommerce Membership Plans
				$membership_name = "";
				$user_plans = ( function_exists( 'wc_memberships_get_user_active_memberships' ) ) ? wc_memberships_get_user_active_memberships($user_id) : 0;
				
				if ( ! empty( $user_plans ) && is_array( $user_plans ) && class_exists( 'WC_Memberships_Membership_Plan' ) ) 
				{
					//loop through each of the current user's plans
					foreach( $user_plans as $user_plan )
					{
						$plan = new WC_Memberships_Membership_Plan( intval( $user_plan->plan_id ) );			
						$membership_name = $plan->name;
					}
				}
				
				if(empty($membership_name) && function_exists( 'wc_memberships_get_user_memberships' ))
				{
					unset($uids[$dud_key]);
				}
			}*/
			
			//*** BuddyPress ****************************************************/
			
			if(function_exists('bp_is_active'))
			{
				$dud_bp_last_activity_duration   = !empty($dud_options['ud_bp_last_activity_duration']) ? $dud_options['ud_bp_last_activity_duration'] : "";
				$dud_filter_bp_spammer      	 = !empty($dud_options['ud_filter_bp_spammer']) ? $dud_options['ud_filter_bp_spammer'] : "";
				$dud_filter_bp_inactive          = !empty($dud_options['ud_filter_bp_inactive']) ? $dud_options['ud_filter_bp_inactive'] : "";
				$dud_filter_bp_no_last_activity  = !empty($dud_options['ud_filter_bp_no_last_activity']) ? $dud_options['ud_filter_bp_no_last_activity'] : "";
				
				// Get the user's last activity
				$orig_last_activity_date = bp_get_user_last_activity($user_id);
				
				if(!empty($dud_filter_bp_no_last_activity) 
					&& (empty($orig_last_activity_date) || $orig_last_activity_date === '0000-00-00 00:00:00') ) 
				{
					if($dynamic_ud_debug)
					{
						echo "--> " . $user_last_name . " has no last BP activity date.<BR><BR>";
					}
							
					unset($uids[$dud_key]);
				}				
				else if(!empty($dud_bp_last_activity_duration))
				{				
					$activity = $orig_last_activity_date;
					$user = new WP_User( $user_id );
					
					// Make sure it's numeric
					if (!is_numeric($activity)) {
						$activity = strtotime($activity);
					}
					
					if($dud_filter_bp_inactive === "show_inactive" || empty($dud_filter_bp_inactive))
					{
						// If account is not activated last activity is the time user registered.
						if (isset($user->user_status) && 2 == $user->user_status) 
						{
							$activity = $user->user_registered; 
						}
					}
					
					if($dud_bp_last_activity_duration > 1)
						$dud_bp_last_activity_duration_cp = "+" . $dud_bp_last_activity_duration . " days";
					else
						$dud_bp_last_activity_duration_cp = "+" . $dud_bp_last_activity_duration . " day";
					
					if($dynamic_ud_debug)
					{
						echo "--> " . $user_last_name . " BP last activity date: " .  $orig_last_activity_date  . "<BR><BR>";
					}						
					// If it's been longer than the last activity duration 
					if ( strtotime($dud_bp_last_activity_duration_cp, $activity) < strtotime(bp_core_current_time())
							&& !(empty($dud_filter_bp_no_last_activity) && (empty($activity) || $orig_last_activity_date  === '0000-00-00 00:00:00'))) 
					{
						if($dynamic_ud_debug)
						{
							echo "--> " . $user_last_name . " has a BP last activity date greater than or equal to " . $dud_bp_last_activity_duration . " day(s).<BR><BR>";
						}
					
						unset($uids[$dud_key]);
					}
					else if(isset($user->user_status) && 1 == $user->user_status && !empty($dud_filter_bp_spammer))
					{
						if($dynamic_ud_debug)
						{
							echo "--> " . $user_last_name . " is marked as a spammer in BuddyPress.<BR><BR>";
						}
						
						unset($uids[$dud_key]);
					}
				}
				else if($dud_filter_bp_inactive === "hide_inactive" || !empty($dud_filter_bp_spammer))
				{
					$user = new WP_User( $user_id );
					
					if($dud_filter_bp_inactive === "hide_inactive")
					{
						if (isset($user->user_status) && 0 !== $user->user_status) 
						{
							if($dynamic_ud_debug)
							{
								echo "--> " . $user_last_name . " is an inactive user in BuddyPress.<BR><BR>";
							}
						
							unset($uids[$dud_key]);
						}
					}
					else if(isset($user->user_status) && 1 == $user->user_status && !empty($dud_filter_bp_spammer))
					{
						if($dynamic_ud_debug)
						{
							echo "--> " . $user_last_name . " is flagged as a spammer in BuddyPress.<BR><BR>";
						}
						
						unset($uids[$dud_key]);
					}
				}
			}
			
			//*** MemberPress *************************************************/
				
			if(class_exists('MeprUser'))
			{
				$dud_mp_one_time_txn   = !empty($dud_options['ud_mp_one_time_txn']) ? $dud_options['ud_mp_one_time_txn'] : "";
				$dud_mp_hide_statuses  = !empty($dud_options['ud_mp_hide_statuses']) ? $dud_options['ud_mp_hide_statuses'] : "";
				$dud_mp_hide_subs      = !empty($dud_options['ud_mp_hide_subs']) ? $dud_options['ud_mp_hide_subs'] : "";

//                $membership = new MeprProduct(); //A MeprProduct object
//                $all_memberships = $membership->get_all();
//
//                $undud_mp_hide_subs = [];
//                foreach($all_memberships as $membership)
//                {
//                    if(!empty($dud_mp_hide_subs)){
//                        if (in_array($membership->ID, $dud_mp_hide_subs))continue;
//                        $undud_mp_hide_subs[] = $membership->ID;
//                    }
//                }
//                $dud_mp_hide_subs = $undud_mp_hide_subs;

				$dud_mp_hide_inactive  = !empty($dud_options['ud_mp_hide_inactive']) ? $dud_options['ud_mp_hide_inactive'] : "";
				$dud_mp_show_multiple  = !empty($dud_options['ud_mp_show_multiple']) ? $dud_options['ud_mp_show_multiple'] : "";
				$dud_recurring_sub = false;
				
				if(!empty($dud_mp_hide_inactive) || !empty($dud_mp_hide_statuses) || !empty($dud_mp_one_time_txn) || !empty($dud_mp_hide_subs))
				{
					$user            = new MeprUser( $user_id );
					$get_memberships = $user->active_product_subscriptions();


					if(!empty($dud_mp_hide_inactive))
					{
						//See if user has an active membership
						if(empty( $get_memberships ))
						{							
							if($dynamic_ud_debug)
							{
								echo "--> " . $user_last_name . " has no active MemberPress memberships.<BR>";
							}
							
							unset($uids[$dud_key]);
						} 
					}

					$active_subscriptions = $user->active_product_subscriptions('ids');

//					echo "Active ids: <BR>";
//					var_dump($active_subscriptions);
//					echo "<BR>";

					if(!empty($dud_mp_hide_subs))
					{
						//Get the product ids of the user's active subs
						$active_subscriptions = $user->active_product_subscriptions('ids');

						/*foreach($active_subscriptions  as $active_sub)
						{
							foreach($dud_mp_hide_subs as $hide_sub)
							{
								if($active_sub == $hide_sub)
								{
									if($dynamic_ud_debug)
									{
										echo "--> " . $user_last_name . " has a subscription marked for hiding.<BR>";
									}

									unset($uids[$dud_key]);
									break 2;
								}
							}
						}*/

						$has_active_membership = false;

						if($dud_mp_show_multiple)
						{
							foreach($active_subscriptions  as $active_sub)
							{
								if(in_array( $active_sub , $dud_mp_hide_subs ))
								{
									continue;
								}
								else
								{
									$has_active_membership = true;
									if($dynamic_ud_debug)
									{
										echo "--> " . $user_last_name . " has at least one active subscription (" . $active_sub . ")<BR>";
									}
								}
							}
						}

//						echo '<pre>';
//						print_r($dud_mp_hide_subs);
//						echo '</pre>';

						if(!$has_active_membership)
						{
						    $flag = false;
                            foreach($active_subscriptions as $subscription){
                                if(in_array( $subscription , $dud_mp_hide_subs )) {
                                    $flag = true;
                                    break;
                                }
                            }
                            if ($flag)continue;
                            unset($uids[$dud_key]);
//							foreach($dud_mp_hide_subs as $hide_sub)
//							{
//
//								if(in_array( $hide_sub , $active_subscriptions ))
//								{
//									if($dynamic_ud_debug)
//									{
//										echo "--> " . $user_last_name . " has a subscription marked for hiding (" . $hide_sub . ")<BR>";
//									}
//
//									unset($uids[$dud_key]);
//								}
//							}
						}
					}


					if((!empty($dud_mp_hide_statuses) || !empty($dud_mp_one_time_txn)) && !empty($active_subscriptions))
					{
							//See if user has an active subscription
							$active_subscriptions = $user->active_product_subscriptions('transactions');

							foreach($active_subscriptions as $active_subscription)
							{
								//Transaction statuses: Pending, Failed, Refunded, or Complete
								//$trans_stat = $active_subscription->status;

								//Subscription statuses: Pending, Active, Suspended, Cancelled

								$sub = new MeprSubscription($active_subscription->subscription_id);

								//echo "Sub status: " . $sub->status . "<BR>";
								//echo "Trans status: " . $trans_stat . "<BR>";

								if(!empty($sub))
								{
									if(strtoupper($sub->status) == "ACTIVE")
									{
										$dud_recurring_sub = true;
									}

									if(!empty($dud_mp_hide_statuses))
									{
										foreach($dud_mp_hide_statuses as $dud_mp_hide_status=>$status)
										{
											if(strtoupper ($sub->status) === strtoupper ($status))
											{
												if($dynamic_ud_debug)
												{
													echo "--> " . $user_last_name . " has a MemberPress subscription in " . $sub->status . " status.<BR>";
												}

												unset($uids[$dud_key]);
											}
										}
									}
								}
							}

						if(!empty($dud_mp_one_time_txn) && $dud_recurring_sub == false)
						{
							if($dynamic_ud_debug)
							{
								echo "--> " . $user_last_name . " does not have any recurring subscriptions.<BR>";
							}

							unset($uids[$dud_key]);
						}
					}
				}
			}
		}
			
		$uids = array_values($uids);
	}
	catch(Exception $e)
	{
		$uids = array_values($uids);
		return $uids;
	}
		
	if($dynamic_ud_debug)
	{
		/*echo "returning these uids: <BR>";
		var_dump($uids);
		echo "<BR>";*/
	
		echo "</PRE>";
	}
//					echo '<pre>';
//					print_r($uids);
//                    echo '</pre>';
	return $uids;
   
}
add_filter('dud_filter_users', 'dud_filter_users',10,2);

function dud_get_filter_meta_val($uid, $dud_filter_fld, $dud_membership_plugin) {
	
	global $wpdb;
	
	//echo "In dud_get_filter_meta_val, uid is " . $uid . ", filter fld is: " . $dud_filter_fld . ", membership plugin is: " . $dud_membership_plugin . "<BR>";

	if($dud_membership_plugin === "cimy")
	{
		//Cimy
		$dud_sql = "SELECT data.VALUE FROM " . DUD_CIMY_DATA_TABLE . " as data JOIN " . DUD_CIMY_FIELDS_TABLE . 
					" as efields ON efields.id=data.field_id WHERE (efields.NAME='$dud_filter_fld' AND data.USER_ID = $uid)";
					
		$results = $wpdb->get_results($dud_sql);
		
		foreach($results as $result)
		{
			if($results)
				return $result->VALUE;
		}
		
	}
	else if($dud_membership_plugin === "bp")
	{
		//BP	
		$dud_sql = "SELECT data.value FROM " . DUD_BP_PLUGIN_DATA_TABLE . " as data JOIN " . DUD_BP_PLUGIN_FIELDS_TABLE . 
					" as efields ON efields.id=data.field_id WHERE (efields.name='$dud_filter_fld' AND data.user_id = $uid)";
		
		//echo "Exclude User Filter SQL: $dud_sql<BR>";
		
		$results = $wpdb->get_results($dud_sql);
		
		foreach($results as $result)
		{
			if($results)
				return $result->value;
			
		}
	}
	else if($dud_membership_plugin === "s2m")
	{
		try
		{
			//S2Member
			$s2m_custom_flds = get_user_meta($uid, DUD_WPDB_PREFIX . 's2member_custom_fields');
			$s2m_custom_flds = !empty($s2m_custom_flds[0]) ? $s2m_custom_flds[0] : null; //it will always be an array even for single values
			
			if(!empty($s2m_custom_flds)) 
			{
				foreach ($s2m_custom_flds as $key => $value) 
				{ 
					$key = strtoupper ($key);
					
					if($key === strtoupper($dud_filter_fld)) 
						return $value;	
				}
			}
		}
		catch(Exception $e)
		{
			return "";
		}
	}
    else
	{
		return get_user_meta($uid, $dud_filter_fld, true);
	}
	
	return "";
}

function dud_filter_users_letter_links($letters, $uids, $dud_options){
	
	$user_directory_sort     = !empty($dud_options['user_directory_sort']) ? $dud_options['user_directory_sort'] : null;
	$ud_sort_fld_key         = !empty($dud_options['ud_sort_fld_key']) ? $dud_options['ud_sort_fld_key'] : null;
	$ud_sort_fld_type        = !empty($dud_options['ud_sort_fld_type']) ? $dud_options['ud_sort_fld_type'] : null;
	$ud_sort_cat_link_caps 	 = !empty($dud_options['ud_sort_cat_link_caps']) ? $dud_options['ud_sort_cat_link_caps'] : "";
	$ud_custom_sort          = !empty($dud_options['ud_custom_sort']) ? $dud_options['ud_custom_sort'] : null;
	
	$plugins = get_option('active_plugins');
		
	if(in_array( 'dynamic-user-directory-custom-sort-fld/dynamic-user-directory-custom-sort-fld.php' , $plugins ) && !empty($ud_custom_sort))
		$custom_sort_active = true;
	else
		$custom_sort_active = false;
	
	$letter_exists = array();
	
	if(!(empty($ud_sort_fld_key) || !$custom_sort_active))
	{	
		foreach($uids as $uid) 
		{
			if($user_directory_sort === "last_name")
				$category =  dud_get_filter_meta_val($uid->user_id, $ud_sort_fld_key, $ud_sort_fld_type);
			else
				$category =  dud_get_filter_meta_val($uid->ID, $ud_sort_fld_key, $ud_sort_fld_type);
				
			if(!in_array(strtoupper($category), $letter_exists) && !in_array(ucwords($category), $letter_exists))
			{
				if($ud_sort_cat_link_caps === 'all')
					array_push($letter_exists, strtoupper($category));
				else
					array_push($letter_exists, ucwords($category));
			}
		}	
	}	
	else
	{
		if(!empty($dud_options['ud_filter_fld_performance']))
			$letter_exists = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
		else 
		{
			foreach($uids as $uid) {
					
				if($user_directory_sort === "last_name")
				{
					$user_last_name = get_user_meta($uid->user_id, 'last_name', true);
				}
				else
				{
					$user_id = $uid->ID; 
					$user_last_name = $uid->display_name;
				}
				
				$last_name_letter = substr($user_last_name, 0, 1);
				
				if(ctype_alpha($last_name_letter)) 
				{
					if(!in_array(strtoupper($last_name_letter), $letter_exists))
					{
						array_push($letter_exists, strtoupper($last_name_letter));
					}
				}
			}
		}
	}
	
	return $letter_exists;	
}
add_filter('dud_filter_users_letter_links', 'dud_filter_users_letter_links',10,3);

function dud_filter_users_get_first_letter($letter, $uids, $dud_options){
	
	$user_directory_sort     = !empty($dud_options['user_directory_sort']) ? $dud_options['user_directory_sort'] : null;
	$ud_sort_fld_key         = !empty($dud_options['ud_sort_fld_key']) ? $dud_options['ud_sort_fld_key'] : null;
	$ud_sort_fld_type        = !empty($dud_options['ud_sort_fld_type']) ? $dud_options['ud_sort_fld_type'] : null;
	$ud_sort_cat_link_caps 	 = !empty($dud_options['ud_sort_cat_link_caps']) ? $dud_options['ud_sort_cat_link_caps'] : "";
	$ud_custom_sort          = !empty($dud_options['ud_custom_sort']) ? $dud_options['ud_custom_sort'] : null;
	
	$plugins = get_option('active_plugins');
		
	if(in_array( 'dynamic-user-directory-custom-sort-fld/dynamic-user-directory-custom-sort-fld.php' , $plugins ) && !empty($ud_custom_sort))
		$custom_sort_active = true;
	else
		$custom_sort_active = false;
	
	foreach($uids as $dud_key => $uid) 
	{
		if(!(empty($ud_sort_fld_key) || !$custom_sort_active))
		{
			if($letter !== dud_get_filter_meta_val($uid->user_id, $ud_sort_fld_key, $ud_sort_fld_type))
				unset($uids[$dud_key]);	
		}	
		else 
		{
			if($user_directory_sort === "last_name")
			{
				$user_last_name = get_user_meta($uid->user_id, 'last_name', true);
			}
			else
			{
				$user_id = $uid->ID; 
				$user_last_name = $uid->display_name;
			}
			
			$last_name_letter = substr($user_last_name, 0, 1);
			
			if(strtoupper($last_name_letter) !== strtoupper($letter))
				unset($uids[$dud_key]);		
		}
	}
	
	$uids = array_values($uids);
	
	return $uids;	
}
