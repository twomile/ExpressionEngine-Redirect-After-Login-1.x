<?php

/**
 * =============================================================
 * For allowing customization of system behavior after
 * login and logout, by skipping the confirmation page and
 * taking the user to a specific location. 
 * 
 * Version:	1.3.0
 * Released:	7/26/2010
 * Authors:	Corey Snipes <corey@twomile.com>
 * 		Kevin Major <kevin@twomile.com>
 * 		Noah Kuhn <http://noahkuhn.com>
 * =============================================================
 */

class Twomile_login_redirect_ext
{
	
	// ----------------------------------
	// Class params
	// ----------------------------------
	
	var $settings		= array();
	var $ext_class		= "Twomile_login_redirect_ext";
	var $name		= "Twomile Login Redirect";
	var $version		= "1.3.0";
	var $description	= "Gives control over user destination after login or logout.";
	var $settings_exist	= "y";
	var $docs_url		= "";


	/**
	 * ----------------------------------------
	 * Constructor.
	 * 
	 * @author	Corey Snipes
	 *          <corey.snipes@twomile.com>
	 *          12/23/2007
	 * ----------------------------------------
	 */
	function Twomile_login_redirect_ext ($settings = "")
	{
		$this->settings = $settings;
	}
	// End Twomile_login_redirect_ext()


	/**
	 * ----------------------------------------
	 * For activating the extension.
	 * 
	 * @author	Corey Snipes
	 *          <corey.snipes@twomile.com>
	 *          12/23/2007
	 * ----------------------------------------
	 */
	function activate_extension ()
	{
		global $DB;

		// ----------------------------------
		//  Default settings
		// ----------------------------------

		$default_settings = serialize(array(
			"display_confirmation_after_login"	=> "no"
			,"display_confirmation_after_logout"	=> "no"
			,"lastpage_destination"			=> "no"
			,"logout_lastpage_destination"		=> "no"
			,"login_page_url"			=> "/member/login"
			,"login_destination"			=> "site/index"
			,"logout_destination"			=> "site/index"
                    )
		);

		// ----------------------------------
		//  Add custom processing to member_member_login_single
		// ----------------------------------
		
		$DB->query(
			$DB->insert_string(
				"exp_extensions", array(
				"extension_id" => "",
				"class"        => $this->ext_class,
				"method"       => "process_login",
				"hook"         => "member_member_login_single",
				"settings"     => $default_settings,
				"priority"     => 7,
				"version"      => $this->version,
				"enabled"      => "y"
				)
			)
		);
		
		// ----------------------------------
		//  Add custom processing to member_member_logout
		// ----------------------------------
		
		$DB->query(
			$DB->insert_string(
				"exp_extensions", array("extension_id" => "",
				"class"        => $this->ext_class,
				"method"       => "process_logout",
				"hook"         => "member_member_logout",
				"settings"     => $default_settings,
				"priority"     => 7,
				"version"      => $this->version,
				"enabled"      => "y"
				)
			)
		);

	}
	// End activate_extension()

	
	/**
	 * ----------------------------------------
	 * For upgrading the extension from a 
	 * prior version.
	 * 
	 * @author	Corey Snipes
	 *          <corey.snipes@twomile.com>
	 *          12/23/2007
	 * ----------------------------------------
	 */
	function update_extension ($current = "")
	{

		// ----------------------------------
		//  No adjustments needed
		// ----------------------------------

		return FALSE;
	}
	// End update_extension()


