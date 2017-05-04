<?php
ob_start();
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class StockList extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Site', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Sites', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		) );
	}


	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_customers( $per_page = 10, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}stocks";

		if ( ! empty( $_REQUEST['s'] ) ) {
			$sql .= ' WHERE stock_name LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `stock_id` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `ticker` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `exchange_name` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `currency` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\'' ;
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}else{
			// $sql .= ' ORDER BY site_name ';
			$sql .= ' ORDER BY stock_name DESC ';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		//print_r($result);
		return $result;
	}

	/**
	* Delete a customer record.
	*
	* @param int $id customer ID
	*/
	public static function delete_customer( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}stocks",
			array( 'stock_id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}stocks";

		if ( ! empty( $_REQUEST['s'] ) ) {
			$sql .= ' WHERE stock_name LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `stock_id` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `ticker` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `exchange_name` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\' 
			OR `currency` LIKE \'%' . esc_sql( $_REQUEST['s'] ) .  '%\'' ;
		}

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No Stocks Found.', 'sp' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'stock_id':
				return $item[ 'stock_id' ];
			case 'stock_name':
				return $item[ 'stock_name' ];
			case 'ticker':
				return $item[ 'ticker' ];
			case 'exchange_name':
				return $item[ 'exchange_name' ];
			case 'currency':
				return $item[ 'currency' ];
			case 'created_date':
				return $item[ 'created_date' ];
			case 'actions':
				$deleteMesage 		= 	"'Are you sure you want to DELETE Stock?'";
				$deletUrl 			=	$_SERVER['REQUEST_URI']."&action=delete&id=".$item['stock_id'];
				$stock_id 			= 	$item['stock_id'];
				//$SiteUrlAdmin 		=	$item['site_url']."wp-login.php";

				//return '<a href="'.$deletUrl.'"  onclick="return confirm('.$deleteMesage.')" style="color: red;" class="button" title="Delete this Blog"><b>Delete</b></a>';
				return '<button name="stock-del" type="button" value="'.$stock_id.'" onclick="return confirm('.$deleteMesage.')" style="color: red;" class="button stock-del" title="Delete this Stock"><b>DELETE</b></button>';

			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		//return sprintf( $item['stock_id'] );
		return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['stock_id']); 
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_stock_name( $item ) {

		$delete_nonce = wp_create_nonce( 'sp_delete_customer' );

		$title = '<strong>' . $item['stock_name'] . '</strong>';

		$actions = array(
			'edit'=>sprintf('<a href="?page=%s&id=%s">Edit</a>','edit_stock', absint( $item['stock_id']) ),
			'delete' => sprintf( '<a href="?page=%s&action=%s&customer=%s&_wpnonce=%s">Trash</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['stock_id'] ), $delete_nonce )
		);

		return $title . $this->row_actions( $actions );
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb' 			=> '<input type="checkbox" />',
			'stock_id'      => __( 'Stock ID', 'sp' ),
			'stock_name'    => __( 'Stock Name', 'sp' ),
			'ticker'   		=> __( 'Ticker', 'sp' ),
			'exchange_name' => __( 'Exchange Name', 'sp' ),
			'currency' 		=> __( 'Currency', 'sp' ),
			//'created_date' 	=> __( 'Stock Price', 'sp' ),
			//'actions'       => __( 'Actions', 'sp' )
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'stock_id'     	=> array( 'stock_id', true ),
			'stock_name'   	=> array( 'stock_name', true ),
			'ticker' 		=> array( 'ticker', false ),
			'exchange_name' => array( 'exchange_name', false ),
			'currency' => array( 'currency', false ),
			//'exchange_name' => array( 'exchange_name', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		 $actions = [
		 	'bulk-delete' => 'Delete',
		 ];

		 return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$columns = $this->get_columns();
		$hidden =  get_hidden_columns( $this->screen );
  		$sortable = $this->get_sortable_columns();
  		$this->_column_headers = array($columns, $hidden, $sortable);
  		$this->_column_headers = $this->get_column_info();
		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'stocks_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $per_page)
		));

		$this->items = self::get_customers( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...

		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
			
			if ( ! wp_verify_nonce( $nonce, 'sp_delete_customer' ) ) {
				// die( 'Go get a life script kiddies' );
				// require dirname(__FILE__)."/functions/delete_site.php";
				echo("Deleted Successfully!");
			}
			else {
				self::delete_customer( absint( $_GET['customer'] ) );
				ob_clean();
				wp_redirect(esc_url(add_query_arg()));
				exit();
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_customer( $id );

			}

			wp_redirect( esc_url( add_query_arg() ) );
			exit;
		}
	}
}
?>