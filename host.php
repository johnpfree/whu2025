<?php
define("HOST", "jfmac");

$eold = error_reporting(E_ALL);	//E_ERROR);
// $str = sprintf("old->new %X->%X strict=%X all=%X error=%X", $eold, $enew, E_STRICT, E_ALL, E_ERROR);
// dumpVar($str, "xxxx");

define("NODBG_DFLT", 0);

define('INCPATH', "/Users/jf/Sites/include/");

define('iPhotoPATH', "/Users/jf/Sites/cloudyhands.com/pix/iPhoto/");
define('iPhotoURL', "../cloudyhands.com/");

define('PICPATH', 'http://jfmac.local/~jf/cloudyhands.com/pix/');

define('REL_PICPATH', "../cloudyhands.com/");
define('REL_PICPATH_WF', "../cloudyhands.com/");
// define('RAWPICPATH', 'galleries');		// 'pc091020'

define('WF_DB', 'whu40');

define('GOOAPIKEY', 'AIzaSyDZfqpTYoilzwPWrJzlIsdKMh3X43pdXeQ');
// define('GOOAPIKEY', 'ABQIAAAAlIXDdyC97EmuJeZHm9CxgBS_SpYYKkgqa-wX1jPJw-rXk3e4OhRlQoodb26v8cEDzVCEIuzs4UIdyQ');
define('WUNDERGROUND', '1d24ab90ef8f01c8');

// mapbox
define('MAPBOX_TOKEN', 'pk.eyJ1Ijoiam9obnBmcmVlIiwiYSI6ImNpajF5OGk2YjAwY3J1OGx3N3hyNjFvNjUifQ.L8lYX2iaC1iXYY1UXOntzw');
define('MAP_DATA_PATH', "/Users/jf/Sites/whufu/data/");

class DBHost extends mysqli
{
	var $dsn = array(
	    'phptype'  => 'mysql',
	    'username' => 'jf',
	    'password' => 'trailview',
	    'hostspec' => '127.0.0.1',
	    // 'hostspec' => 'localhost',
	    'database' => 'NULL'
	);
}
class DbWpData
{
	var $tablepref = 'wp_';
	var $dsn = array(
	    'database' => 'wptest',
	);
	function tablepref()	{ return $this->tablepref;}
	function dsn()				{ return $this->dsn;}
}

define("WP_DATABASE", 'wptest');
define("WP_PATH", '../wptest');

?>
