<?php
/* 
Plugin Name: Custom Post
Plugin URI:
Description: For creating custom articles from front side.
Version: 1.0
Author: G0947
Author URI: www.artworldwebsolutions.com
License:
*/

@session_start();


/*
ini_set("display_errors",1);
error_reporting(E_ALL);
*/




ob_start();

function custom_post(){

define('IMAGEDIR','uploads');
	$user_ID = get_current_user_id();

	if($_SESSION['article_id'] != '' && $user_ID!='')
	{
		global $wpdb;
		$aa=mysql_fetch_array(mysql_query("SELECT * FROM wp_users WHERE ID='".$user_ID."'"));
		$user_name=$aa['user_login'];

		$table_name_posts=$wpdb->prefix."posts";	
		$art_id=$_SESSION['article_id'];
		$result_d=$wpdb->query("UPDATE $table_name_posts SET post_author='".$user_ID."' WHERE ID='".$art_id."'");
	//echo "UPDATE $table_name_posts SET post_author='".$user_ID."' WHERE ID='".$art_id."'";


		$table_name_postmeta=$wpdb->prefix."postmeta";
		$result_d2=$wpdb->query("INSERT INTO `$table_name_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES (NULL, '$art_id', 'user_role', ''), (NULL, '$art_id', 'user_name', '$user_name'), (NULL, '$art_id', 'user_id', '$user_ID') ");
		if(mysql_affected_rows())
		{
			$_SESSION['article_id']="";
		}
	}


	require_once(dirname(__FILE__)."/languages_text.php");		 
	?>
	<script type="text/javascript" src="<?php echo plugins_url( 'jscripts/jquery.min.js' , __FILE__ )  ?>"></script>	
	<script type="text/javascript" src="<?php echo plugins_url( 'jscripts/jquery.validate.min.js' , __FILE__ )  ?>"></script>
	<script type="text/javascript" src="<?php echo plugins_url( 'jscripts/val_script.js' , __FILE__ )  ?>"></script>
	<?php if(!isset($_GET["post_id"])){?>
	<script type="text/javascript" src="<?php echo plugins_url( 'jscripts/drawcaptcha.js' , __FILE__ )  ?>"></script>
	<?php }?>
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'css/bootstrap.css' , __FILE__ )  ?>" />
	<link href="<?php echo plugins_url( 'css/custom.css' , __FILE__ )  ?>" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="<?php echo plugins_url('jscripts/ajaxupload.3.5.js' , __FILE__ )  ?>" ></script>
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url( 'css/styles.css' , __FILE__ )  ?>" />
	<?php wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
	if(isset($_POST["submit"]) && empty($_POST['edit_form']))
	{
		
		$title 		 = $_POST['title'];
		$description = $_POST['post_description'];
		$block 		 = $_POST['blocks'];
		$cat_name  	 = $_POST['cat_list'];
		$file		 = $_POST['featured_image'];
		$filename    = $_POST['img_name'];	
		$image_caption = $_POST['image_caption'];	
		
		list($width, $height, $type, $attr) = getimagesize($file);

		if($file!="" && basename($file)!="img-4.png"){
			if($width>=480){

				if($image_caption!=""){					
					$append_image = "<p>[caption align=\"alignright\" width=\"".$width."\"]<img src=\"".$file."\"> ".$image_caption."[/caption]</p>";
				}else{
					$append_image = "<p><img src=\"".$file."\"></p>";
				}

				if(strpos($description,"<!--more-->")){
					$replace_more = "<!--more-->".$append_image."";
					$description =preg_replace("/<!--more-->/",$replace_more,$description,1);
				}else{
					$description = $append_image.$description;
				}
			}else{

				if($image_caption!=""){					
					$append_image = "[caption align=\"alignright\" width=\"150\"]<img border=\"0\" src=\"".$file."\" width=\"150\" height=\"125\" align=\"right\">".$image_caption."[/caption]";
				}else{
					$append_image = "<img border=\"0\" src=\"".$file."\" width=\"150\" height=\"125\" align=\"right\">";
				}					

				if(strpos($description,"<!--more-->")){
					$replace_more = "<!--more-->".$append_image."";
					$description =preg_replace("/<!--more-->/",$replace_more,$description,1);
				}else{
					$description = $append_image.$description;
				}
			}
		}

		//** Append read more at the end of the content. **//
		$description = $description."<br> <!--more-->";
		//** Append section ends here **//
		
		global $wp_roles;
		global $current_user, $wpdb;
		$role = $wpdb->prefix . 'capabilities';			
		$current_user->role = array_keys($current_user->$role);
		$role = $current_user->role[0];
		if($role=="administrator"){
			$post_status = "publish";
		}else{
			$post_status = "draft";
		}
		$post = array(
			'post_title'	=> $title,
			'post_content'	=> $description,
			'post_status'	=> $post_status,
			'post_type'	=> 'post'
			);
		//==Check duplicate post==//
		global $wpdb;
		$SQL_TERMS = "SELECT term_id,name FROM ".$wpdb->prefix."terms WHERE name='".$cat_name."'";
		$rsCat = $wpdb->get_results($SQL_TERMS);
		$term_id = $rsCat[0]->term_id;
		if($term_id!=0){
			$SQL_TAXONOMY = "SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy WHERE term_id='".$term_id."'";
			$rsTaxonomy = $wpdb->get_results($SQL_TAXONOMY);
				//print_r($rsTaxonomy);
			$term_taxonomy_id = $rsTaxonomy[0]->term_taxonomy_id;
			if($term_taxonomy_id!=0){
				$SQL_POST = "SELECT ".$wpdb->prefix."posts.ID,".$wpdb->prefix."posts.post_title
				FROM  ".$wpdb->prefix."posts,".$wpdb->prefix."term_relationships
				WHERE  ".$wpdb->prefix."posts.ID=".$wpdb->prefix."term_relationships.object_id
				AND ".$wpdb->prefix."posts.post_title='".$title."'
				AND ".$wpdb->prefix."term_relationships.term_taxonomy_id='".$term_taxonomy_id."'";
				$rsPOST = $wpdb->get_results($SQL_POST);
			}
		}
		//print_r($rsPOST);
		//==Check duplicate post==//		
		if (empty($rsPOST)){
			$ID = wp_insert_post($post); 
     	//--------------------- Update Post Meta  ----------------
			$postmetablock 	=	$_POST['cat_list'].'/'.$_POST['blocks'];
			add_post_meta($ID, "sm:block", $postmetablock);
			if ( ! is_user_logged_in() )
			{			
				$_SESSION['article_id'] = $ID;
			//echo "<prE>";
			//print_r($_SESSION['article_id']);
			//echo "</pre>";

				$redir_url="/register/";
				wp_redirect($redir_url);
			}
			else
			{

			}

		//=========Error Message===================//
			if(empty($ID)){
				$_SESSION["ERRORMSG"] = "Error";
				break;
			}
		//=======================================//
			global $wpdb;
			$meta_key = $block;
			$meta_value = $block;
			$category_meta_key = "metablogcategory";
			$cat = get_cat_ID($cat_name);
			wp_set_post_terms($ID,$cat,"category");
		$post_id = $ID; // example post_ID
		$description = 'custom post image';

		# -----------------start set featured image--
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');

		$attachmentId = media_handle_upload('async-upload',$ID);
		set_post_thumbnail( $post_ID, $attachmentId );
		$a = 'a:6:{s:5:"width";s:3:"144";s:6:"height";s:2:"40";s:14:"hwstring_small";s:23:"height="35" width="128"";s:4:"file";s:8:"menu.png";s:5:"sizes";a:1:{s:14:"post-thumbnail";a:3:{s:4:"file";s:14:"menu-50x40.png";s:5:"width";s:2:"50";s:6:"height";s:2:"40";}}s:10:"image_meta";a:10:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";}}';
		$res = unserialize($a);
		$res['sizes']['post-thumbnail']['file'] = $filename;
		$res['file'] = $filename;
		
		$wpdb->query("UPDATE post_parent = '0' WHERE post_type='attachment' AND post_parent='".$post_id."'");
		media_sideload_image($filename,$post_id,$description);
		$siteurl = get_option('siteurl');
		$blog_id = $GLOBALS['current_blog']->blog_id;
		//$guid = getcwd()."/wp-content/blogs.dir/".$blog_id."/files/".basename( $filename )."";
		$guid = getcwd()."/wp-content/"."uploads/".basename( $filename )."";		
		print_r("up from empty function");
		$attachment = array(
			'guid' => $guid, 
			'post_mime_type' => $_POST['image_type'],
			'post_title' => $image_caption,
			'post_name' => preg_replace('/\.[^.]+$/', '', basename($filename)),
			'post_content' => '',
			'post_parent'=>$ID,
			'post_type'=>'attachment',
			'post_status' => 'inherit'
			);	
		if(!empty($filename))
		{
			
			wp_insert_attachment( $attachment, $filename,$ID);
			
			$last_attachment = $wpdb->get_row($query = "SELECT * FROM {$wpdb->prefix}posts ORDER BY ID DESC LIMIT 1", ARRAY_A);
			$attachment_id = $last_attachment['ID'];
			
			add_post_meta($post_id, '_thumbnail_id', $attachment_id);
			

		}
		# ------------------- end set featured image--
		
		//update_option('custom_post_frontend_'.$post_id,$post_id); 
		$blog_id = $GLOBALS['current_blog']->blog_id;
		add_post_meta($ID, $meta_key, $meta_value, $unique);
		add_post_meta($ID, $category_meta_key, $cat_name, $unique);
		if(is_user_logged_in()){
			$user_ID = get_current_user_id();
			add_post_meta($ID, "user_id", $user_ID, $unique);			
			$user_name = $current_user->user_login;
			add_post_meta($ID, "user_role", $role, $unique);
			add_post_meta($ID, "user_name", $user_name, $unique);
		}
		add_post_meta($ID, "custompost", "custom", $unique);
		//$enclousure = "".$siteurl."/wp-content/blogs.dir/".$blog_id."/files/" . basename( $filename )."";
		$enclousure = "".$siteurl."/wp-content/".IMAGEDIR."/" . basename( $filename )."";
		if(!empty($filename)){
			add_post_meta($ID, "enclosure1", $enclousure, $unique);
		}
		
		$msg = ARTICLECREATED;
		$msg1 = ARTICLEREVIEW;
		$_SESSION["PUBSTATUSMSG"] ="<p style=\"font-size:17px;color:#2C2C2C;\">".$msg."</p><p style=\"color:#2C2C2C;\">".$msg1."</p>";



		
		//============Update Block Options=====================================//			
		if($_POST['blocks'] == "featured_slider"){
			$blog_id = $GLOBALS['current_blog']->blog_id;
			$advancenewspaper = get_option("advancednewspaper");
			if($advancenewspaper["of_an_nrfea"] == 0){
				$advancenewspaper_sub = array("of_an_nrfea"=>6);
				$advancenewspaper_array1 = array_merge($advancenewspaper,$advancenewspaper_sub);
				$advancenewspaper_array = serialize($advancenewspaper_array1);
				global $wpdb;
				$options_query = 'UPDATE wp_'.$blog_id.'_options SET option_value="'.addslashes($advancenewspaper_array).'" WHERE option_name="advancednewspaper"';
				$wpdb->query($options_query);
			}		 	
		} 
	//============Update Block Options=====================================//
		
	}else{
		$msg = ARTICLEEXISTS;
		$_SESSION["ERRORMSG"] = "Article already exists";
	}
	
	
	
}




