<?php
defined( 'ABSPATH' ) or die('');
if(class_exists('AffFns')){ return; }
class AffFns{
    private static $logFile = '';
    private static $templateDetails = array();
    private static $arrDetails = array();
	
	/*
	* Activate the Plugins
	*/
    public static function activatePlugin(){        
        self::installSql();       
    }

	/*
	* Install SQL Queries during plugin installation
	*/
    private static function installSql(){
        global $wpdb;        
        
		$sql = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."affiliate_products` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `product_name` varchar(255) DEFAULT NULL,
		  `product_price` varchar(255) DEFAULT NULL,
		  `product_url` text,
		  `product_images` longtext,
		  `product_id` bigint(20) DEFAULT NULL,
		  `product_json` longtext,
		  `store_id` bigint(20) DEFAULT NULL,		  
		  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `modified` datetime DEFAULT NULL,		  
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 ;";
		
		$wpdb->query($sql);
    }
	
	/*
	* Remove affiliate data during uninstallation
	*/
    public static function deactivatePlugin(){
		global $wpdb; 
       // $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}affiliate_products" );
    }
	
	/*
	* Get all stores 
	*/
	public function getAllStores($args = array()){
		global $wpdb;
		global $post;
		$args = array(
			'post_type'        => 'affstore',
			'post_status'      => 'publish'
		);
		$posts_array = get_posts( $args);
		return $posts_array;
	}

	/*
	* Insert or Update the property with unique method
	*/
	public function insertOrUpdateProperty($args = array(), $is_debug= false){
		global $wpdb;		
		if(!empty($args) && is_array($args) && isset($args['title']) && !empty($args['title']) && isset($args['store_id']) && !empty($args['store_id'])){			
			$posttitle = strtolower($args['title']);			
			$posttitle = explode(' ',$posttitle);
			if(is_array($posttitle)){
				asort($posttitle);
				$posttitle = implode(' ',$posttitle);
			}
			
			$querystr = "
			SELECT $wpdb->posts.* 
				FROM $wpdb->posts, $wpdb->postmeta
				WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
				AND $wpdb->postmeta.meta_key = 'af_sort_title' 
				AND $wpdb->postmeta.meta_value = '".$posttitle."' 
				AND $wpdb->posts.post_status = 'publish' 
				AND $wpdb->posts.post_type = 'affstore'
				ORDER BY $wpdb->posts.post_date DESC
			";
			$existing_post = $wpdb->get_row($querystr, ARRAY_A);
			$postid = 0;
			if(!empty($existing_post)){				
				$postid = $existing_post['ID'];		
			}else{
				// Create post object
				$product_post = array(
					'post_type'     => 'affstore',
					'post_title'    => $args['title'],
					'post_status'   => 'publish'
				);
				// Insert the post into the database
				$postid = wp_insert_post( $product_post );	
				update_post_meta($postid,'af_sort_title',$posttitle);
			}
			if($postid){				
				$query = "
				SELECT ".$wpdb->prefix."affiliate_products.* 
					FROM ".$wpdb->prefix."affiliate_products
					WHERE ".$wpdb->prefix."affiliate_products.product_id = ".$postid." 
					AND ".$wpdb->prefix."affiliate_products.store_id = ".$args['store_id'];
				$existing_product = $wpdb->get_row($query, ARRAY_A);
				if(!empty($existing_product)){				
					// Update Store product
					$ups = $wpdb->update(
							$wpdb->prefix.'affiliate_products',
							array(
								'product_name' => $args['title'],
								'product_price' => $args['price'],
								'product_url' => $args['url'],
								'product_images' => $args['image'],								
								'product_json' => json_encode($args),
								'modified' => date('Y-m-d H:i:s'),
								'product_id' => $postid,
								'store_id' => $args['store_id']
							),
							array(
								'id'=>$existing_product['id']
							),							
							array(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%d',
								'%d'
							),
							array(
								'%d'
							)
						);
					if($ups){
						return true;
					}					
				}else{
					// Create Store product
					$ins = $wpdb->insert(
							$wpdb->prefix.'affiliate_products',
							array(
								'product_name' => $args['title'],
								'product_price' => $args['price'],
								'product_url' => $args['url'],
								'product_images' => $args['image'],								
								'product_json' => json_encode($args),
								'created' => date('Y-m-d H:i:s'),
								'product_id' => $postid,
								'store_id' => $args['store_id']
							),
							array(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%d',
								'%d'
							)
						);
					if($ins){
						return true;
					}	
				}				
			}
		}
		return false;		
	}
    
}

?>