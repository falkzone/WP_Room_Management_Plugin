<?php
/*
Plugin Name: Wordpress Time Table Room Management Plugin
Plugin URI: http://falkzone.github.io
Description: create weekly time tables and display them on your site.
Version: 1.0
Author: Falkzone
Author URI: http://falkzone.github.io
*/

/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

//create a class to avoid plugin collisions
global $wpdb;
$dir = preg_replace("/^.*[\/\\\]/", "", dirname(__FILE__));
define ("wttrm_DIR", "/wp-content/plugins/" . $dir);
define("wttrm_DIR_URL",get_bloginfo('wpurl') .wttrm_DIR);
define ("wttrm_DIR_I18N", wttrm_DIR . "/locales/");
define ("wttrm_TIMETABLE",$wpdb->prefix . "wttrm_timetables");
define ("wttrm_ENTRYTABLE",$wpdb->prefix . "wttrm_ttentries");




add_action('init', 'wttrmPlugIn_load_translation_file');

function wttrmPlugIn_load_translation_file() {
	// get current language
	$locale = get_locale();
        $plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'wttrmPlugIn','wp-content/plugins/' . $plugin_dir,$plugin_dir.'\locales' );
        //wttrm_DIR_I18N.'wttrmPlugIn_'.$locale.'.mo'
       
        
}

	/*
	** Database and app Installation function
	*/
	if(!function_exists("wttrm_install")){
		
		function wttrm_install () {
		   global $wpdb;
		   global $wttrm_db_version;
		   $wttrm_db_version 		= "1.3";
			
			
		   	//create timetables table
		   	// Check if exists
		  	if($wpdb->get_var("SHOW TABLES LIKE '".wttrm_TIMETABLE."'") != wttrm_TIMETABLE) {
		   	   	$sql = "CREATE TABLE " . wttrm_TIMETABLE . " (
						id INT NOT NULL AUTO_INCREMENT,
						id_entry INT NOT NULL ,
						mon VARCHAR( 30 ) NULL ,
						tue VARCHAR( 30 ) NULL ,
						wed VARCHAR( 30 ) NULL ,
						thu VARCHAR( 30 ) NULL ,
						fri VARCHAR( 30 ) NULL ,
						sat VARCHAR( 30 ) NULL ,
						sun VARCHAR( 30 ) NULL,
						UNIQUE KEY id (id)				
						);";
		   	   	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				//insert data
				$sql="insert into ".wttrm_TIMETABLE." (id_entry,mon, tue, wed, thu, fri, sat, sun) values(1,'from 8am to 5pm','from 8am to 5pm','from 8am to 5pm','from 8am to 5pm','from 8am to 5pm','closed','closed');";
				$results = $wpdb->query( $sql );
				
		  	 	}
			//create entries table
			// check if exists
		  	if($wpdb->get_var("SHOW TABLES LIKE '".wttrm_ENTRYTABLE."'") != wttrm_ENTRYTABLE) {
			    $sql = "CREATE TABLE " . wttrm_ENTRYTABLE . " (
					    id INT NOT NULL AUTO_INCREMENT,
					    entryname VARCHAR( 180 ),
					    UNIQUE KEY id (id)
					    );";
			    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
                            
				// insert data
				$sql ="insert into ".wttrm_ENTRYTABLE." (entryname) values('Drugstore');";
				$results = $wpdb->query( $sql );
				
				
				//add the db version into wordpress
				add_option("wttrm_db_version", $wttrm_db_version);	
				
		  	 	}
				
		  	// Upgrade Section (not needed here as it is the first one)
		  	$installed_ver = get_option( "wttrm_db_version" );
		  	if( $installed_ver != $wttrm_db_version ) {
		  		//the new sql version (but this is fake)
		  		$sql = "Alter TABLE " . wttrm_ENTRYTABLE . " 
					    MODIFY  entryname VARCHAR( 180 )
				   		;";
				dbDelta($sql);
                                $sql = "alter TABLE " . wttrm_TIMETABLE . " 
										MODIFY mon VARCHAR( 30 ), MODIFY tue VARCHAR( 30 ),MODIFY wed VARCHAR( 30 ),
						MODIFY thu VARCHAR( 30 ),MODIFY fri VARCHAR( 30 ) ,MODIFY sat VARCHAR( 30 ),MODIFY sun VARCHAR( 30 );";
				dbDelta($sql);
				//change the version option of this plugin (not for this install)
		    	update_option( "wttrm_db_version", $wttrm_db_version );
		  		
		  	}//endif
		  	
		  	 	
		}//end of install 
	//execute the installation upon activation	
	register_activation_hook(__FILE__,"wttrm_install");
	}



	/*
	** Admin Menu Creation
	*/
	function wttrm_menu() {
		 if (function_exists('add_menu_page')) 
			{
				add_menu_page('Weekly Time Table', 'wttrm Time Tables', 'administrator', 'tophor','weeklytt_home');
		  	}
	
		 if (function_exists('add_submenu_page')) 
			{	// entry menus
				add_submenu_page( 'tophor', 'Manage entries for a new time table', 'Manage entries', 'administrator', 'mng_entry', 'mng_entry');
				// timetable menus
			 	add_submenu_page( 'tophor', 'Manage Weekly time table', 'Manage wttrm', 'administrator', 'mng_wttrm', 'mng_wttrm');
			}
	}

	/*
	** Home page of the plugin
	*/
	function weeklytt_home()
	{
			$outp = '<div class="wrap">';
			$outp.= '<h1>'.__('Weekly Time Table','wttrmPlugIn').'</h1>';
			$outp .= '<h2>'.__('Time Table Room Management for your WordPress Site','wttrmPlugIn').'</h2>
			<p>This plugin lets you create time tables and dispaly them into your Wordpress site</p>';
			$outp .= "<p>This is an example:</p>";
			$outp.= wttrmdsp(0);
			$outp .= "<h2>".__('Usage','wttrmPlugIn')."</h2>";
			$outp .= __('You must first create an entry to create a Time Table.The plugin doesn\'t format. However, length is limited to 30 caracters. Each <em>wttrm time table</em> has
                        an id that should be used when displaying it using the shortcode.<br/>The shortcode to display the time table is <em><strong>[wttrmdsp entry_id=5]</strong></em>, where 5 
                        is an id of a <em>time table</em>. You can put a list of IDs in the shortcode to have a multiple entries time table, just like this <em><strong>[wttrmdsp entry_id=5,17,9]</strong></em>.<br/>
			Use css to style your timetable. A time table as an <em>id=\'wttrm\'</em> attribute.<br/>	When displayed, the entry is in a container with attribute : <em>id=\'wttrmentry\'</em>
			<br/><br/>As an example, this is the css that is used in the admin side :<br/>
			<em>#wttrm {padding:2px;}<br/>#wttrm th { background-color:#DDDDDD; padding:5px;}<br/>#wttrm tr { background-color:#EEEEEE;padding:5px;}<br/>#wttrm td {padding:3px; } </em><br/><br/>It\'s a simple plugin, feel free to adapt it at will !','wttrmPlugIn');
			$outp .= '<br/><div><table class="widefat" style="margin-top: .5em"><thead><tr valign="top">
			<th>Wordpress Time Table plugin</th></tr></thead><tbody><tr>	
			<td>Find me on <a href="https://falkzone.github.io/" target="_blank"></a>.
			</td></tr></tbody></table></div>';
			$outp .= '</div><!--end wrap -->';
			echo $outp; 
	}

	/*
	** Select list box for entries
	*/
	function list_wttrmentries($style)
	{		// if $style is 1 then it's a select, 2 it's an unordered list
			global $wpdb;
			$delaction = "del_entry";
			$edtfrmaction ="edt_frm";
			
			$wpdb->show_errors(true);
			if ($style == 1)
			$outp ='<select name="id_entry">';
			else $outp ='<table id="wttrm">';
			$sql= "select * from ".wttrm_ENTRYTABLE;
			$rows = $wpdb->get_results($sql);
			foreach($rows as $row)
			{
				if ($style == 1)
				$outp.= '<option value="'.$row->id.'">'.$row->entryname.'</option>';
				else {
					//nonces construct for _GET
					$urldel = str_replace( '%7E', '~', $_SERVER['PHP_SELF']).'?page=mng_entry&pid='.$row->id.'&act=delentry';
					$urledt = str_replace( '%7E', '~', $_SERVER['PHP_SELF']).'?page=mng_entry&pid='.$row->id.'&act=edtfrm';
					$linkdel = wp_nonce_url(  $urldel, $delaction );
					$linkedt = wp_nonce_url(  $urledt, $edtfrmaction );
					
					$outp.= '<tr><td><a href="'.$linkedt.'" title="Edit an entry">';
					$outp .= '<img src="'.wttrm_DIR_URL.'/img/pencil.png" alt="edit a time table"/></a>&nbsp;&nbsp;';
					$outp .= '<a href="'.$linkdel.'" title="delete an entry">';
					$outp .= '<img src="'.wttrm_DIR_URL.'/img/cross.png" alt="delete a time table"/></a></td>';
					$outp .= '<td>'.$row->id.'</td><td> '.html_entity_decode($row->entryname).'</td></tr>';
				}
			}
			if ($style == 1)
			$outp .= '</select>';
			else $outp .= '</table>';
			return $outp;
	}



	/*
	** Display the timeTable list in two flavours. 0: no edition, 1: edition
	*/
	function wttrmdsp($edition)
	{		 
			global $wpdb;
			//nonces construct
			$delaction = "del_wttrm";
			$edtfrmaction ="edt_frm";
			
							   		
			$wpdb->show_errors(true);
			$sql = "select h.id, e.entryname, mon, tue, wed, thu, fri, sat, sun from ".wttrm_TIMETABLE." h, ".wttrm_ENTRYTABLE." e where h.id_entry = e.id";
			$outp='<table id="wttrm">';
			$oupt.='<tr>';
			if ($edition==1) $outp .= '<th></th>'; 
			$outp.='<th>'.__('Id. Entry','wttrmPlugIn').'</th><th>'.__('mon','wttrmPlugIn').'</th><th>'.__('tue','wttrmPlugIn').'</th><th>'.__('wed','wttrmPlugIn').'</th><th>'.__('thu','wttrmPlugIn').'</th><th>'.__('fri','wttrmPlugIn').'</th><th>'.__('sat','wttrmPlugIn').'</th><th>'.__('sun','wttrmPlugIn').'</th></tr>';
	
			$rows = $wpdb->get_results($sql);
			foreach($rows as $row)
			{
				//nonces construct
				$urldel = str_replace( '%7E', '~', $_SERVER['PHP_SELF']).'?page=mng_wttrm&pid='.$row->id.'&act=delwttrm';
				$urledt = str_replace( '%7E', '~', $_SERVER['PHP_SELF']).'?page=mng_wttrm&pid='.$row->id.'&act=edtfrm';
				$linkdel = wp_nonce_url(  $urldel, $delaction );
				$linkedt = wp_nonce_url(  $urledt, $edtfrmaction );
				
				$outp.= "<tr>";
				if ($edition==1) { 
					$outp.= '<td><a href="'.$linkedt.'" title="'.__('Edit a Time Table','wttrmPlugIn').'">';
					$outp .= '<img src="'.wttrm_DIR_URL.'/img/pencil.png" alt="'.__('edit a time table','wttrmPlugIn').'"/></a>&nbsp;&nbsp;';
					$outp .= '<a href="'.$linkdel.'" title="'.__('delete a time table','wttrmPlugIn').'">';
					$outp .= '<img src="'.wttrm_DIR_URL.'/img/cross.png" alt="'.__('delete a time table','wttrmPlugIn').'"/></a></td>';
				}
				$outp.= '<td><div class="wttrmentry">'.$row->id.'. '.html_entity_decode($row->entryname).'</div></td><td>'.html_entity_decode($row->mon).'</td><td>'.html_entity_decode($row->tue).'</td><td>'.html_entity_decode($row->wed).'</td><td>'.html_entity_decode($row->thu).'</td><td>'.html_entity_decode($row->fri).'</td><td>'.html_entity_decode($row->sat).'</td><td>'.html_entity_decode($row->sun).'</td></tr>';
			}  
			$outp.='</table><hr/>';
	
			return $outp;																	
	
	}

	/*
	** Table header
	*/
	function tablehead($plusentry = 0)
	{
			$outp = '<table  id="wttrm"><tr>';
			if ($plusentry == 1)
			$outp .= '<th></th>';
			$outp .='<th>'.__('mon','wttrmPlugIn').'</th><th>'.__('tue','wttrmPlugIn').'</th><th>'.__('wed','wttrmPlugIn').'</th><th>'.__('thu','wttrmPlugIn').'</th><th>'.__('fri','wttrmPlugIn').'</th><th>'.__('sat','wttrmPlugIn').'</th><th>'.__('sun','wttrmPlugIn').'</th></tr>';
			return $outp;
	}


	/*
	 * Manage entries (CRUD)
	 */
	function mng_entry() {
			global $wpdb;
			$wpdb->show_errors(true);
			if (isset($_POST['act']) && ($_POST['act'] == "addentry"))
			{
				$sql = $wpdb->prepare("insert into ".wttrm_ENTRYTABLE." (entryname) values(%s)",htmlentities($_POST['entryname'], ENT_QUOTES));
				$wpdb->get_results($sql);
			}
			
			if (isset($_GET['act']) && ($_GET['act'] == "delentry"))
			{
				check_admin_referer('del_entry');
				//deletes the entry
				$sql=$wpdb->prepare("delete from ".wttrm_ENTRYTABLE." where id=%d",$_GET['pid']);
				$wpdb->query($sql);
				//deletes the assiciated time table
				$sql=$wpdb->prepare("delete from ".wttrm_TIMETABLE." where id_entry = %d",$_GET['pid']);
				$wpdb->query($sql);	
			}
	
			if (isset($_POST['act']) && ($_POST['act'] == "edtentry"))
			{
				$sql= $wpdb->prepare("UPDATE ".wttrm_ENTRYTABLE." set entryname=%s where id= %d",htmlentities($_POST['entryname'], ENT_QUOTES),$_POST['pid']);
				$wpdb->query($sql);
	
			}
	
			  echo '<div class="wrap">';
			  echo '<h1>'.__('Manage entries for wttrm','wttrmPlugIn').'</h1>
			  <p>'.__('Existing Time Tables','wttrmPlugIn').'</p>';
			  echo wttrmdsp(0,0);
			  echo '<p>'.__('Existing Entries','wttrmPlugIn').'</p>';
			  echo list_wttrmentries(2);
			  if (isset($_GET['pid'])&& ($_GET['act']=="edtfrm" && !(isset($_POST['act'])))) {
			  	  check_admin_referer('edt_frm');//nonces check for GET
			  	  $sql = "select e.entryname from ".wttrm_ENTRYTABLE." e where e.id=".$_GET['pid']; 
				  $rows = $wpdb->get_row($sql);
				  $outp = '<br/><br/><img src="'.wttrm_DIR_URL.'/img/pencil.png" alt="edit an entry"/>'.__('Modify this entry','wttrmPlugIn').'<form method="post" action="">';
				  $outp .= '<table>
				  <tr>
				  <td>'.__('Entry','wttrmPlugIn').'</td><td><input type="text" name="entryname" size="50" value="'.html_entity_decode($rows->entryname).'"/></td>
				  </tr>	
				  </table>
				  <input type="hidden" name="pid" value="'.$_GET['pid'].'">
				  <input type="hidden" name="act" value="edtentry">
				  <input type="submit" value="Modify" />';
				  $outp.= '</form>';
				  echo $outp;
			    }
			  echo '<hr/>';
			  echo '<p><img src="'.wttrm_DIR_URL.'/img/add.png" alt="Create an entry"/>'.__('Create an entry.','wttrmPlugIn').'</p>';
			  echo '<form action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="POST"><br/>';
			  echo '<tr>
				  	<td>'.__('Entry','wttrmPlugIn').'</td><td><input type="text" name="entryname" size="50" value=""/></td>
					</tr>				  	
					</table>
					<input type="hidden" name="act" value="addentry">
					<input type="submit" value="Submit" />
					</form>';
			  echo '</div>';
	}


	/*
	** Manage time tables (CRUD)
	*/
	function mng_wttrm() {
		global $wpdb;
		$wpdb->show_errors(true);
	
			if (isset($_GET['act']) && ($_GET['act'] == "delwttrm"))
			{
				check_admin_referer('del_wttrm');
				$sql=$wpdb->prepare("delete from ".wttrm_TIMETABLE." where id=%d",$_GET['pid']);
				$wpdb->query($sql);
	
			}
	
			if (isset($_POST['act']) && ($_POST['act'] == "edtwttrm"))
			{
				$sql= $wpdb->prepare("UPDATE ".wttrm_TIMETABLE." set mon=%s ,tue=%s , wed=%s ,thu=%s ,fri=%s ,sat=%s ,sun=%s where id=%d",htmlentities($_POST['mon'], ENT_QUOTES), htmlentities($_POST['tue'], ENT_QUOTES), htmlentities($_POST['wed'], ENT_QUOTES), htmlentities($_POST['thu'], ENT_QUOTES), htmlentities($_POST['fri'], ENT_QUOTES),htmlentities($_POST['sat'], ENT_QUOTES),htmlentities($_POST['sun'], ENT_QUOTES),$_POST['pid']);
				$wpdb->query($sql);
	
			}
			
			if (isset($_POST['act']) && ($_POST['act'] == "addwttrm"))
			{
				$sql = $wpdb->prepare( "INSERT INTO ".wttrm_TIMETABLE."( id_entry,mon, tue, wed, thu, fri, sat, sun ) VALUES ( %d, %s, %s, %s, %s, %s, %s, %s )", htmlentities($_POST['id_entry'], ENT_QUOTES), htmlentities($_POST['mon'], ENT_QUOTES), htmlentities($_POST['tue'], ENT_QUOTES), htmlentities($_POST['wed'], ENT_QUOTES), htmlentities($_POST['thu'], ENT_QUOTES), htmlentities($_POST['fri'], ENT_QUOTES),htmlentities($_POST['sat'], ENT_QUOTES),htmlentities($_POST['sun'], ENT_QUOTES) );
				$wpdb->get_results($sql);
			}
	
			  echo '<div class="wrap">';
			  echo '<h1>'.__('Manage Weekly Time Table','wttrmPlugIn').'</h1>';
			  echo '<p>'.__('Choose time table to edit','wttrmPlugIn').'</p>';
			  echo wttrmdsp(1);
			  // edit form
			  if (isset($_GET['pid']) && ($_GET['act']=="edtfrm") && (!isset($_POST['act']))) {
			  	check_admin_referer('edt_frm');
				  $sql = "select h.id, e.entryname as entryn, mon, tue, wed, thu, fri, sat, sun from ".wttrm_TIMETABLE." h, ".wttrm_ENTRYTABLE." e where h.id_entry = e.id and h.id=".$_GET['pid']; 
				  $rows = $wpdb->get_row($sql);
				  $outp = '<br/><br/><img src="'.wttrm_DIR_URL.'/img/pencil.png" alt="edit a wttrm"/>'.__('Modify this time table','wttrmPlugIn').'<form method="post" action="">';
				  $outp .= '<strong>'.__('Entry','wttrmPlugIn').' : </strong>'.html_entity_decode($rows->entryn).'<br/><br/>';
				  $outp .= tablehead();
				  $outp .= '<tr><td>ex.15h-17h</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
				  <tr>
				  <td><input type="text" name="mon" size="10" value="'.html_entity_decode($rows->mon).'"/></td>
				  <td><input type="text" name="tue" size="10" value="'.html_entity_decode($rows->tue).'"/></td>
				  <td><input type="text" name="wed" size="10" value="'.html_entity_decode($rows->wed).'"/></td>
				  <td><input type="text" name="thu" size="10" value="'.html_entity_decode($rows->thu).'"/></td>
				  <td><input type="text" name="fri" size="10" value="'.html_entity_decode($rows->fri).'"/></td>
				  <td><input type="text" name="sat" size="10" value="'.html_entity_decode($rows->sat).'"/></td>
				  <td><input type="text" name="sun" size="10" value="'.html_entity_decode($rows->sun).'"/></td>
				  </tr>	
				  </table>
				  <input type="hidden" name="pid" value="'.$_GET['pid'].'">
				  <input type="hidden" name="act" value="edtwttrm">
				  <input type="submit" value="Modify" />';
				  
				  $outp.= '</form><hr/>';
				  echo $outp;
			    }
			  // add form  
			  echo '<p><img src="'.wttrm_DIR_URL.'/img/add.png" alt="Create a time table"/>'.__('Create a time table.','wttrmPlugIn').'</p>';
			  echo '<form action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="POST">
			  <br/>';
			  echo '<b>Entry</b>'.list_wttrmentries(1).'</br>';
			  echo tablehead();
			  echo '<tr><td>ex.15h-17h</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			  <tr>
			  
			  <td><input type="text" name="mon" size="10" /></td>
			  <td><input type="text" name="tue" size="10" /></td>
			  <td><input type="text" name="wed" size="10" /></td>
			  <td><input type="text" name="thu" size="10" /></td>
			  <td><input type="text" name="fri" size="10" /></td>
			  <td><input type="text" name="sat" size="10" /></td>
			  <td><input type="text" name="sun" size="10" /></td>
			  </tr>	
			  </table>
			  <input type="hidden" name="act" value="addwttrm">
			  <input type="submit" value="Submit" />
			  </form>';  
			  echo '</div>';
	}


	/*
	 * Create the function for the shortcode
	 */
	function shc_wttrmdsp($atts)
	  	{ 
		   global $wpdb;
		  
		   $wpdb->show_errors(true);
		   extract(shortcode_atts(array(
			'entry_id' => 'entry_id',
		   ), $atts));

                   $outp =  tablehead(1);
                   $sql="select * from ".wttrm_TIMETABLE." h, ".wttrm_ENTRYTABLE." e where h.id_entry = e.id  and h.id IN (".$entry_id.")";
                   $rows = $wpdb->get_results($sql);
		   
                        foreach($rows as $row)
                            {
                                     $outp .= '<tr><td><span id="wttrmentry">'.html_entity_decode($row->entryname).'</span></td><td>'.html_entity_decode($row->mon).'</td><td>'.html_entity_decode($row->tue).'</td><td>'.html_entity_decode($row->wed).'</td><td>'.html_entity_decode($row->thu).'</td><td>'.html_entity_decode($row->fri).'</td><td>'.html_entity_decode($row->sat).'</td><td>'.html_entity_decode($row->sun).'</td></tr>';
                            }

                    $outp .= '</table>';
                    return $outp;
	    
		}


	/*
	 * Uninstall procedures
	 */
	function wttrm_uninstall()
		{	global $wpdb;
	    	delete_option('wttrm_db_version');
	    	//dropping the tables
	    	$sql = "drop table ".wttrm_TIMETABLE;
	    	$wpdb->query($sql);
	    	$sql = "drop table ".wttrm_ENTRYTABLE;
	    	$wpdb->query($sql);
		}
	register_uninstall_hook(__FILE__, 'wttrm_uninstall');

/*
 * USE WORDPRESS LANGUAGE
 */
// Add this action into the admin header of Wordpress
wp_enqueue_style('wttrmPlugIn', wttrm_DIR.'/css/wttrm.css');
// Call the menu creation function
add_action('admin_menu','wttrm_menu');
// Add the shortcode to display the table
add_shortcode('wttrmdsp', 'shc_wttrmdsp');

?>