if(isset($_POST['edit_form']) && !empty($_POST['edit_form']))
{			
	$id 		 = $_POST['edit_form'];
	$title 		 = $_POST['title'];
	$description = $_POST['post_description'];
	$block 		 = $_POST['blocks'];
	$cat_name  	 = $_POST['cat_list'];
	$file		 = $_POST['featured_image'];
	$filename    = $_POST['img_name'];
	$current_user_id = get_current_user_id();
	$image_caption = $_POST['image_caption'];

	$uid = $_POST['uid'];
	if($uid == $current_user_id){
		list($width, $height, $type, $attr) = getimagesize($file);
		if($file!="" && basename($file)!="img-4.png"){
			if($width>=480){

				if($image_caption!=""){					
					$append_image = "<p>[caption align=\"alignright\" width=\"".$width."\"]<img src=\"".$file."\">".$image_caption."[/caption]</p>";
				}else{
					$append_image = "<p><img src=\"".$file."\"></p>";
				}	

				if(strpos($description,"<!--more-->")){
					$replace_more = "<!--more-->".$append_image."";
					$description =preg_replace("/<!--more-->/",$replace_more,$description,1);
				}else{						
					$description = $append_image.$description;
				}	
			}else{


				if($image_caption!=""){					
					$append_image = "[caption align=\"alignright\" width=\"150\"]<img border=\"0\" src=\"".$file."\" width=\"150\" height=\"125\" align=\"right\">".$image_caption."[/caption]";
				}else{
					$append_image = "<img border=\"0\" src=\"".$file."\" width=\"150\" height=\"125\" align=\"right\">";
				}	


				if(strpos($description,"<!--more-->")){
					$replace_more = "<!--more-->".$append_image."";
					$description =preg_replace("/<!--more-->/",$replace_more,$description,1);
				}else{
					$description = $append_image.$description;
				}
			}
		}else{
			if($_POST["imgedit"]!=""){
				$file = $_POST["imgedit"];
				list($width, $height, $type, $attr) = getimagesize($file);
				if($width>=480){

					if($image_caption!=""){					
						$append_image = "<p>[caption align=\"alignright\" width=\"".$width."\"]<img src=\"".$file."\">".$image_caption."[/caption]</p>";
					}else{
						$append_image = "<p><img src=\"".$file."\"></p>";
					}	

					if(strpos($description,"<!--more-->")){
						$replace_more = "<!--more-->".$append_image."";
						$description =preg_replace("/<!--more-->/",$replace_more,$description,1);
					}else{
						$description = $append_image.$description;
					}	
				}else{

					if($image_caption!=""){					
						$append_image = "[caption align=\"alignright\" width=\"150\"]<img border=\"0\" src=\"".$file."\" width=\"150\" height=\"125\" align=\"right\">".$image_caption."[/caption]";
					}else{
						$append_image = "<img border=\"0\" src=\"".$file."\" width=\"150\" height=\"125\" align=\"right\">";
					}

					if(strpos($description,"<!--more-->")){
						$replace_more = "<!--more-->".$append_image."";
						$description = preg_replace("/<!--more-->/",$replace_more,$description,1);
					}else{
						$description = $append_image.$description;
					}	
				}				 
			}		
		}

		
		global $post;
		if(!isset($_POST['publish_post'])){
			$my_post = array(
				'ID'			=> $id,
				'post_title'	=> $title,
				'post_content'	=> $description,
				'post_status'	=> 'draft',
				'post_type'	=> 'post'
				);	
		}else{
			$my_post = array(
				'ID'			=> $id,
				'post_title'	=> $title,
				'post_content'	=> $description,
				'post_status'	=> 'publish',
				'post_type'	=> 'post'
				);			
		}

		global $wpdb;
		wp_update_post($my_post);
		$meta_key = $block;
		$meta_value = $block;
		$category_meta_key = "metablogcategory";
		$cat = get_cat_ID($cat_name);
		wp_set_post_terms($id, $cat, "category");

			# -----------------start set featured image--
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');

		$siteurl = get_option("siteurl");	
			# -------------------end set featured image--
		delete_post_meta($id, 'featured_slider', 'featured_slider');
		delete_post_meta($id, 'below_featured', 'below_featured');
		delete_post_meta($id, 'primary_mid_column', 'primary_mid_column');
		update_post_meta($id, $meta_key, $meta_value, $unique);
		update_post_meta($id, $category_meta_key, $cat_name, $unique);
		if(!empty($filename)){
			$blog_id = $GLOBALS['current_blog']->blog_id;
				//$enclousure = "".$siteurl."/wp-content/blogs.dir/".$blog_id."/files/" . basename( $filename )."";
			$enclousure = "".$siteurl."/wp-content/".IMAGEDIR."/" . basename( $filename )."";
				//====================Delete Image From Folder==========================//
			$guid = getcwd()."/wp-content/".IMAGEDIR."/".basename( $filename )."";

			$attachment = array(
				'guid' => $guid, 
				'post_mime_type' => $_POST['image_type'],
				'post_title' => $image_caption,
				'post_name' => preg_replace('/\.[^.]+$/', '', basename($filename)),
				'post_content' => '',
				'post_parent'=>$id,
				'post_type'=>'attachment',
				'post_status' => 'inherit'
				);
			wp_insert_attachment($attachment, $filename,$id);
			$last_attachment = $wpdb->get_row($query = "SELECT * FROM {$wpdb->prefix}posts WHERE post_parent='".$id."' ORDER BY ID DESC LIMIT 1", ARRAY_A);
			$attachment_id = $last_attachment['ID'];
			if($attachment_id!=""){
				update_post_meta($id, '_thumbnail_id', $attachment_id);
			}
			global $wpdb;
			$result_post_meta_en = $wpdb->get_row("SELECT * FROM  ".$wpdb->prefix."postmeta WHERE  `post_id` = '".$id."' AND meta_key='enclosure1'");

			$file1 = getcwd()."/wp-content/".IMAGEDIR."/".basename($result_post_meta_en->meta_value);
			unlink($file1);

			  //====================Delete Image From Folder==========================//
			update_post_meta($id, "enclosure1", $enclousure, $unique);
		}
		else
		{
			$_thumbnail_id = get_post_meta($id, "_thumbnail_id", true );
			if(!empty($_thumbnail_id))
			{
				$thumb_post = array(
					'ID'			=> $_thumbnail_id,
					'post_title'	=> $image_caption					
					);
				wp_update_post($thumb_post);
			}
		}

	//============ Update Block Options =====================================//			
		if(isset($_POST['publish_post']) && $_POST['blocks'] == "featured_slider"){
			$blog_id = $GLOBALS['current_blog']->blog_id;
			$advancenewspaper = get_option("advancednewspaper");
			if($advancenewspaper["of_an_nrfea"] == 0){
				$advancenewspaper_sub = array("of_an_nrfea"=>6);
				$advancenewspaper_array1 = array_merge($advancenewspaper,$advancenewspaper_sub);
				$advancenewspaper_array = serialize($advancenewspaper_array1);
				global $wpdb;
				$options_query = 'UPDATE wp_'.$blog_id.'_options SET option_value="'.addslashes($advancenewspaper_array).'" WHERE option_name="advancednewspaper"';
				$wpdb->query($options_query);
			}		 	
		} 
			//============Update Block Options=====================================//
		$msg = ARTICLEUPDATED;
		$_SESSION["STATUSMSG"] = "".$msg.".....";
		$site_url = get_option("siteurl");			
		$URL = $site_url."/custom-post/";
		wp_redirect($URL, $status);
		exit;
	}else{
		$_SESSION["ERRORILLEGALPOSTMSG"] = "Illegal Access";
	}	

} ?>
<?php //==================Fetch row at edit time============================//
$id = $_GET['post_id'];
if(isset($id) && !is_numeric($id)){
	$_SESSION["ERRORILLEGALPOSTMSG"] = "Illegal Access";			 
}

