<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AwSeller {

    public static function sellerDashBoard(){
        if (!is_user_logged_in()) {
            echo "<h2>You are not logged in user. Please login.</h2>";
        }else{
            $posts = AwAuction::getAuctionsByUser();
            
            echo self::getHTML($posts );
            echo self::jsScripts();
        die;
        }

    }

    public static function getHTML( $postData ){
        $html = '';
        $html .= '<table class="table-front" cellpadding="0" cellspacing="0">';
        $html .=    '<thead class="thead-inverse">';
        $html .=        '<tr class="success">';
        $html .=           '<th>BWIC Title</th>';
        //$html .=           '<th>Start Date</th>';
        $html .=           '<th>Due Date and Time</th>';
        $html .=           '<th>Status</th>';
        $html .=        '</tr>';
        $html .=    '</thead>';
        $html .=    '<tbody>';

        foreach ($postData as $auction) {
            /*if ($auction->status !== 'completed' ) {
                continue;
            }*/

            $bwic_title = get_post_meta($auction->ID,'bwic_title', true);
            $bwicTitle = "";
            if ($bwic_title !== "") {
                $bwicTitle = " - ".$bwic_title;
            }

            $html .=        '<tr  data-toggle="collapse" data-target="#accordion-'.$auction->ID.'" class="clickable">';
            $html .=            '<td>';
            $html .=                '<a style="cursor:pointer; font-weight:bold;">'.$auction->post_title.$bwicTitle.'</a>';
            $html .=            '</td>';
            //$html .=            '<td>'.date('m-d-Y h:i A', $auction->start_date).'</td>';
            $html .=            '<td>'.date('m-d-Y h:i A', $auction->end_date).'</td>';
            $html .=            '<td><strong class="auction-status">'.$auction->status.'</strong></td>';
            $html .=        '</tr>';
            $html .=        '<tr id="accordion-'.$auction->ID.'" class="collapse">
                                <td colspan="3">
                                    <div>
                                        '.self::getBonds($auction->ID ).'
                                    </div>
                                </td>
                            </tr>';
        }

        $html .=    '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public static function getBonds( $auction_ID ) {

        $html  = '';
        $html .= '<table class="table-front">';
        $html .=    '<thead>';
        $html .=        '<tr class="success">';
        $html .=           '<th>Type</th>';
        $html .=           '<th>Orig Rating</th>';
        $html .=           '<th>CU SIP/ISIN</th>';
        $html .=           '<th>Ticker</th>';
        $html .=           '<th>Orig Size (MM)</th>';
        $html .=           '<th>Reserve Price</th>';
        $html .=           '<th>Bids Recieved</th>';
        $html .=        '</tr>';
        $html .=    '</thead>';
        $html .=    '<tbody>';

        $postData = get_post_meta($auction_ID,"_auction_meta_field_value",true);

        foreach ( $postData as $bond ) {

            $list_id        = get_post_meta($bond,"list_id",true);
            $type           = get_post_meta($bond,"type",true);
            $orig_rating    = get_post_meta($bond,"orig_rating",true);
            $cusip_isin     = get_post_meta($bond,"cusip/isin",true);
            $ticker         = get_post_meta($bond,"ticker",true);
            $orig_size      = get_post_meta($bond,"orig_size_(mm)",true);
            $curr_size      = get_post_meta($bond,"curr_size_(mm)",true);
            $c_e            = get_post_meta($bond,"c/e",true);
            $manager        = get_post_meta($bond,"manager",true);
            $reserve_price  = get_post_meta($bond,"reserve_price",true);

            $findDot    = strpos($orig_size,'.');
            $appendOrig = "";
            if($findDot === false){
                $appendOrig =    $orig_size.".00";
            }else{
                $appendOrig =    $orig_size;
            }
            
            $html .=        '<tr class="warning"  data-toggle="collapse" data-target="#accordion-'.$bond.'" class="clickable">';
            $html .=            '<td>'.$type.'</td>';
            $html .=            '<td>'.$orig_rating.'</td>';
            $html .=            '<td>'.$cusip_isin.'</td>';
            $html .=            '<td>'.$ticker.'</td>';
            $html .=            '<td>'.$appendOrig.'</td>';
            $html .=            '<td>'.$reserve_price.'</td>';
            $html .=            '<td>';
            $html .=                '<button type="button" title="Show Bids" class="btn btn-default" style="padding:0 10px;">';
            $html .=                    '<i class="glyphicon glyphicon-list"></i> Show Bids';
            $html .=                '</button>';
            $html .=            '</td>';
            $html .=        '</tr>';
            $html .=        '<tr id="accordion-'.$bond.'" class="collapse">';
            $html .=            '<td colspan="7">';
            $html .=                '<div>';
            $html .=                    self::getBidUser($auction_ID, $bond);
            $html .=                '</div>';
            $html .=            '</td>';
            $html .=        '</tr>';
        }

        $html .=    '</tbody>';
        $html .= '</table>';

        return $html;

    }


    public static function getBidUser( $auction_ID, $bond_ID ){

        $html = '';
        $html .= '<table class="table-front">';
        $html .=    '<thead>';
        $html .=        '<tr class="success">';
        $html .=           '<th>Bid Price</th>';
        $html .=           '<th></th>';
        $html .=        '</tr>';
        $html .=    '</thead>';
        $html .=    '<tbody>';

        $meta_key       = 'user_placed_bid_'.$auction_ID.'_'.$bond_ID;
        $placedBids     = get_post_meta($auction_ID,$meta_key,true);
        //$meta_key       = "_auction_meta_field_value";
        //$placedBids     = get_post_meta($auction_ID,$meta_key,true);
        $reserve_price  = get_post_meta($bond_ID,"reserve_price",true);
        $user_id        = get_current_user_id();
        $flag           = true;

        /* arrange the order of bids in decreasing order */
        /*foreach ($placedBids as $key => $row) {
            $volume[$key]   = $placedBids[$key][$auction_ID][$bond_ID];
            $edition[$key]  = $placedBids[$key][$auction_ID][$bond_ID];
        }*/
        //array_multisort($volume, SORT_DESC, $edition, SORT_DESC, $placedBids);

     
        foreach ($placedBids as $key => $bid ) {


            if( !isset($placedBids[$key][$auction_ID][$bond_ID]) ){
                
               // continue;
            }

            $flag = false;
            $reserve_price_btn  = '';
            $reserve_price      = get_post_meta($bond_ID,"reserve_price",true);
            
            /* check if reserve price is blank or not */
            if ($reserve_price == "") {
                
                $winner_meta = "user_winning_bid_".$auction_ID."_".$bond_ID;
                $winner_val = get_post_meta($auction_ID,$winner_meta ,true);
                
                if ($winner_val == "") {
                    
                }
                $reserve_price_btn = '<button data-auction="'.$auction_ID.'" data-price="'.$placedBids[$key][$auction_ID][$bond_ID].'" data-bond="'.$bond_ID.'" class="trade_btn btn-default" data-user="'.$key.'" id="trade_'.$auction_ID.'_'.$bond_ID.'">Trade</button>';
            }else{
                $reserve_price_btn = "";
            }
          
            $html .=        '<tr class="warning user_'.$key.'">';
            $html .=            '<td>$'.$placedBids[$key][$auction_ID][$bond_ID].'</td>';
            $html .=            '<td style="text-align:right;">'.$reserve_price_btn.'</td>';
            $html .=        '</tr>';

        }

        if( $flag ) {
            $html .=        '<tr class="warning">';
            $html .=            '<td colspan="4" style="text-align:center"><strong>No Bid Placed.</strong></td>';
            $html .=        '</tr>';
        }

        $html .=    '</tbody>';
        $html .= '</table>';

        return $html;
    }

    function jsScripts(){
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
        
                jQuery(".trade_btn").click(function(){
                    var   auction_id  =  jQuery(this).attr("data-auction");
                    var   bid_price   =  jQuery(this).attr("data-price");
                    var   bon_id      =  jQuery(this).attr("data-bond");
                    var   bid_user    =  jQuery(this).attr("data-user");
                    console.log(auction_id);console.log(bon_id);console.log(bid_price);console.log(bid_user);
                    jQuery.ajax({
                        type: "POST",
                        data : {action:"trade_bid",id:auction_id,bid_price:bid_price,bon_id:bon_id,bid_user:bid_user},
                        url: "/wp-admin/admin-ajax.php",
                        success: function(result){
                            console.log(result);
                            var data    = jQuery.parseJSON(result);
                            var auc_id  = data.auction_id;
                            var bond_id = data.bon_id;
                            var user_id = data.bid_user;
                            //accordion-154576
                            alert('Bond Traded successfully.');
                            jQuery("#accordion-"+bond_id+" td div table tbody").find("tr td").find("button").remove();
                            jQuery("#accordion-"+bond_id+" td div table tbody").find("tr.user_"+user_id).find("td").eq(1).html("<h5>Winner</h5>");
                      }
                    });
                });
            });
        </script>

        <?php
    }

}/* class ends here */

?>