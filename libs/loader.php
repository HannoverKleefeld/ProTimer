<?
if(!defined('DEBUG_LOADER'))define('DEBUG_LOADER',false);
if(!defined('LIB_PHP_VERSION'))define('LIB_PHP_VERSION','');

if(!function_exists('LIB_ProJetAutoLoader')){
	
	DEFINE('LIB_PROJET_INCLUDE_DIR',__DIR__);
	function LIB_ProJetAutoLoader($class){
// 		$class=strtolower($class);
		$file =LIB_PROJET_INCLUDE_DIR . "/$class.class.php";
		if(is_file($file)&&!class_exists($class)){ include $file; return true;}
		
		
		return false;
	}
	spl_autoload_register('LIB_ProJetAutoLoader');	 
}
 
?>