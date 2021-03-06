<?php

$blogname    = trim($_POST['blogname']);
$requirePath = "/var/www/html/".$blogname."/wp-load.php";

require($requirePath);

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class RssUpdateSingle {

    function updateBlog(){
        global $wpdb;            
        
        $post_update = 0;
        $post_create = 0;


        $rssFeed    = $wpdb->get_results( "SELECT link_url FROM wp_links; ");
        file_put_contents(dirname(__FILE__).'/rssfeed.log', print_r($rssFeed,true),FILE_APPEND);
        foreach ($rssFeed as $feed ) {
            
            $rss = $feed->link_url;
            
            file_put_contents(dirname(__FILE__).'/rss.log', print_r($rss,true),FILE_APPEND);
            $doc    =   new DOMDocument();

            if( empty( $rss) ){
                echo "Blog Rss Feed Empty";
            }else{
                $doc->load($rss);
                if (@$doc->load($rss) === false)
                {
                    echo "<div><h3>Blog Rss Feed Empty!</h3></div>";
                }
            }

            foreach ( $doc->getElementsByTagName('item') as $node ){

                $description    =   preg_replace('~>\s+<~m', '><', $node->getElementsByTagName('description')->item(0)->nodeValue);
                $description    =   trim($description);
                $chart_count    =   0;
                $title          =   $node->getElementsByTagName('title')->item(0)->nodeValue;
                $smblock        =   $node->getElementsByTagName('block')->item(0)->nodeValue;
                $guid           =   $node->getElementsByTagName('guid')->item(0)->nodeValue;
                $link           =   $node->getElementsByTagName('link')->item(0)->nodeValue;
                $pubDate        =   $node->getElementsByTagName('pubDate')->item(0)->nodeValue;
                $category       =   $node->getElementsByTagName('category')->item(0)->nodeValue;
                
                $dot            =   ' ';
                $position       =   stripos ($guid, $dot); 
                $offset         =   $position; 
                $position2      =   stripos ($guid, $dot, $offset);
                $content2       =   substr($guid, 0, $position2); 
                $content        =   str_replace($content2, '', $guid);
                $enclosure      =   $node->getElementsByTagName('enclosure');
                

                
                if( $enclosure->item(0) != "" ){
                    $URL        = $enclosure->item(0)->getAttribute('url');
                    $imagetype  = $enclosure->item(0)->getAttribute('type');
                }else{
                    $URL        = "";
                    $imagetype  = "";                    
                }
               
                $category = get_term_by('name', $category, 'category');                
                $cat_ID   = $category->term_id; 

                $posttitle = trim($title);
                $sql = "SELECT ID FROM $wpdb->posts WHERE post_title LIKE '%".$posttitle."%'";
                $postid    = $wpdb->get_results ( $sql );

                $postData['post_title']    =  $title;
                $postData['post_content']  = $description;
                $postData['post_category'] = array( $cat_ID );
                
                $postData['smblock']       = $smblock;
                $postData['link']          = $link;
                $postData['enclosure']     = $URL;

                if( !empty( $postid ) ){                 

                    foreach ($postid as $post ) {                        
                        //$wpdb->delete( 'wp_postmeta', array( 'post_id' => $post->ID  ) );
                        wp_delete_post( $post->ID, true);
                        delete_post_meta($post->ID, 'post_id', ''); 
                        
                    }

                    $this->createPost($postData);
                    // echo "<h3 style='color:#DBA901'>POST Updated: ".$title."</h3>";
                    $this->post_update++;                    
                }else{

                    $this->createPost($postData);
                    // echo "<h3 style='color:#298A08'>POST Created: ".$title."</h3>";                    
                    $this->post_create++;
                }   
            }
        
        }/* foreach ends here. */

        $data['created'] = "Posts Created: ".$this->post_create;
        $data['updated'] = "Posts Updated: ".$this->post_update;

        echo json_encode($data);
    }


    /* Function to create new Post. */
    function createPost( $postData ){
                   
        // Create post object
        $my_post = array(
          'post_title'    => wp_strip_all_tags( $postData['post_title'] ),
          'post_content'  => $postData['post_content'],
          'post_status'   => 'publish',
          'post_author'   => 1,
          'post_category' => $postData['post_category']
        );
         
        // Insert the post into the database
        $insert_ID = wp_insert_post( $my_post );

        if( $insert_ID ){
            add_post_meta( $insert_ID, 'enclosure', $postData['enclosure'] );
            add_post_meta( $insert_ID, 'sm:block', $postData['smblock'] );
            add_post_meta( $insert_ID, 'syndication_permalink', $postData['link'] );

            /* function to addAttachment */
            $this->addAttachment( $insert_ID, $postData['enclosure'] );
        }
    }


    /*function to add attatchment.*/
    function addAttachment( $postID, $enclosure ){
        
        if( !empty($enclosure) ):
            /*code to copy Image*/
            $source = $enclosure;

            $destination = ABSPATH."/wp-content/uploads/".basename($enclosure);
           
            if(@copy($source,$destination)){
                // / $filename should be the path to a file in the upload directory.
                $filename       = $destination;
                
                // The ID of the post this attachment is for.
                $parent_post_id = $postID;
                
                // Check the type of file. We'll use this as the 'post_mime_type'.
                $filetype       = wp_check_filetype( basename( $filename ), null );
                
                // Get the path to the upload directory.
                $wp_upload_dir  = wp_upload_dir();

                // Prepare an array of post data for the attachment.
                $attachment = array(
                    'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );

                // Insert the attachment.
                $attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

                // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
                require_once( ABSPATH. 'wp-admin/includes/image.php' );

                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                wp_update_attachment_metadata( $attach_id, $attach_data );

                set_post_thumbnail( $parent_post_id, $attach_id );
            }
        endif;
    }
}/* Class ends here */


$RssUpdateSingle = new RssUpdateSingle;
$RssUpdateSingle->updateBlog();
?>