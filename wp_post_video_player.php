<?php
/*

Plugin Name: Wp Post Video Player

Plugin URI: http://sabdsoft.com/

Description: This wordpress plugin displays Video File on Front End

Version: 1.1

License: GPL

Author: Hiren Patel

*/
// Define Post Details Paths and Directories
define("VIDEO_PLAYER_FILENAME", basename(__FILE__));

$path = $_SERVER['REQUEST_URI'];

$path_length = strpos($path, VIDEO_PLAYER_FILENAME) + strlen(VIDEO_PLAYER_FILENAME);
	
$path = substr($path, 0, strpos($path, '?')) . '?page=' . VIDEO_PLAYER_FILENAME;

define("VP_ADMIN_PLUGIN_PATH", $path);

	
if ($IS_WINDOWS) {
	$temp = str_replace(VIDEO_PLAYER_FILENAME, "", __FILE__);
	$temp = str_replace("\\", "/", $temp);	//switch direction of slashes
	define("VIDEO_PLAYER_PLUGIN_PATH", $temp);
} else {
	define("VIDEO_PLAYER_PLUGIN_PATH", str_replace(VIDEO_PLAYER_FILENAME, "", __FILE__));
}


if ( ! defined( 'WP_CONTENT_URL' ) )
	  define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
	  define( 'WP_CONTENT_DIR', ABSOLUTE_PATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
	  define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
	  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	  
// Determine whether we're in HTTPS mode or not, and change URL's accordingly.
if(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] == 'on') 
{
	define('VIDEO_PLAYER_SITE_URL', str_replace('http://', 'https://', get_bloginfo('url')));
	define('VIDEO_PLAYER_BLOG_URL', str_replace('http://', 'https://', get_bloginfo('wpurl')));
}
else 
{
	define('VIDEO_PLAYER_SITE_URL', get_bloginfo('url'));
	define('VIDEO_PLAYER_BLOG_URL', get_bloginfo('wpurl'));
}
define("VIDEO_PLAYER_PLUGIN_URL", WP_PLUGIN_URL."/wp-post-video-player/");	

define("UPLOAD_PATH",WP_CONTENT_DIR.'/uploads/wp_video_file');

define("UPLOAD_URL",WP_CONTENT_URL.'/uploads/wp_video_file');

