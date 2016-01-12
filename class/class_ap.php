<?php
if(class_exists('AffiliateProducts')){ return; }
class AffiliateProducts{	
	
	public function __construct() {	
		global $wpdb, $post;												
		add_action('wp_enqueue_scripts', array($this,'add_media_upload_scripts'));						

		# Register shortcodes	
		add_filter( 'the_content', 'do_shortcode');	
		add_action( 'init', array($this,'add_aff_menu') );	
		add_action('admin_menu', array($this,'register_aff_submenu_page'));   
		add_action( 'admin_init', array($this,'register_my_setting') );     
    }
   
	public function add_media_upload_scripts() {    
		// If Jquery not included already add it now		
		if( !wp_script_is('jquery', 'enqueued') ){
			wp_enqueue_script('jquery');
		}
		
		wp_register_style( 'ap_style', AP_PLUGIN_ASSETS_URL.'/css/ap_style.css' );
		wp_enqueue_style( 'ap_style' );
		
		wp_register_script('ap_script', AP_PLUGIN_ASSETS_URL.'/js/ap_custom.js', '', '1.0.1');
		wp_enqueue_script('ap_script');		

		wp_localize_script('apscript', 'apvars', array( 'adminurl' => get_admin_url() ) );
	}

    # Menu
	public function add_aff_menu(){		
		
		register_post_type(	'affstore', 
					array(	'label' 			=> __('Aff Store'),
							'labels' 			=> array(	'name' 					=> __('Aff Store'),
															'singular_name' 		=> __('Aff Store'),
															'add_new' 				=> __('Add Aff Store'),
															'add_new_item' 			=> __('Add New Aff Store'),
															'edit' 					=> __('Edit'),
															'edit_item' 			=> __('Edit Aff Store'),
															'new_item' 				=> __('New Aff Store'),
															'view_item'				=> __('View Aff Store'),
															'search_items' 			=> __('Search Aff Store'),
															'not_found' 			=> __('No Aff Store found'),
															'not_found_in_trash' 	=> __('No Aff Store found in trash')	),
							'public' 			=> true,
							'can_export'		=> true,
							'show_ui' 			=> true, // UI in admin panel
							'_builtin' 			=> false, // It's a custom post type, not built in
							'_edit_link' 		=> 'post.php?post=%d',
							'capability_type' 	=> 'post',
							//'menu_position'     => 25,
							//'menu_icon' 		=> get_bloginfo('template_url').'/images/favicon.ico',
							'hierarchical' 		=> false,
							'rewrite' 			=> array(	"slug" => "affstore"	), // Permalinks
							//'rewrite' 			=> false,
							'query_var' 		=> "affstore", // This goes to the WP_Query schema
							'supports' 			=> array(	'title',															 
															'excerpt',																														
															'revisions') ,
							'show_in_nav_menus'	=> true ,
							'taxonomies'		=> array('affstore')
						)
					);
					
		register_post_type(	'affproduct', 
					array(	'label' 			=> __('Aff Product'),
							'labels' 			=> array(	'name' 					=> __('Aff Product'),
															'singular_name' 		=> __('Aff Product'),
															'add_new' 				=> __('Add Aff Product'),
															'add_new_item' 			=> __('Add New Aff Product'),
															'edit' 					=> __('Edit'),
															'edit_item' 			=> __('Edit Aff Product'),
															'new_item' 				=> __('New Aff Product'),
															'view_item'				=> __('View Aff Product'),
															'search_items' 			=> __('Search Aff Product'),
															'not_found' 			=> __('No Aff Product found'),
															'not_found_in_trash' 	=> __('No Aff Product found in trash')	),
							'public' 			=> true,
							'can_export'		=> true,
							'show_ui' 			=> true, // UI in admin panel
							'_builtin' 			=> false, // It's a custom post type, not built in
							'_edit_link' 		=> 'post.php?post=%d',
							'capability_type' 	=> 'post',
							//'menu_position'     => 25,
							//'menu_icon' 		=> get_bloginfo('template_url').'/images/favicon.ico',
							'hierarchical' 		=> false,
							'rewrite' 			=> array(	"slug" => "affproduct"	), // Permalinks
							//'rewrite' 			=> false,
							'query_var' 		=> "affproduct", // This goes to the WP_Query schema
							'supports' 			=> array(	'title',
															'author', 
															'excerpt',
															'thumbnail',
															'comments',
															'editor', 
															'trackbacks',
															'custom-fields',
															'revisions') ,
							'show_in_nav_menus'	=> true ,
							'taxonomies'		=> array('affproduct')
						)
					);
										
					register_taxonomy(	"affcategory", 
									array(	"affproduct"	), 
									array (	"hierarchical" 		=> true, 
											"label" 			=> "Aff Product Category", 
											'labels' 			=> array(	'name' 				=> __('Aff Product Categories'),
																			'singular_name' 	=> __('Aff Product Category'),
																			'search_items' 		=> __('Search Aff Products'),
																			'popular_items' 	=> __('Popular Aff Product Categories'),
																			'all_items' 		=> __('All Aff Product Categories'),
																			'parent_item' 		=> __('Parent Aff Product Category'),
																			'parent_item_colon' => __('Parent Aff Product Category:'),
																			'edit_item' 		=> __('Edit Aff Product Category'),
																			'update_item'		=> __('Update Aff Product Category'),
																			'add_new_item' 		=> __('Add New Aff Product Category'),
																			'new_item_name' 	=> __('New Aff Product Category Name')	), 
											'public' 			=> true,
											'show_ui' 			=> true,
											'show_admin_column' => true,
											'rewrite' => true)
									);							
	}		

	public function register_aff_submenu_page() {
		add_submenu_page('edit.php?post_type=affstore', 'Aff Product | '. __('Global Settings', 'dbem'), __('Global Settings', 'dbem'), 'activate_plugins', 'affproduct-settings', array($this,'ap_general_settings'));	
		
	}
	
	public function ap_general_settings(){
		$opts = get_option('aff_settings'); 
		?>
		<div class="wrap">
			<h2 id="add-new-user"> Affiliate Product Settings</h2>
			<?php settings_errors(); ?> 
			<form class="validate" id="ap-settings" name="ap-settings" method="post" action="<?php echo admin_url(); ?>options.php">
				<?php settings_fields( 'my_options_aff' ); ?>            
				<table class="form-table">
					<tbody>  
                    <tr class="form-field form-required">
						<th scope="row"><label for="pt-color">Test </label></th>
						<td><div class="form-item">
							<input type="text" id="pt-image-height" name="pt-image-height" value="<?php echo esc_attr($opts['pt-image-height']); ?>" />px
							</div>
						</td>
					</tr>              					
					                          
					</tbody>
				</table>
				<?php submit_button();  ?>			
			</form>
			
		</div>
	<?php 	
	}
	
	public function register_my_setting() {
		register_setting( 'my_options_aff', 'aff_settings', array($this,'aff_settings_options') ); 
	} 
		
	public function aff_settings_options($options){		
		$options['pt-image-height'] = sanitize_text_field( (isset($_POST['pt-image-height'])) ? $_POST['pt-image-height'] : '' );		
		
		return $options;		
	}
	
	

}

?>