if($id!="" && is_numeric($id)){
	$userid = get_post_meta($id,"user_id",true);
	$current_user_id = get_current_user_id();
	if($userid==$current_user_id){
		global $wpdb;
		$result = $wpdb->get_row("SELECT * FROM  ".$wpdb->prefix."posts WHERE  `ID` = '".$id."' ");
		$edit_post_title = $result->post_title;
		$edit_post_status = $result->post_status;
		$edit_post_ID = $result->ID;

		$editcontent = preg_replace("/<img[^>]+\>/i", "", $result->post_content); 

		$editcontent = preg_replace('#\[caption[^\]]*\](.*?)\[/caption\]#m', "", $editcontent);				 

		$edit_post_content .= str_replace("<p></p>","", $editcontent);

		$below_featured = get_post_meta($id,"below_featured",true);
		$featured_slider = get_post_meta($id,"featured_slider",true);
		$primary_mid_column = get_post_meta($id,"primary_mid_column",true);
		if($below_featured!=""){
			$edit_smblock = $below_featured;
		}else if($featured_slider!=""){
			$edit_smblock = $featured_slider;
		}else if($primary_mid_column!=""){
			$edit_smblock = $primary_mid_column;
		}

		$edit_enclosure = get_post_meta($id,"enclosure1",true);
		$edit_category = get_post_meta($id,"metablogcategory",true);

		$_thumbnail_id = get_post_meta($id, "_thumbnail_id", true );
		if(!empty($_thumbnail_id)){					 
			$thumbnail_data = get_post($_thumbnail_id);
			$edit_image_caption = $thumbnail_data->post_title;
		}				
	}else{
		$_SESSION["ERRORILLEGALPOSTMSG"] = "Illegal Access";
	}
}
	 //==================Fetch row at edit time=============================//
