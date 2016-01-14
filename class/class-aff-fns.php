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
				AND $wpdb->posts.post_type = 'affproduct'
				ORDER BY $wpdb->posts.post_date DESC
			";
			$existing_post = $wpdb->get_row($querystr, ARRAY_A);
			$postid = 0;
			if(!empty($existing_post)){				
				$postid = $existing_post['ID'];
                $existing_price = get_post_meta($postid,'aff_price');
                if($existing_price > $args['price']){
                    update_post_meta($postid,'aff_price',$args['price']);
                }
			}else{
				// Create post object
				$product_post = array(
					'post_type'     => 'affproduct',
					'post_title'    => $args['title'],
					'post_content'    => "[AffSingleView]",
					'post_status'   => 'publish'
				);
				// Insert the post into the database
				$postid = wp_insert_post( $product_post );	
				update_post_meta($postid,'af_sort_title',$posttitle);
				update_post_meta($postid,'aff_image',$args['image']);
				update_post_meta($postid,'aff_price',$args['price']);
			}
			if($postid){
				update_post_meta($postid,'aff_image',$args['image']);
				update_post_meta($postid,'aff_price',$args['price']);
				
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
    /*
	* runUpdate 
	*/
	public function runUpdate(){
		
		$stores = $this->getAllStores();
		if(isset($stores) && !empty($stores)){
			foreach($stores as $store){
				if(isset($store->post_excerpt) && !empty($store->post_excerpt)){
					
					$store_id = $store->ID;
					$siteUrl = $store->post_excerpt;
					$domainArray = parse_url($siteUrl);
					$domainName = $domainArray['host'];
					$html = file_get_html($siteUrl);
					if($domainName == 'legen.dk' || $domainName == 'http://legen.dk' || $domainName == 'https://legen.dk' || $domainName == 'www.legen.dk' || $domainName == 'http://www.legen.dk' || $domainName == 'https://www.legen.dk'){
						$domainName = 'http://www.legen.dk';
						$productHtmls = $html->find('.ProductList_Custom_TBL tr');
						if(!empty($productHtmls)){
							foreach($productHtmls as $rows){
								foreach($rows->find('.produktboks') as $singleProduct){

									$productLink = $domainName.$singleProduct->find('tr td a', 0)->href;

									$productImage = $domainName.$singleProduct->find('tr td a img', 0)->src;

									$title = $singleProduct->find('tr td h4 u a', 0)->innertext;

									$mrp = $singleProduct->find('tr td strike', 0)->innertext;

									$price = $singleProduct->find('font span[itemprop=price]', 0)->innertext;

									$productId= intval(preg_replace('/[^0-9]+/', '', $title), 10);

									$product = array();
									$product['id']= $productId;
									$product['original_title']= $title;								
									$title = htmlentities($title, ENT_QUOTES, 'UTF-8');
									$replaceArray = array('-',',','.', '  ');
									$replaceTo = array('','','', ' ');
									$title = str_replace($replaceArray, $replaceTo,$title);
									
									$product['title']= $title;
									$product['url']= $productLink;
									$product['image']= $productImage;
									$product['mrp']= $mrp;
									$product['price']= $price;
									$product['store_id']= $store_id;
									
									$result = $this->insertOrUpdateProperty($product);
								}
							}
						}
					}
					if($domainName == 'legekaeden.dk' || $domainName == 'http://legekaeden.dk' || $domainName == 'https://legekaeden.dk' || $domainName == 'www.legekaeden.dk' || $domainName == 'http://www.legekaeden.dk' || $domainName == 'https://www.legekaeden.dk'){
						$domainName = 'http://www.legekaeden.dk';
						$html = file_get_html($siteUrl);

						$productHtmls = $html->find('#productsearchresult li');
						if(!empty($productHtmls)){
							foreach($productHtmls as $rows){

								$title = $rows->find('.product-name', 0)->innertext;
								$productId = intval(preg_replace('/[^0-9]+/', '', $title), 10);
								$price = $rows->find('.price-box', 0)->innertext;
								$productImage = $domainName.$rows->find('figure img', 0)->getAttribute('data-src');
								$productLink = $domainName.$rows->find('.link-product-page', 0)->href;
								
								$product = array();
								$product['id']= $productId;								
								$product['original_title']= $title;								
								$title = htmlentities($title, ENT_QUOTES, 'UTF-8');
								$replaceArray = array('-',',','.', '  ');
								$replaceTo = array('','','', ' ');
								$title = str_replace($replaceArray, $replaceTo,$title);

								$product['title']= $title;
								$product['url']= $productLink;
								$product['image']= $productImage;
								$product['price']= $price;
								$product['store_id']= $store_id;
									
								$result = $this->insertOrUpdateProperty($product);							
							}
						}
					}
				}
			}
		}
	}
}

?>