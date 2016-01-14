<?php
defined( 'ABSPATH' ) or die('');
if(class_exists('AffiliateProducts')){ return; }
class AffiliateProducts{
	/*
	* Initializing class and functions
	*/
	public function __construct() {
		global $wpdb, $post;
		add_action('wp_enqueue_scripts', array($this,'add_media_upload_scripts'));

		# Register shortcodes
		add_filter( 'the_content', 'do_shortcode');
		add_action( 'init', array($this,'add_aff_menu') );
		add_action( 'admin_menu', array($this, 'aff_remove_menu_items') );
		add_action('do_meta_boxes', array($this, 'custom_post_type_boxes') );
		add_action('admin_menu', array($this,'register_aff_submenu_page'));
		//add_action( 'admin_init', array($this,'register_initial_data') );
		add_shortcode( 'AffiliateListing', array($this,'AffListing'));
		add_shortcode( 'AffSingleView', array($this,'AffSingleProduct'));
    }

    /*
	* Register and adding scripts
	*/
	public function add_media_upload_scripts() {
		// If Jquery not included already add it now
		if( !wp_script_is('jquery', 'enqueued') ){
			wp_enqueue_script('jquery');
		}

		wp_register_style( 'ap_style', AP_PLUGIN_ASSETS_URL.'/ap_style.css' );
		wp_enqueue_style( 'ap_style' );

		wp_register_script('ap_script', AP_PLUGIN_ASSETS_URL.'/ap_custom.js', '', '1.0.1');
		wp_enqueue_script('ap_script');

		wp_localize_script('apscript', 'apvars', array( 'adminurl' => get_admin_url() ) );
	}

    /*
	* Registering Custom Post and Toxonomy
	*/
	public function add_aff_menu(){
		// Register Custom Post Type for Affiliate Stores
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
		// Register Custom Post Type for Affiliate Products
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

	}

	/*
	* Registering a custom settings page for Affiliate
	*/
	public function register_aff_submenu_page() {
		add_submenu_page('edit.php?post_type=affstore', 'Aff Product | '. __('Global Settings', 'dbem'), __('Global Settings', 'dbem'), 'activate_plugins', 'affproduct-settings', array($this,'ap_general_settings'));

	}

	/*
	* Remove Custom Post Type from admin menu
	*/
	function aff_remove_menu_items() {
		remove_menu_page( 'edit.php?post_type=affproduct' );
	}

	/*
	* Add Meta Box to get site URL
	*/
	public function custom_post_type_boxes(){
		add_meta_box( 'postexcerpt', __( 'Enter site Url to scrape the data' ), 'post_excerpt_meta_box', 'affstore', 'normal', 'high' );
	}

	/*
	* Affiliate settings page Content
	*/
	public function ap_general_settings(){
		$optsinit = get_option('aff_settings_initialize');
		$opts = get_option('aff_settings');

        $isRefProducts = !empty($_POST['ref_products'])?sanitize_text_field($_POST['ref_products']):'';
        if($isRefProducts == 'yes'){
            $run = new AffFns();
            $run->runUpdate();
            echo '<div class="updated settings-error notice" > <p><strong>Products refreshed.</strong></p></div>';
        }
		?>
		<div class="wrap">
			<h2 id="add-new-user"> Affiliate Product Settings</h2>
			<?php settings_errors(); ?>


            <form class="validate" id="ap-settings-initail" name="ap-settings-initial" method="post" action="<?php menu_page_url('affproduct-settings'); ?>">
                <input type="hidden" name="ref_products" value="yes" />
				<?php settings_fields( 'my_options_initial' ); ?>
				<?php submit_button('Refresh Affiliate Products');  ?>
			</form>
		</div>
	<?php
	}

	/*
	* Register Affiliate settings data for Initial
	*/
	public function register_initial_data () {
		//register_setting( 'my_options_initial', 'aff_settings_initialize', array($this,'aff_settings_initial') );
	}

	/*
	* Store Affiliate settings data for Initial
	*/
	/*public function aff_settings_initial($options){
		$options['is_aff_initial'] = sanitize_text_field('yes');

		// Data Grabing
		$run = new AffFns();
		$run->runUpdate();
		return $options;
	}*/