	/**
	 * ----------------------------------------
	 * For sending the user to a custom
	 * destination after login, and determining
	 * whether the confirmation page should
	 * be displayed.
	 * 
	 * @author	Corey Snipes
	 *          <corey.snipes@twomile.com>
	 *          12/23/2007
	 * ----------------------------------------
	 */   
	function process_login()
	{
		global $FNS, $LANG, $OUT, $SESS, $IN, $DSP, $PREFS;
		$url = NULL;
                $site_index = $PREFS->core_ini['site_index'];
                $site_url = $PREFS->core_ini['site_url'];

                if (empty($site_index)){

                    //Remove last slash from url and set base
                    $site_url = substr($site_url,0,-1);
                    $site_base = $site_url;
                }

                else $site_base = $site_url . $site_index;


                //Set variables to stop it from throwing variable not set errors
		if (empty($SESS->tracker['1'])) $SESS->tracker['1']="/";
		if (empty($SESS->tracker['2'])) $SESS->tracker['2']="/";

                //Check if any trackers are set to index and convert to /
                if ($SESS->tracker['1']=="index") $SESS->tracker['1']="/";
                if ($SESS->tracker['2']=="index") $SESS->tracker['2']="/";


		// -------------------------------------------
		//  Set destination URL
		// -------------------------------------------

		// Check if 'last page' is set to yes
		if ($this->settings['lastpage_destination'] == "yes")
		{
			//Check if login page url is defined and if it is also in the previous page url - if so go back 2 pages, else go back 1			
			if ($this->settings['login_page_url'] != ""){
				if ((stristr($SESS->tracker['1'], $this->settings['login_page_url'])))
				{
					$url = $site_base . $SESS->tracker['2'];
				}
				else
				{
                       			$url = $site_base . $SESS->tracker['1'];
				}
			}
			else
			{
                       			$url = $site_base . $SESS->tracker['1'];
			}
		}
		else
		{
                    $url = $site_base . $this->settings["login_destination"];                    
                }

                if (strlen($url) < 1) $url = "/";
		
		// -------------------------------------------
		//  If skipping confirmation page, redirect here
		// -------------------------------------------

		if ($this->settings["display_confirmation_after_login"] != "yes")
		{
			$FNS->redirect($url);
		}
		
		// -------------------------------------------
		//  Otherwise, build the display output 
		// -------------------------------------------
		
		$data = array(	
			'title'		=> $LANG->line('mbr_login'),
			'heading'	=> $LANG->line('thank_you'),
			'content'	=> $LANG->line('mbr_you_are_logged_in'),
			'redirect'	=> $url,
			'link'		=> array($url, "")
		);
		$OUT->show_message($data);
				
		// -------------------------------------------
		//  Return 
		// -------------------------------------------
		
		return FALSE;		
		
	}
	// End process_login()

	
	/**
	 * ----------------------------------------
	 * For sending the user to a custom
	 * destination after logout, and determining
	 * whether the confirmation page should
	 * be displayed.
	 * 
	 * @author	Corey Snipes
	 *          <corey.snipes@twomile.com>
	 *          12/23/2007
	 * ----------------------------------------
	 */
	function process_logout()
	{
		global $FNS, $OUT, $LANG, $PREFS, $SESS;
		$url = NULL;

                $site_index = $PREFS->core_ini['site_index'];
                $site_url = $PREFS->core_ini['site_url'];

                if (empty($site_index)){

                    //Remove last slash from url and set base
                    $site_url = substr($site_url,0,-1);
                    $site_base = $site_url;
                }

                else $site_base = $site_url . $site_index;

                // -------------------------------------------
		//  Set destination URL
		// -------------------------------------------
                
                //Check if any trackers we use are set to index and convert to /
                if ($SESS->tracker['0']=="index") $SESS->tracker['0']="/";

		// Check if 'logout last page' is set to yes
		if ($this->settings['logout_lastpage_destination'] == "yes")
		{
                    $url = $site_base . $SESS->tracker['0'];
		}
		else
		{
                    $url = $site_base . $this->settings["logout_destination"];
                }

		if (strlen($url) < 1) $url = "/";
		
		// -------------------------------------------
		//  If skipping confirmation page, redirect here
		// -------------------------------------------

		if ($this->settings["display_confirmation_after_logout"] != "yes")
		{
			$FNS->redirect($url);
		}
		
		// -------------------------------------------
		//  Otherwise, build the display output 
		// -------------------------------------------
		
		$data = array(
			'title' 	=> $LANG->line('mbr_login'),
			'heading'	=> $LANG->line('thank_you'),
			'content'	=> $LANG->line('mbr_you_are_logged_out'),
			'redirect'	=> $url,
			'link'		=> array($url, "")
		);
		$OUT->show_message($data);
				
		// -------------------------------------------
		//  Return 
		// -------------------------------------------
		
		return FALSE;
				
	}
	// End process_logout()


	/**
	 * ----------------------------------------
	 * For handling the settings for this 
	 * extension.
	 * 
	 * @author	Corey Snipes
	 *          <corey.snipes@twomile.com>
	 *          12/23/2007
	 * ----------------------------------------
	 */
	function settings()
	{
		$settings = array();
		$settings["display_confirmation_after_logout"] = array( "s", array( "no" => "no", "yes" => "yes" ), "yes" );
		$settings["logout_lastpage_destination"] = array( "s", array( "no" => "Specific Page (below)", "yes" => "Last Page Visited" ), "yes" );
		$settings["logout_destination"] = "";
		$settings["display_confirmation_after_login"] = array( "s", array( "no" => "no", "yes" => "yes" ), "yes" );
		$settings["lastpage_destination"] = array( "s", array( "no" => "Specific Page (below)", "yes" => "Last Page Visited" ), "yes" );
		$settings["login_destination"] = "";
		$settings["login_page_url"] = "";
                return $settings;
	}
	// End settings()

}
?>