?>
<?php
if(isset($_SESSION["PUBSTATUSMSG"]) || isset($_SESSION["DELETESUCCMSG"])){
	$onclick = "return close_popup();";
}else if(isset($_SESSION["ERRORMSG"]) || isset($_SESSION["DELETEERRMSG"])){
	$onclick = "return close_popup_error();";
}else if(isset($_SESSION['ERRORILLEGALPOSTMSG'])){
	$onclick = "return close_popup_error_post();";
}
?>
<div id="postbox" onclick="<?php echo $onclick;?>"> 
	<form name="new_post" method="post" action="" id="register-form" novalidate="novalidate" onSubmit="return validate_custum_form();"> 
		<?php 
		$meta_key = "Custom Post"; 
		$key_1_value = get_post_meta(get_the_ID(), $meta_key, true);
	// check if the custom field has a value
		$array_postmeta_find = array("{REGISTER}","{LOGIN}");
		if ( ! is_user_logged_in() ){
			$register_link = '<a href="' . get_permalink( get_page_by_path( 'register' ) ) . '">' . __('here') . '</a>';
			$login_link = '<a href="' . esc_url( wp_login_url($redirect) ) . '">' . __('Log in') . '</a>';
		}else{
			$register_link = '<a href="' . get_permalink( get_page_by_path( 'edit-profile' ) ) . '">' . __('here') . '</a>';
			$login_link = '<a href="' . esc_url( wp_logout_url($redirect) ) . '">' . __('Log out') . '</a>';
		}
		$array_postmeta_replace = array($register_link,$login_link);
		$custompostfield = str_replace($array_postmeta_find,$array_postmeta_replace,$key_1_value);

		if( ! empty( $key_1_value ) ){
			echo '<p style="width:950px;text-align:justify;">'.$custompostfield .'</p>';
		} 
		?>
		<span class="successmsg"><?php echo $_SESSION["STATUSMSG"];unset($_SESSION["STATUSMSG"]);?></span>
		<?php 
		if(isset($_SESSION["PUBSTATUSMSG"]) || isset($_SESSION["DELETESUCCMSG"])){
			if($_SESSION["PUBSTATUSMSG"]!=""){
				$display = "block";	
				$sucessmsg = ARTICLESUBMITTED;
				$sucessmsg1 = ARTICLESUBMITTEDSUB;
				$sucessmsg2 = ARTICLESUBMITTEDSUB1;
			}if($_SESSION["DELETESUCCMSG"]!=""){
				$display = "block";	
				$sucessmsg = ARTICLEDELETEDHEADER;
				$sucessmsg1 = $_SESSION['posttitle']."&nbsp;".ARTICLEDELETED;
				$sucessmsg2 = "";
			}else{
				$display = "none";
			}
			?>
			<div id="popup_box" style="display:<?php $display;?>">
				<div class="popinnerdiv"><div class="popinnerdivsub"><?php echo $sucessmsg;?></div></div>
				<div style="height:85px;"><div class="popinnerdivsub1"><?php echo $sucessmsg1;?><br/><?php echo $sucessmsg2;?></div></div>
				<div style="text-align:center;"><img src="<?php echo plugins_url( 'images/button-sucess.png' , __FILE__ )  ?>" onclick="return close_popup();" style="cursor:pointer;"/></div>
			</div>
			<?php }else if(isset($_SESSION["ERRORMSG"]) || isset($_SESSION["ERRORILLEGALPOSTMSG"]) || isset($_SESSION["DELETEERRMSG"])){
				if($_SESSION["ERRORMSG"]!="" || $_SESSION["DELETEERRMSG"]!=""){
					$display = "block";	
					$close_popup_error = "return close_popup_error();";
				}else if($_SESSION['ERRORILLEGALPOSTMSG']!=""){
					$display = "block";
					$close_popup_error = "return close_popup_error_post();";
				}else{
					$display = "none";
				}

				if($_SESSION["ERRORMSG"]=="Articlealreadyexists"){
					$article_error_sub = ARTICLEEXISTS;
					$article_error_sub1 = ERROROCCUREDSUB2;
				}else if($_SESSION['ERRORILLEGALPOSTMSG']=="Illegal Access"){
					$article_error_sub = ILLEGALACCESS;
					$article_error_sub1 = "";
				}else if($_SESSION["DELETEERRMSG"]=="ERROR DELETE POST"){			
					$article_error_sub = ERROROCCUREDSUB1;
					$article_error_sub1 = "";
				}else{			
					$article_error_sub = ERROROCCUREDSUB;
					$article_error_sub1 = ERROROCCUREDSUB1;
				}
				?>
				<div id="popup_box_error" style="display:<?php $display;?>">
					<div class="popinnerdiv"><div class="popinnerdivsub"><?php echo ERROROCCURED;?></div></div>
					<div style="height:85px;"><div class="popinnerdivsub1"><?php echo $article_error_sub;?><br/><?php echo $article_error_sub1;?></div></div>
					<div style="text-align:center;"><img src="<?php echo plugins_url( 'images/button-error.png' , __FILE__ )  ?>" onclick="<?php echo $close_popup_error;?>" style="cursor:pointer;"/></div>
				</div>
				<?php 
			}
			unset($_SESSION["PUBSTATUSMSG"]);
			unset($_SESSION["ERRORMSG"]);
			unset($_SESSION["ERRORILLEGALPOSTMSG"]);
			unset($_SESSION["DELETESUCCMSG"]);
			unset($_SESSION["posttitle"]);
			unset($_SESSION["DELETEERRMSG"]);			
			?>

			<div id="imgprgbar" style="display:none;">
				<div id="loading"></div>
				<div id="content"><?php echo IMAGEUPLOADSTATUSBAR;?></div>

			</div>		
			<div id="top">		
				<div id="category">
				<!--<strong>Choose Page</strong><br/>
				Choose under which menu you want your article to be posted<br/>-->
				<img src="<?php echo plugins_url( 'images/menu.png' , __FILE__ )  ?>" /><br/>
				<div style="width:180px;margin-top:5px;">
					<div class="border-round" style="float:left;margin-top:5px;">1</div>
					<div style="float:right;"><?php 
					//$catlistdetail = get_categories();
						global $wpdb;
						$catlistdetail = array();
						$catlistdetail = $wpdb->get_results('SELECT t1.name FROM '.$wpdb->prefix.'terms AS t1,'.$wpdb->prefix.'term_taxonomy AS t2 WHERE t1.term_id = t2.term_id AND t2.taxonomy = "category" AND t1.name!="Uncategorized"');

						echo '<select name="cat_list" id="cat_list">';
						if(count($catlistdetail)>0){
							foreach($catlistdetail as $catlist){
								if($edit_category==$catlist->name){
									echo '<option value="'.$catlist->name.'" selected="selected">'.$catlist->name.'</option>';
								}else{
									echo '<option value="'.$catlist->name.'">'.$catlist->name.'</option>';
								}	
							}
						}else{
							echo '<option value="Uncategorized">'.HOME.'</option>';
						}	
						echo '</select>';
						?></div></div>
						<div style="display:none;" id="show_cat" class="errormsg"><?php echo CATEGORYDIV;?></div>
					</div>
					<div id="uploadpic">
						<!--<strong>Choose main picture</strong> <br/>This is the picture that will appear on the front page<br/>-->
						<input type="hidden" value="<?php echo plugins_url();?>" id="url" />
						<input type="hidden" name="hide_image" id="hide_image" />	
						<?php if($edit_enclosure!=""){ ?>	
						<img style="width:175px;" id="featuredImg" src="<?php echo $edit_enclosure; ?>" />				 
						<?php }else{ ?>
						<img style="width:175px;" id="featuredImg" src="<?php echo plugins_url( 'images/img-4.png' , __FILE__ ); ?>" />		
						<?php } ?>
						<input type="hidden" name="featured_image" id="featured_image" value="<?php echo plugins_url( 'images/img-4.png' , __FILE__ )?>" />
						<input type="hidden" name="image_type" id="image_type" value="" />
						<input type="hidden" name="img_name" id="img_name" value=""/>
						<input type="hidden" name="language" id="language" value="<?php echo get_option("WPLANG");?>"/>
						<input type="hidden" name="confirmdel" id="confirmdel" value="<?php echo CONFIRMDELETE;?>"/>
						<input type="hidden" name="selectdel" id="selectdel" value="<?php echo ARTICLESELECTION;?>"/>
						<div style="width:180px;margin-top:5px;">
							<div style="float:left;width:89px;"><div class="border-round" style="margin-top:5px;margin-left:60px;">2</div></div>
							<div style="float:right;width:89px;">
								<div id="upload">
									<span><img src="<?php echo plugins_url( 'images/cam-1.png' , __FILE__ );  ?>" style="width:38px;height:32px;" title="Set Featured Image"/><span>
									</div>					
								</div>
							</div>
							<div style="clear:both;"></div>
							<span id="status" ></span>
						</div>
						<div id="block">				
							<div>
								<img src="<?php echo plugins_url( 'images/img-5.png' , __FILE__ );  ?>" />
								<div align="center" style="width:269px;">
									<div  style="width:40px;float:left;">
										<div class="border-round" style="margin-top:10px;float:right;">3</div></div>
										<div style="width:177px;">
										<div style="float:left;margin-left:10px;margin-top:5px;">
												<select name="blocks" id="blocks1">				
													<option value="featured1">Featured1</option>
													<option value="block11">Block11</option>
													<option value="block12">Block12</option>
													<option value="block13">Block13</option>
													<option value="block14">Block14</option>
													<option value="block21">Block21</option>
													<option value="block22">Block22</option>
													<option value="block31">block31</option>
													<option value="block32">block32</option>
													<option value="block33">block33</option>
												</select>
											</div>
										</div>
									</div> 
									<!--<div id="picsize">(Requires 500 x 300 pictures)</div>-->
									<div style="width:500px; margin-top:45px;">
										<div id="show_position" class="errormsg padding_text_position"><?php echo POSITIONDIV;?></div>
										<div id="show_image" class="errormsg padding_text_position"><?php echo IMAGEDIV;?></div>
										<div id="show_captcha" class="errormsg padding_text_captcha"><?php echo CAPTCHAEDIV;?></div>
										<div id="show_captcha_valid" class="errormsg padding_text_captcha"><?php echo INVALIDCAPTCHADIV;?></div>
									</div></div>
								</div>
								<div id="publish">
									<!-- <img src="< ?php //echo plugins_url( 'captcha.php' , __FILE__ );  ?>" id="captcha" />-->
									<?php if(!isset($_GET['post_id'])){ ?>
									<div id="txtCaptcha"></div>	
									<span id="capcha-text"><?php echo CAPTCHACODE;?>: * <img src="<?php echo plugins_url( 'images/refresh.png' , __FILE__ );  ?>" onclick="return DrawCaptcha();" style="cursor:pointer;"></span>
									<?php } ?>
									<div>
										<?php if(!isset($_GET['post_id'])){ ?>
										<input type="text" name="captcha" id="captcha-form" autocomplete="off" maxlength="5" />
										<?php }
										if(!isset($_GET['post_id'])){
											$submit =  SUBMIT;		
											$display_publish = "none;";
											$display_preview = "none;";
											$class_submit = "submit";
											$style ="width:auto;margin-top:-32px;";
										}else{
											$submit = SAVEDRAFT;														
											$bloglanguage = get_option("WPLANG");
							/*if($bloglanguage == "sv_SE"){
								  $display_publish = "width:125px;block;";
								  $display_preview = "width:125px;block;";
								  $style= "width:125px;";
							}else if($bloglanguage == "nb_NO"){
								 $style= "width:91px;" ;								 
								 $display_publish = "width:91px;block;";
								 $display_preview = "width:91px;block;";
							}else{
								  $style= "width:81px;";
								  $display_publish = "width:81px;block;";
								  $display_preview = "width:81px;block;";
								} */							
							}
							?>
							<div><input type="submit" value="<?php echo $submit;?>" tabindex="6" id="submit" class="submit" style="<?php echo $style;?>" name="submit"/></div>
							<div style="clear:both;"></div>

							<?php if(isset($_GET['post_id'])){ ?>
							<div><input type="submit" value="<?php _e('Publish');?>" tabindex="7" id="publish_post" class="submit" name="publish_post" style="<?php echo $display_publish;?>" /></div>	<div style="clear:both;"></div>
							<?php if($edit_post_status=="publish"){ 
								$permalink = get_permalink( $edit_post_ID );
								?>					
								<div><a href="<?php echo $permalink; ?>" id="post-preview" target="_blank" name="post-preview"><input type="button" value="<?php _e("Preview");?>" tabindex="8" id="post_preview" class="submit" name="post_preview" style="<?php echo $display_preview;?>" /></a></div>						
								<?php 
							}
							$edit_form = $_GET['post_id'];
						}else{
							$edit_form = "";
						}
						
						$siteurl = get_option('siteurl');
						?>
						<input type="hidden" id="edit_form" name="edit_form" value="<?php echo $edit_form;?>">
						<input type="hidden" id="uid" name="uid" value="<?php echo $userid;?>">
						<input type="hidden" id="imgedit1" name="imgedit" value="<?php echo $edit_enclosure;?>">
						<input type="hidden" id="siteurl" name="siteurl" value="<?php echo $siteurl;?>">
						<input type="hidden" name="imagedir" id="imagedir" value="<?php echo IMAGEDIR;?>"/>
					<!--<input type="submit" value="Preview" tabindex="6" id="submit" class="submit" name="submit" />
					<input type="submit" value="Publish" tabindex="6" id="submit" class="submit" name="submit" />-->
				</div>
			</div>			
			
		</div>
		<div id="bottom">
			<div id="title">
				<div class="fieldgroup">
					<label for="title"><?php _e('Title');?></label>
					<input type="text" id="title1" tabindex="2" size="120" name="title" value="<?php echo $edit_post_title;?>" />
				</div>	
				<div id="show_title" class="errormsg padding_text"><?php echo TITLEDIV;?></div>
				<div id="show_description" class="errormsg padding_text"><?php echo DESCRIPTIONDIV;?></div>
			</div>
			<div id="image_caption">
				<div class="fieldgroup">
					<label for="title"><?php _e('Image Caption');?></label>
					<input type="text" name="image_caption" tabindex="3" value="<?php echo $edit_image_caption; ?>" />					
				</div>	
				<div id="show_title" class="errormsg padding_text"><?php echo TITLEDIV;?></div>
				<div id="show_description" class="errormsg padding_text"><?php echo DESCRIPTIONDIV;?></div>
			</div>			
			<div id="editor">
				<?php 
				$settings = array(
					'quicktags' => array('buttons' => 'em,strong,link',),
					'text_area_name' => 'extra_content', 
					'quicktags' => true,
					'tinymce' => array(
						'advlink1' => 1,
						'advimage' => 0,
						'editorstyle' => 0,
						'hideclasses' => 0,
						'contextmenu' => 0,
						'no_autop' => 1,							
						'advlist' => 0,								
						'paragraph' => 1,						
						),
					'media_buttons' => false,
					'wpautop' => true,
					'tabindex' => 4 
					);					
				$id = 'post_description';
				$initial_data ="";

				if(!empty($edit_post_content)){
					$initial_data = $edit_post_content;
				}					
				wp_editor($initial_data,$id,$settings);					
				?>
			</div>
		</div>
		<input type="hidden" name="action" value="post" />
		<?php wp_nonce_field( 'new-post' ); ?>
	</form>
