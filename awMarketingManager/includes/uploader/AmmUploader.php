<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AmmUploader {

    function upload() {

        if(!AmmBuyer::ifLoggedIn()){
            die;
        }   

        if( isset($_POST['uploader']) ) {
            self::processUpload( $_POST, $_FILES );
            die();
        }

        $currentDir = dirname(__FILE__);

        $current_user = wp_get_current_user();

        $role = $current_user->roles[0];

        // echo '<pre>';
        // print_r($roles);
        // echo '</pre>';

        // if($role == 'seller'){ 
            ?>
            <form name="form" id="form" method="post" enctype="multipart/form-data">
               
               <div class="row">
                    <div class='col-md-6'>
                        <div class="form-group">
                            <label for="usr">Upload CSV:</label>
                            <input type="file" name="csv_file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"  class="filestyle" id="csv_file" required />
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <input type="hidden" name="uploader" value="1">
                            <input type="submit" name="submit" value="Submit" class="bwic_submit btn btn-default" />
                        </div>
                    </div>
                </div>
            </form>
        <?php
        // }
        // else{

        //     echo "You don't have permission to access this page.";

        // }
    }

    public static function processUpload( $postArr, $filesArr ) {
        $bonds = array();    
        $uploadPath = dirname(__FILE__)."/uploads";

        $tmp_name = $filesArr["csv_file"]["tmp_name"];
        $name     = $filesArr["csv_file"]["name"];
        $ext      = pathinfo($name, PATHINFO_EXTENSION);

        move_uploaded_file($tmp_name, "$uploadPath/$name");

        $Filepath = $uploadPath."/".$name;

        $bonds = self::readXLS( $Filepath );

        if( !empty($bonds) ) {
            self::insertBonds($bonds);
        }

        $htmlSucess = ' <div class="alert alert-success">
                          <h5><strong>Success!</strong> Bonds Uploaded!</h5>                          
                        </div>';


        echo $htmlSucess;
                            
        //echo "<h3> Bonds Uploaded Successfully!</h3>";
        echo '<h3> return to <a href="/bonds-uploader/">Upload Page</a></h3>';


    }
    

    public static function insertBonds( $bonds ) {

        $email_bonds = array();

        foreach ( $bonds as $k => $bond) {
            if(empty($bond)) {
                continue;
            }

            $my_post = array(
                'post_title'    => $bond['Type'],                
                'post_status'   => 'draft',
                'post_author'   => get_current_user_id(),
                'post_type'     => 'bond'
            );
            
            $lastid = wp_insert_post( $my_post );
            update_post_meta($lastid, 'mode', 'marketing');

            foreach ($bond as $key => $value) {

                $meta_key   = str_replace(' ', '_', $key);
                $meta_key   = str_replace('$', '', $meta_key);
                $meta_key   = strtolower($meta_key);

                $meta_value = $value;
                $email_bonds[$k]['id']    = $lastid;  
                $email_bonds[$k][$meta_key]  = $meta_value;
                update_post_meta($lastid, $meta_key, $meta_value);
            }
        }

         echo AmmEmailNotifications::sendNotificationToAdmin($email_bonds);   
         echo AmmEmailNotifications::sendNotificationSummarySeller($email_bonds);   
         
         
        
    }    

    public static function readXLS( $Filepath ) {
        require('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
        require('spreadsheet-reader-master/SpreadsheetReader.php');

        date_default_timezone_set('UTC');

        $StartMem = memory_get_usage();

        try{
            
            
            $bonds = array();

            $Spreadsheet = new SpreadsheetReader($Filepath);
            $BaseMem     = memory_get_usage();
            $Sheets      = $Spreadsheet -> Sheets();

            foreach ($Sheets as $Index => $Name){

                $Time      = microtime(true);
                $header    = true;
                $headerRow = array();

                $Spreadsheet -> ChangeSheet($Index);              

                foreach ( $Spreadsheet as $Key => $Row ) {
                    
                    if( $Row[0] == "" ) {
                            continue;
                    }

                    if( $header ){
                        $header = false;
                        $headerRow = $Row;
                        continue;
                    }

                    $tempData   = array_combine ( $headerRow, $Row );
                    $bonds[]    = $tempData;
                }
            }

        } catch (Exception $E) {
            echo $E -> getMessage();
        }      

        return $bonds;

    }

} /*Class ends */    