if (!class_exists('Wp_Video_Player')) {



	class Wp_Video_Player {

	

		function Wp_Video_Player() {			

			$this->addActions();

			register_activation_hook(__FILE__, array($this, 'createVideoPlayerPlugin'));

			register_deactivation_hook(__FILE__, array($this, 'removeVideoPlayerPlugin'));

		}

		

		function addActions() {

			add_action('admin_menu', array(&$this, 'addAdminInterfaceItems'));
			add_action('admin_menu', array(&$this,'wp_post_video_player_add_custom_box'));
			add_action( 'save_post', array(&$this,'wp_post_video_player_save_options'));
			add_action( 'edit_post', array(&$this,'wp_post_video_player_save_options'));
			add_action( 'publish_post',array(&$this, 'wp_post_video_player_save_options'));
			add_action( 'delete_post', array(&$this,'wp_post_video_player_delete_options'));
			add_shortcode('video-player',array(&$this,'wp_post_video_player_display'));
			add_filter('admin_head', array(&$this,'wp_post_video_player_admin_js'));

		}

		//If you want to insert table or query Put in that function
		//this will insert while installatoin time

		function createVideoPlayerPlugin() 

		{

			global $wpdb;
			
			global $jal_db_version;
			
			//create main table
			$table_name_main = $wpdb->prefix . "video_file";
			
			if($wpdb->get_var("show tables like '$table_name_main'") != $table_name_main) 
			{
				
				$sql="CREATE TABLE " . $table_name_main . " (
				id int(11) NOT NULL auto_increment,
				title varchar(255) NOT NULL,
				video_name varchar(255) NOT NULL,
				published tinyint(4) NOT NULL default '0',
				PRIMARY KEY ( `id` )
				);";
				
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				
				dbDelta($sql);
				
			}
			
			//create reference table
			$table_name_ref = $wpdb->prefix . "video_file_pages";
			
			if($wpdb->get_var("show tables like '$table_name_ref'") != $table_name_ref) 
			{
			
				$sql = "CREATE TABLE " . $table_name_ref . " (
				id bigint(20) NOT NULL auto_increment,
				post_id bigint(20) NOT NULL default '0',
				post_video_player_id bigint(20) NOT NULL default '0',
				UNIQUE KEY id (id)
				);";
				
				dbDelta($sql);
				
			}
			
			add_option("jal_db_version", $jal_db_version);
			$newoptions = get_option('wp_post_video_player');
			$newoptions['record_per_page'] = 5;
			$newoptions['btncolor'] = '333333';
			$newoptions['accentcolor'] = '3f3f3f';
			$newoptions['txtcolor'] = 'dddddd';
			$newoptions['volume'] = '30';
			$newoptions['autoload'] = '0';
			$newoptions['autoplay'] = '0';
			$newoptions['ffmpeg_path'] = 'ffmpeg';
			add_option('wp_post_video_player', $newoptions);
			
			if (!is_dir(WP_CONTENT_DIR.'/uploads/wp_video_file'))
			{ 
				mkdir(WP_CONTENT_DIR.'/uploads/wp_video_file');
			}

		}

		//If you want to insert table or query Put in that function
		//this will insert while installatoin time

		function removeVideoPlayerPlugin() 

		{

			global $wpdb;	//required global declaration of WP variable
			//delete main table
			$table_name_main = $wpdb->prefix . "video_file";
			
			$sql = "DROP TABLE ". $table_name_main;
			
			$wpdb->query($sql);
			
			$table_name_main = $wpdb->prefix . "video_file_pages";
			
			$sql = "DROP TABLE ". $table_name_main;
			
			$wpdb->query($sql);
			
			$this->recursiveMusicFileDelete(WP_CONTENT_DIR.'/uploads/wp_video_file');
			
		}

		//recursiv File Delete
		
		function recursiveMusicFileDelete($str)
		
		{
			
			if(is_file($str)){
				
				return @unlink($str);
				
			}
			elseif(is_dir($str))
			{
				
				$scan = glob(rtrim($str,'/').'/*');
				foreach($scan as $index=>$path){
					$this->recursiveMusicFileDelete($path);
				}
				
				return @rmdir($str);
			}
				
		}
		

		function addAdminInterfaceItems() 
		
		{

			$icon_path = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/icon';
			
			
			add_menu_page(__('Wp Post Video Player'), __('Wp Post Video Player'), 'manage_options',VIDEO_PLAYER_FILENAME, null,$icon_path.'/generic.png');

			add_submenu_page('wp_video_player',__('Data Set'),__('Data Set'),'manage_options',VIDEO_PLAYER_FILENAME, array(&$this,'wp_post_video_player_options_page'));

		}
		
		
		//display admin navigation
		function wp_video_player_admin_nav()
		
		{
			
			$wp_video_player_admin_nav_options = array();
			$wp_video_player_admin_nav_options['new'] = __("Add New", 'wp_video_player');
			$wp_video_player_admin_nav_options['view'] = __("View All", 'wp_video_player');
			$wp_video_player_admin_nav_options['setting'] = __("Setting", 'wp_video_player');
			?>
		
		<div class="formbuilder-subnav">
          <ul class="subsubsub">
            <?php
            $i=1;
            foreach( $wp_video_player_admin_nav_options as $key=>$value ) { ?>
            <li><a  href="<?php echo VP_ADMIN_PLUGIN_PATH; ?>&action=<?php echo $key; ?>"><?php echo $value; ?></a>
              <? if ($i!=count($wp_video_player_admin_nav_options))
                { ?>
              |
              <?
                } 
                $i++;
             ?>
            </li>
            <?php } ?>
          </ul>
        </div>
		<?php
		
		}

	

		function wp_post_video_player_options_page($action="")
		{
			
			global $wpdb;
			?>
		
            <div id="icon-tools" class="icon32"><br>
            </div>
            <div class="wrap">
              <h2>
                <?php _e('Video File Management', 'wp_post_video_player'); ?>
              </h2>
              <?php
                if(!isset($_GET['action'])) $_GET['action'] = false;
                switch($_GET['action']) {
                    case "new":
					
                        $this->wp_video_player_new();
						
                    break;
					
                    case "edit":
					
                        $this->wp_video_player_edit($_GET['editid']);
						
                    break;
					
                    case "setting":
					
                        $this->wp_video_player_setting();
						
                    break;
					
                    case "view":
                    default:
					
                        $this->wp_video_player_list_page();
						
                    break;
            
                }
                ?>
		</div>
		<?php

		}
		
		function wp_video_player_new() 
		{ 
		
			global $wpdb;
			
			//add edit record
			if(isset($_POST['Submit']))
			{
				// A form was added to the post.  Go ahead and add or modify it in the db.
				$video['title'] = addslashes($_POST['title']);
				$video['video_name'] = addslashes($_FILES['videofile']['name']);
				
				$options= get_option('wp_post_video_player');
				
				$error = "";
				if($_POST['title'] == "")
					$error .= "Title Cannot be blank<br>";
				
						
				if($_FILES['videofile']['name'] == '')
					$error .= "Upload a File<br>";
				
				if($_FILES['videofile']['name'] != '')
				{
				  $extArray = explode('.',$_FILES['videofile']['name']);
				  $ext=$extArray[count($extArray)-1];
				  if(!in_array(strtolower($ext),array('mpeg','wmv','mpg','mp4')))
				  {
					$error .=  "Not valid file(File Supported mpeg,wmv,mpg,'mp4')<br>";
				  }
				}
				if($error != "")
				{
					$_SESSION['message'] = $error;
				}	
				else
				{			
					$wpdb->insert($wpdb->prefix."video_file", $video);
					$id=$wpdb->insert_id;
					
					$filename=$id.".".$ext;
					$target_path = UPLOAD_PATH."/".$filename;
					move_uploaded_file($_FILES['videofile']["tmp_name"], $target_path);
					
					$cmd=$options['ffmpeg_path']." -i ".$target_path."  -ar 22050  ".UPLOAD_PATH."/".$id.".flv";
					$cmd=str_replace("\\","/",$cmd);
					//echo $cmd;die;			
					$output = exec(escapeshellcmd($cmd));
					unlink($target_path);
					
					$_SESSION['message'] = "Record Inserted Successfuly";
					?>
					<script type="text/javascript">
					<!--
					window.location = "<?=VP_ADMIN_PLUGIN_PATH?>&action=edit&editid=<?=$id?>&msg=2"
					//-->
					</script>
					<?php
				}
			}	
		$this->wp_video_player_admin_nav(); ?>
		<div class="wrap">
		  <h2>
			<?php _e('Add Video'); ?>
		  </h2>
		  <div class="narrow">
		  <?php if($_SESSION['message']){ ?>
		  <div class="updated"><p><strong><?php echo $_SESSION['message'];?></strong></p></div>
		  <? } ?>
			 <form name="form1" method="post" action="" enctype="multipart/form-data">
			  <table  width="400"  cellpadding="0"  cellspacing="0" border="0">
				<tr>
				  <td><table id="table" border="1" width="100%">
					  <tbody>
						<tr>
						  <td>Title</td>
						  <td><input type="text" name="title" id="title"  value="<?php echo $video['title']?>"/></td>
						</tr>
						<tr>
						  <td>Upload File</td>
						  <td><input type="file" name="videofile" id="videofile"  value=""/></td>
						</tr>
						
						
					  </tbody>
					</table></td>
				</tr>
				<tr>
				  <td> <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></td>
				</tr>
			  </table>
			</form>
		  </div>
		</div>
		<?
		
		}
		
		//edit details
		function wp_video_player_edit($id) 
		{ 
			global $wpdb,$_SESSION;
		
			$video_details = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."video_file where id=".$id) );
			
			//add edit record
			if(isset($_POST['Submit']))
			{
				$_SESSION['message'] ="";
		
				$sql = "SELECT * FROM ".$wpdb->prefix."video_file WHERE id= '" . $id . "';";
				
				$video = $wpdb->get_row($sql, ARRAY_A);
		
				// A form was added to the post.  Go ahead and add or modify it in the db.
				$video['title'] = addslashes($_POST['title']);
				$video['video_name'] = (isset($_FILES['videofile']['name']) && $_FILES['videofile']['name']!='')?$_FILES['videofile']['name']:$video_details->video_name;
				
				$options= get_option('wp_post_video_player');	
				
				$error = "";
				if($_POST['title'] == "")
					$error .= "Title Cannot be blank<br>";
				
					 
				if($_FILES['videofile']['name'] != '')
				{
				  $extArray = explode('.',$_FILES['videofile']['name']);
				  $ext=$extArray[count($extArray)-1];
				  if(!in_array(strtolower($ext),array('mpeg','wmv','mpg','mp4')))
				  {
					$error .=  "Not valid file(File Supported mpeg,wmv,mpg,'mp4')<br>";
				  }
				}
				if($error != "")
				{
				   $_SESSION['message'] = $error;
				}	
				else
				{		
					if($_FILES['videofile']['name'] != '')
					{
					   $filename=$id.".".$ext;
					   $target_path = UPLOAD_PATH."/".$filename;
					   move_uploaded_file($_FILES['videofile']["tmp_name"], $target_path);
					   
						$cmd=$options['ffmpeg_path']." -i ".$target_path."  -ar 22050  ".UPLOAD_PATH."/".$id.".flv";
						$cmd=str_replace("\\","/",$cmd);
									
						$output = exec(escapeshellcmd($cmd));
						unlink($target_path);
					}
		
					$wpdb->update($wpdb->prefix."video_file",$video,array('id'=>$video['id']));
					$_SESSION['message'] = "Record Updated Successfuly";
				}
			}
			
			$this->wp_video_player_admin_nav(); ?>
		<div class="wrap">
		  <h2>
			<?php _e('Add Video'); ?>
		  </h2>
		  <div class="narrow">
		<?php if($_SESSION['message']){ ?>
		  <div class="updated"><p><strong><?php echo $_SESSION['message'];?></strong></p></div>
		  <? } ?>
			<form name="form1" method="post" action="<?php echo VP_ADMIN_PLUGIN_PATH; ?>&action=edit&editid=<?php echo $id; ?>" enctype="multipart/form-data">
			  <table  width="400"  cellpadding="0"  cellspacing="0" border="0">
				<tr>
				  <td><table id="table" border="1" width="100%">
					  <tbody>
						<tr>
						  <td>Title</td>
						  <td><input type="text" name="title" id="title"  value="<?php echo $video_details->title?>"/></td>
						</tr>
						<tr>
						  <td>Upload File</td>
						  <td><input type="file" name="videofile" id="videofile"  value=""/><?php echo $video_details->video_name ?></td>
						</tr>
					  </tbody>
					</table></td>
				</tr>
				<tr>
				  <td><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></td>
				</tr>
			  </table>
			</form>
		  </div>
		</div>
		<?
		}
		
		//Display js files

		function wp_post_video_player_admin_js() 
		{
			
		// Display the admin related Js
		?>
        
		<script type='text/javascript' src='<?php echo VIDEO_PLAYER_PLUGIN_URL; ?>wp_video_player.js'></script>
		<link rel="stylesheet" href="<?php echo VIDEO_PLAYER_PLUGIN_URL; ?>pagination.css" />
		<?
		
		}
		
		
		//Display list page
		function wp_video_player_list_page()
		{ 
			global $wpdb;
			
			include_once(VIDEO_PLAYER_PLUGIN_PATH ."pagination.class.php");
			
			if($_GET['action']=='deleteAll')
			{
				
				for($i=0;$i<count($_POST['delId']);$i++)
				{
					
					$id=$_POST['delId'][$i];
					//delete file
					$ext = "flv";
					$filename=$id.".".$ext;
					@unlink(UPLOAD_PATH."/".$filename);	
					$wpdb->query("DELETE FROM ".$wpdb->prefix."video_file WHERE id = ".$id); 
					$wpdb->query("DELETE FROM ".$wpdb->prefix."video_file_pages WHERE post_video_player_id  = ".$id);
					
				}
				
				$_SESSION['message'] = "Records deleted Successfuly";
			}
			
			if($_GET['action']=='delete')
			{
				
				$id=$_GET['delid'];
				//delete file
				$ext = "flv";
				$filename=$id.".".$ext;
				
				@unlink(UPLOAD_PATH."/".$filename);	
				
				$wpdb->query("DELETE FROM ".$wpdb->prefix."video_file WHERE id = ".$id);  
				$wpdb->query("DELETE FROM ".$wpdb->prefix."video_file_pages WHERE post_video_player_id  = ".$id);
				
				$_SESSION['message'] = "Record delete Successfuly";
				
			}
				
			$options= get_option('wp_post_video_player');
			
			//paging logic
			$sql="SELECT * FROM ".$wpdb->prefix."video_file";
			$items = mysql_num_rows(mysql_query($sql));
		
			if($items > 0)
			{
				$p = new pagination;
				$p->items($items);
				$p->limit($options['record_per_page']);
				$p->target("?page=wp_video_player.php");
				$p->parameterName("pg_no");
				
				if(!isset($_GET['pg_no'])) 
				{
					$p->page = 1;
				} 
				else
				{
					$p->page = $_GET['pg_no'];
				}
		 
				//Query for limit paging
				$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;		
			}
			else
			{
				
			}	
			$videos= $wpdb->get_results( $wpdb->prepare($sql." ".$limit) );
			$this->wp_video_player_admin_nav();
			?>
		<div class="wrap">
		  <h2>
			<?php _e('Video List'); ?>
		  </h2>
		  <? if($msg){ ?>
		   <div class="updated"><p><strong><?php echo $msg?></strong></p></div>
		  <? } ?> 
		  <form method="post" action="" id="video_list" name="video_list">
			<div class="tablenav">
			  <div class="alignleft actions">
				<input type="button" onclick="javascript:delete_videos();" value="Delete Videos" class="input-button">       
			  </div>      
			  <div class="clear"></div>
			</div>
			<div class="clear"></div>
			<table>
				<tr>
					<td><table cellspacing="0" class="widefat post fixed">
					  <thead>
						<tr>
						  <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox" onclick="check_all()" value="" name="post_details"></th>
						  <th width="5%">Id</th>
						  <th width="15%">Title</th>
						  <th>Video Name</th>
						  <th width="10%">Action</th>
						</tr>
					  </thead>
					  <tfoot>
						<tr>
						  <th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox" onclick="check_all()" value="" name="post_details"></th>
						  <th>Id</th>
						  <th>Title</th>
						  <th>Video Name</th>
						  <th>Action</th>
						</tr>
					  </tfoot>
					  <tbody>
					 <?php
					 if(!empty($videos))
					 {
						$x=0;
						foreach($videos as $video) { 
						$x++;
						  $editLink="options-general.php?page=".VIDEO_PLAYER_FILENAME."&action=edit&editid=".$video->id;
						  $deleteLink="options-general.php?page=".VIDEO_PLAYER_FILENAME."&action=delete&delid=".$video->id;
			
						  ?>
							<tr valign="top" class="alternate author-self status-publish iedit">
							  <th class="check-column" scope="row"><input type="checkbox" value="<?php echo $video->id?>" name="delId[]"></th>
							  <td><?php echo $x?></td>
							  <td><?php echo $video->title?></td>
							  <td><?php echo $video->video_name?></td>
							  <td><a href="<?php echo $editLink?>">Edit</a>/<a href="<?php echo $deleteLink?>">delete</a></td>
							</tr>
							
							<?php } 
						}
						else
						{?>
						
						<tr><td colspan="5" align="center">No Record Found</td></tr>	
					   <?php }  
					 if(count($videos)>=$options['record_per_page'])
					 {?>
					   <tr>
					<td colspan="5"><table class="widefat post fixed"><tr><td  align="center"><?php echo $p->show();?></td></tr></table></td>
				</tr>  
				<?php } ?>
							
					  </tbody>
					</table></td>
				</tr>
				
				
			</table>
		  </form>
		</div>
		<?
		}
		
		
		//Video Player Setting
		function wp_video_player_setting()
		{
			
			global $wpdb;
			
			$this->wp_video_player_admin_nav();
			
			$options= $newoptions  = get_option('wp_post_video_player');
			//print "<pre>";print_r($options);die;
			// if submitted, process results
			if ( $_POST["video_player_submit"] )
			{
				
				$newoptions['record_per_page'] = strip_tags(stripslashes($_POST["record_per_page"]));
				$newoptions['btncolor'] = strip_tags(stripslashes($_POST["btncolor"]));
				$newoptions['accentcolor'] = strip_tags(stripslashes($_POST["accentcolor"]));
				$newoptions['txtcolor'] = strip_tags(stripslashes($_POST["txtcolor"]));
				$newoptions['volume'] = strip_tags(stripslashes($_POST["volume"]));
				$newoptions['autoload'] = strip_tags(stripslashes($_POST["autoload"]));
				$newoptions['autoplay'] = strip_tags(stripslashes($_POST["autoplay"]));
				$newoptions['ffmpeg_path'] = strip_tags(stripslashes($_POST["ffmpeg_path"]));
				
			}
			
			
			// any changes? save!
			if ( $options != $newoptions ) 
			{
				
				$options = $newoptions;
				update_option('wp_post_video_player', $options);
				
			}
			// options form
			echo '<form method="post">';
			echo '<tr><td colspan="2"><h2>Display options</h2></td></tr>';
			echo '<table class="form-table">';
			// Display Record at Admin side
			echo '<tr valign="top"><th scope="row">Display Record Admin Side</th>';
			echo '<td><input type="text" name="record_per_page" value="'.$options['record_per_page'].'" size="8"></input></td></tr>';
			
			//Button color	
			echo '<tr valign="top"><th scope="row">Button Color</th>';
			echo '<td><input type="text" name="btncolor" value="'.$options['btncolor'].'" size="8"></input></td></tr>';
			
			//Text color	
			echo '<tr valign="top"><th scope="row">Text Color</th>';
			echo '<td><input type="text" name="txtcolor" value="'.$options['txtcolor'].'" size="8"></input></td></tr>';
			
			//Accent color
			echo '<tr valign="top"><th scope="row">Accent color</th>';
			echo '<td><input type="text" name="accentcolor" value="'.$options['accentcolor'].'" size="8"></input></td></tr>';
			
			//Volume color
			echo '<tr valign="top"><th scope="row">Volume</th>';
			echo '<td><input type="text" name="volume" value="'.$options['volume'].'" size="8"></input></td></tr>';
			
			//ffmpeg Path
			echo '<tr valign="top"><th scope="row">ffmpeg Path</th>';
			echo '<td><input type="text" name="ffmpeg_path" value="'.$options['ffmpeg_path'].'"></input></td></tr>';
			
			// Autoload
			echo '<tr valign="top"><th scope="row">Autoload</th>';
			echo '<td><input type="radio" name="autoload" value="0"';
			if( $options['autoload'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="autoload" value="1"';
			if( $options['autoload'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
			
			// Autoplay
			echo '<tr valign="top"><th scope="row">Autoplay</th>';
			echo '<td><input type="radio" name="autoplay" value="0"';
			if( $options['autoplay'] == 0 ){ echo ' checked="checked" '; }
			echo '></input> NO&nbsp;<input type="radio" name="autoplay" value="1"';
			if( $options['autoplay'] == 1){ echo ' checked="checked" '; }
			echo '></input> Yes</td></tr>';
			
			// close stuff
			echo '<input type="hidden" name="video_player_submit" value="true"></input>';
			echo '</table>';
			echo '<p class="submit"><input type="submit" value="Update Options &raquo;"></input></p>';
			echo "</div>";
			echo '</form>';
		
		}
		
		
		//display on post page
		
		function wp_post_video_player_add_custom_box()
		{
			
			  if( function_exists( 'add_meta_box' )) 
			  {
		
				add_meta_box( 'wp_post_video_player_sectionid', __( 'Video Player Title', 'wp_post_video_player_textdomain' , 'test'),array(&$this,'wp_post_video_player_post_options'), 'post', 'normal', 'high' );
		
				add_meta_box( 'wp_post_video_player_sectionid', __( 'Video Player Title', 'wp_post_video_player_textdomain' , 'test'),array(&$this,'wp_post_video_player_post_options'), 'page', 'normal', 'high' );
		
			   }
			   
		}
		
		//post option		
		function wp_post_video_player_post_options()
		{
			
			global $post, $wpdb;
		
			// Load the available forms
			$sql = "SELECT * FROM ".$wpdb->prefix."video_file ORDER BY title ASC";
			$videos = $wpdb->get_results($sql, ARRAY_A);
			
			
			// If the post already has an id, determine whether or not there is a form already linked to it.
			if($post->ID)
			{
				// Determine if the post/page has a linked form.
				$sql = "SELECT * FROM ".$wpdb->prefix."video_file_pages WHERE post_id = " . $post->ID;
				
				$pageDetails = $wpdb->get_row($sql, ARRAY_A);
				
			}
		
			echo "<div id='videoPlayerFormOptions'>\n" .
					"<p>" . __("If you wish to display a Tag that you have created using the Post Detail plugin, please select it from the following options.", 'wp_post_video_player') . "</p>\n" .
					"<select name='videoSelection'>\n" .
					"<option value=''>" . __("Select Title", 'wp_post_video_player') . "</option>\n";
			
			foreach($videos as $video)
			{
				
				$video_id = $video['id'];
				//echo $pageDetails['post_video_player_id'];die;
				if($video_id == $pageDetails['post_video_player_id']) 
				{
					$selected = "selected=\"selected\"";
					$post_data = $video;
				}
				else 
					$selected = "";
		
				echo "<option value='$video_id' $selected>" . $video['title'] . "</option>\n";
				
			}
			echo "</select>";
			
			echo "</div>\n";
			
		
		}

		
		//save option on post page
		function wp_post_video_player_save_options($id)
		{
			
			global $wpdb;
			if(isset($_POST['videoSelection']))
			{
				
				$id = $_POST['post_ID'];
				$sql = "SELECT * FROM ".$wpdb->prefix."video_file_pages WHERE post_id = " . $id;
				
				
				$pageDetails= $wpdb->get_row($sql);
				
				$page = intval($pageDetails->id);
				
		
				// Determine if the selected PostDetail ID is the same as the old PostDetail ID.
				if($_POST['videoSelection'] != $pageDetails->post_video_player_id )
				{
					
					// A form was added to the post.  Go ahead and add or modify it in the db.
					$postDetails['post_id'] = addslashes($id);
					$postDetails['post_video_player_id'] = addslashes($_POST['videoSelection']);
					
					if($page==0)
					{
						//print_r($postDetails);die;
						$wpdb->insert($wpdb->prefix."video_file_pages", $postDetails);
					}
					else
					{
						$wpdb->update($wpdb->prefix."video_file_pages", $postDetails,array('id'=>$pageDetails->id));
					}
					
				}
				
			}
			
		}
		
		
		//delete option while delete file
		function wp_post_video_player_delete_options()
		{
			
			global $wpdb;
			$pid=$_GET['post'];
			if ($wpdb->get_var($wpdb->prepare("SELECT post_id FROM ".$wpdb->prefix."video_file_pages WHERE post_id = %d", $pid)))
			{
				
				return $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."video_file_pages WHERE post_id = %d", $pid));
				
			}
			
			return true;
		}
	
		// template function
		function wp_post_video_player_display($content = '') 
		{
			
			global $post, $_SERVER, $wpdb;
			$id = $post->ID;
			
			$file = $wpdb->get_row( $wpdb->prepare("SELECT vp.post_id, vps.*
		FROM ".$wpdb->prefix."video_file_pages vp INNER JOIN ".$wpdb->prefix."video_file vps ON vp.post_video_player_id  = vps.id WHERE vp.post_id =".$id) );
			if(!empty($file))
			{
		
				$title=$file->title;
				$filenamestr.=UPLOAD_URL."/".$file->id.".flv";
				$options= $newoptions  = get_option('wp_post_video_player');
				
				?>
                
				<script src='<?php echo VIDEO_PLAYER_PLUGIN_URL; ?>assets/AC_RunActiveContent.js' language='javascript'></script>
				<!-- saved from url=(0013)about:internet -->
				<script language='javascript'>
				AC_FL_RunContent('codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0', 'width', '400', 'height', '325', 'src', ((!DetectFlashVer(9, 0, 0) && DetectFlashVer(8, 0, 0)) ? 'OSplayer' : 'OSplayer'), 'pluginspage', 'http://www.macromedia.com/go/getflashplayer', 'id', 'flvPlayer', 'allowFullScreen', 'true', 'allowScriptAccess', 'always', 'movie', ((!DetectFlashVer(9, 0, 0) && DetectFlashVer(8, 0, 0)) ? 'wp-content/plugins/wp-post-video-player/assets/OSplayer' : 'wp-content/plugins/wp-post-video-player/assets/OSplayer'), 'FlashVars', 'movie=<?php echo $filenamestr;?>&btncolor=0x<?php echo $options["btncolor"]?>&accentcolor=0x<?php echo $options["accentcolor"]?>&txtcolor=0x<?php echo $options["txtcolor"]?>&volume=<?php echo $options["volume"]?>&autoload=<?php echo ($options["autoload"]==1)?"on":"off";?>&autoplay=<?php echo ($options["autoplay"]==1)?"on":"off";?>&vTitle=<?php echo $title;?>&showTitle=yes');
				</script>  
                
			 <?php
			}
			else
			{
				
				echo "There is no video file assign for this post";
				
			}
			
		}		

	}

}

$Wp_Video_Player = new Wp_Video_Player;

?>