</div>
<?php if (is_user_logged_in()) { ?>
<div id="edit_delete">
	<?php
	global $wpdb;
	$user_id = get_current_user_id();
	$postlist = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'postmeta WHERE meta_key="user_id" AND meta_value="'.$user_id.'" ORDER BY post_id DESC'); 
		//print_r($postlist);
		//echo count($postlist);
	if(count($postlist)>0){	
		if(count($postlist)>10){
			$style = "height:290px;overflow-y:scroll;";
		}else{
			$style = "";
		}
		?>
		<div>
			<input type="hidden" name="postid" id="postid" />
			<input type="button" value="<?php _e("Edit");?>" onclick  = "edit_rec()" class="buttonimag" />
			<input type="button" value="<?php _e("Delete");?>" onclick ="deletepost()" class="buttonimag" />
		</div>
		<div id="edit_delete_list">			
			<div id="head_tag">
				<div id="head_tag_title"><?php _e("Title");?></div>
				<div id="head_tag_date"><?php _e("Date");?></div>
				<div id="head_tag_status"><?php _e("Status");?></div>
			</div>		
			<div id="post_list" style="<?php echo $style;?>"><ul style="padding-left:0px;" class="ullist">
				<div class="box_border_bottem"></div>
				<?php
				for($j=0;$j<count($postlist);$j++){
					$rsPost[] =  get_post($postlist[$j]->post_id);
				}		
				$counter = 0;
				for( $i=0; $i < count($rsPost); $i++ ){
					$counter = $counter+1;
					?><li>
					<div id="post_list_title">
						<input type="radio" id="post_check" name="post_check" value="<?php echo $rsPost[$i]->ID; ?>" onclick="return sendid('<?php echo $rsPost[$i]->ID;?>');"/>
						<span class="posttitlepad"><?php echo $rsPost[$i]->post_title; ?></span>
					</div>
					<div id="post_list_date"><?php  
						$time1 = date("Y-m-d H:i:s");
						$time2 = $rsPost[$i]->post_date;
						$hourdiff = round((strtotime($time1) - strtotime($time2))/3600,1);								
						if( $hourdiff < 1 )
						{
							$minutes = round((strtotime($time1) - strtotime($time2))/60,1);
							$hours = round($minutes)."&nbsp;".MINUTESAGO."";
						}
						else
						{
							$hours = round($hourdiff)."&nbsp;".HOURSAGO."";	
						}
						if( date("Y-m-d",strtotime($time2)) < date("Y-m-d") )
						{
							echo date("Y-m-d",strtotime($time2));
						}
						else
						{
							echo $hours;								
						}								
						?></div>
						<div id="post_list_status"><?php 
							echo _e(ucfirst($rsPost[$i]->post_status));
							?></div>
						</li>
						<?php if($counter!=count($rsPost)){ ?>
						<div class="box_border_bottem"></div>
						<?php						 
					} 
				}
				?>
			</ul>
		</div>		
	</div>
	<?php }else{ ?>
	<div class="noarticlefound"><?php echo NOARTICLEFOUND;?></div>
	<?php } ?>
