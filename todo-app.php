<?php
/**
 * Plugin Name: ToDo App
 * Description: Custom Plugin for my ToDo App
 * Version: 0.1
 * Author: Daron Spence
 */

// Register Custom Post Type
function register_todo_cpt() {

	$labels = array(
		'name'                => _x( 'ToDo Items', 'Post Type General Name', 'todo' ),
		'singular_name'       => _x( 'ToDo', 'Post Type Singular Name', 'todo' ),
		'menu_name'           => __( 'ToDo', 'todo' ),
		'name_admin_bar'      => __( 'ToDo', 'todo' ),
		'parent_item_colon'   => __( 'Parent ToDo', 'todo' ),
		'all_items'           => __( 'All ToDos', 'todo' ),
		'add_new_item'        => __( 'Add New ToDo', 'todo' ),
		'add_new'             => __( 'Add New', 'todo' ),
		'new_item'            => __( 'New ToDo', 'todo' ),
		'edit_item'           => __( 'Edit ToDo', 'todo' ),
		'update_item'         => __( 'Update ToDo', 'todo' ),
		'view_item'           => __( 'View ToDo', 'todo' ),
		'search_items'        => __( 'Search ToDo', 'todo' ),
		'not_found'           => __( 'Not found', 'todo' ),
		'not_found_in_trash'  => __( 'No ToDos found in Trash', 'todo' ),
	);
	$args = array(
		'label'               => __( 'todos', 'todo' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'author', ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'		  => true,
		'menu_position'       => 25,
		'menu_icon'           => 'dashicons-editor-ul',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,		
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
	);
	register_post_type( 'todos', $args );

}

// Hook into the 'init' action
add_action( 'init', 'register_todo_cpt', 0 );

add_action( 'init', function() {

	if ( is_user_logged_in() ){

		if ( ! is_admin() ) {
		  
			show_admin_bar( false );

		} elseif ( ! current_user_can('administrator') && is_admin() ){
			
			wp_redirect( home_url() );

			exit();

		}

	}

});

add_action( 'login_head', function(){ ?>

	<style>
		body {
			background: url( '<?php echo get_template_directory_uri(); ?>/get-it-done.png' );
			background-size: cover;
		}
		.login #backtoblog a, .login #nav a {
			color: black;
		}
		.login h1 a {
			background: none;
			text-indent: 0;
			text-transform: uppercase;
			color: white;
			font-size: 3em;
			width: auto;
		}
	</style>

<?php });

add_filter( 'login_headerurl', function( $url ){
	
	$url = home_url();

	return $url;
});


add_action( 'wp_loaded', function(){

	if ( is_admin() && ! current_user_can( 'manage_options' ) ) {
		wp_redirect( home_url() );
	}

//	var_dump( $_POST );

	if ( ! empty( $_POST['todo_item_add'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-item' ) )
			die('Cheatin huh?');

		$item = esc_html( $_POST['todo_item_add'] );
		$author = get_current_user_id();
		$device = ! empty( $_POST['device'] ) ? esc_html( $_POST['device'] ) : false ;

		$new_todo = wp_insert_post( array( 'post_type' => 'todos', 'post_author' => $author, 'post_title' => $item, 'post_status' => 'publish' ), true );

		if ( is_wp_error( $new_todo ) )
			die( 'failed' );

		$new_todo = get_post( $new_todo );

		die( json_encode( array( 'item' => $item, 'author' => $author, 'id' => $new_todo->ID, 'device' => $device ) ) );
	}

	if ( ! empty( $_POST['todo_item_delete'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-item' ) )
			die('Cheatin huh?');

		$todo = intval( $_POST['todo_item_delete'] );
		$author = esc_html($_POST['author']);

		$todo = get_post( $todo );

		if ( $todo->post_author === $author ){
			wp_delete_post( $todo->ID );
			die( 'success' );
		}

		die( "error" );

	}

	if ( ! empty( $_POST['todo_item_finished'] ) ) {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-item' ) )
			die('Cheatin huh?');

		$todo = intval( $_POST['todo_item_finished'] );
		$author = esc_html($_POST['author']);

		$todo = get_post( $todo );

		if ( $todo->post_author !== $author ) {
			die( 'invalid author' );
		}

		$todo = $todo->ID;

		if ( $_POST['finished'] === 'true' ) {
			// mark complete
			wp_update_post( array( 'ID' => $todo, 'comment_status' => 'closed' ) );
			die( 'success' );
		} elseif ( $_POST['finished'] === 'false' ) {
			//mark incomplete
			wp_update_post( array( 'ID' => $todo, 'comment_status' => 'open' ) );
			die( 'success' );
		}
		
		die( "error" );

	}

	if ( ! empty( $_POST['todo_item_edit'] ) ){

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-item' ) )
			die('Cheatin huh?');

		$todo = intval( $_POST['todo_item_edit'] );
		$author = esc_html($_POST['author']);
		$title = esc_html( $_POST['value'] );

		$todo = get_post( $todo );

		if ( $todo->post_author === $author ){
			wp_update_post( array( 'ID' => $todo->ID, 'post_title' => $title ) );
			die( 'success' );
		}

	}

	if ( ! empty( $_POST['updateUsername'] ) ){

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-item' ) )
			die('Cheatin huh?');

		$author = get_current_user_id();
		$new_nickname = esc_html( $_POST['updateUsername'] );

		wp_update_user( array( 'ID' =>$author, 'nickname' => $new_nickname ) );

		die('success');

	}

	if ( ! empty( $_POST['invite_friend'] ) && ( $_POST['invite_friend'] === 'true' ) ){

		add_filter( 'mandrill_payload', function( $payload ){

			# $payload['template']['content'] # Multi-dimensional array of MC tags https://mandrill.zendesk.com/hc/en-us/articles/205582497

			return $payload;

		} );

		$email = sanitize_email( $_POST['email'] );

		$sender = get_userdata( intval( $_POST['author'] ) );

		$sender = "{$sender->data->display_name} ({$sender->data->user_email})";

		$success = wp_mail( $email, "You've been invited!", $sender . " invited you." );

		if ( $success ) {
			die('success');	
		} else {
			die('error');	
		}

	}

});