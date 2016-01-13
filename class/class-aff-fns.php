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
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}affiliate_products" );
    }
    
}

?>