</div>
<?php 
}  
}

add_shortcode("custompost","custom_post");
function edit1_post(){	
	echo 'edit post';
}
add_shortcode("editpost","edit1_post"); 

add_action('wp_head','hook_css');

function hook_css()
{

	//$output="<style> div[id*='fscf_required'] {display:none } .content {width: 85% !important;</style>";

		echo $output;

		$option_name 	= 'tadv_version' ;
		$new_value 		= '3420' ;

		$option_name1 	= 'tadv_plugins' ;
		$new_value1 	= 'a:7:{i:0;s:5:"style";i:1;s:8:"emotions";i:2;s:5:"print";i:3;s:13:"searchreplace";i:4;s:10:"xhtmlxtras";i:5;s:7:"advlink";i:6;s:8:"advimage";}' ;

		$option_name2 	= 'tadv_options' ;
		$new_value2 	= 'a:7:{s:8:"advlink1";i:1;s:8:"advimage";i:1;s:11:"editorstyle";i:0;s:11:"hideclasses";i:0;s:11:"contextmenu";i:0;s:8:"no_autop";i:1;s:7:"advlist";i:0;}' ;


		if ( get_option( $option_name ) !== false ) {
    // The option already exists, so we just update it.
    //update_option( $option_name, $new_value );
		} else {
			$deprecated = null;
			$autoload = 'yes';
			add_option( $option_name, $new_value, $deprecated, $autoload );
		}

		if ( get_option( $option_name1 ) !== false ) {
    // The option already exists, so we just update it.
    //update_option( $option_name, $new_value );
		} else {
			$deprecated = null;
			$autoload = 'yes';
			add_option( $option_name1, $new_value1, $deprecated, $autoload );
		}

		if ( get_option( $option_name2 ) !== false ) {
    // The option already exists, so we just update it.
    //update_option( $option_name, $new_value );
		} else {
			$deprecated = null;
			$autoload = 'no';
			add_option( $option_name2, $new_value2, $deprecated, $autoload );
		}
	}
	?>