	/*
	* Display Product Listing
	*/
	public function AffListing($atts){
		$pdata = extract(shortcode_atts(array( "pid" => '' ), $atts));
		global $wpdb;
		$str = '';
		$str .= '<div id="blog">';
		$my_query = new WP_Query('post_type=affproduct&posts_per_page=-1');
		$str .= '<div id="aff-listing-wrap" class="post">';
		   $str .= '<h1><a href="'.get_the_permalink().'">'.get_the_title().'</a></h1>';
		   $str .= '<div class="entry">';
		   $str .= '<ul id="aff-listings">';
			if($my_query->have_posts()) :
			 	while($my_query->have_posts()) : $my_query->the_post();
					 $id = get_the_ID();
					 $image = get_post_meta($id,'aff_image', true);
					 $str .= '<li>';
					 if(!empty($image)){
						$str .= '<p><a href = "'.get_the_permalink().'"><img src="'.$image.'" width="128" height="128"></a></p>';
					 }else{
						$str .= '<p><a href = "'.get_the_permalink().'"><img src="'.AP_PLUGIN_ASSETS_URL.'/product-default.png" width="128" height="128"></a></p>';
					 }
					 $str .= '<p><a href = "'.get_the_permalink().'">'.get_the_title().'</a></p>';
					 $str .= '<p><a href = "'.get_the_permalink().'">'.get_post_meta($id,'price', true).'$100.00</a></p>';
					 $str .= '</li>';

				 endwhile;
				 wp_reset_postdata();
				 $str .= '</ul>';
				 $str .= '</div>';
			endif;
		$str .= '</div>';
		$str .= '</div>';

		return $str;

	}

	/*
	* Display Product Listing
	*/
	public function AffSingleProduct($atts){
		global $wpdb;
		$pdata = extract(shortcode_atts(array( "pid" => '' ), $atts));
        $pid = !empty($pid)?$pid:get_the_ID();

		$str = '';
		$str .= '<div id="blog">';
		$my_query = new WP_Query('post_type=affproduct&p='.$pid.'&posts_per_page=-1');
		if($my_query->have_posts()) :
			 while($my_query->have_posts()) : $my_query->the_post();
				  $str .= '<div class="post" id="aff-listing-wrap">';
					   $str .= '<h1><a href="'.get_the_permalink().'">'.get_the_title().'</a></h1>';
					   $str .= '<div class="entry">';
					   //$str .= get_the_content();
					   $str .= '<ul id="aff-listings">';
					   		$id = get_the_ID();
							$image = get_post_meta($id,'aff_image', true);

							 $str .= '<li>';
							 if(!empty($image)){
								$str .= '<p><a href = "'.get_the_permalink().'"><img src="'.$image.'" ></a></p>';
							 }else{
								$str .= '<p><a href = "'.get_the_permalink().'"><img src="'.AP_PLUGIN_ASSETS_URL.'/product-default.png"></a></p>';
							 }
							 $str .= '<p><a href = "'.get_the_permalink().'">'.get_the_title().'</a></p>';
							 $str .= '<p><a href = "'.get_the_permalink().'">'.get_post_meta($id,'price', true).'$100.00</a></p>';
							 $str .= '</li>';
							 $str .= '</ul>';

							$str .= '<h3>Product Price Comparision</h3>';
							$str .= '<table>';
							$query = "SELECT * FROM ".$wpdb->prefix."affiliate_products where product_id=$id";
							$pageposts = $wpdb->get_results($query, OBJECT);
						    foreach($pageposts as $cps) :
								$query = "SELECT * FROM ".$wpdb->prefix."posts where post_type='affstore' and ID=".$cps->store_id;
								$store = $wpdb->get_row($query, OBJECT);

								 $str .= '<tr>';
									 $str .= '<td><a target="_blank" href = "'.$store->post_excerpt.'">'.$store->post_title.'</a></td>';
									 $str .= '<td>'.$cps->product_price.' DKK</td>';
									 $str .= '<td><a target="_blank" href = "'.$cps->product_url.'"><button class="">Go to Store >></button></a></td>';
								 $str .= '</tr>';

							endforeach;
							$str .= '</table>';

					  $str .= '</div>';
				  $str .= '</div>';
			 endwhile;
			 wp_reset_postdata();
		endif;
		$str .= '</div>';

		return $str;

	}

}

?>