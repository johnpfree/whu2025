<?php
include_once("host.php");
include_once(INCPATH . "template.inc");
include_once(INCPATH . "class.Properties.php");
include_once(INCPATH . "class.ViewBase.php");
include_once(INCPATH . "class.DBBase.php");

include_once("class.Things.php");
include_once("class.Pages.php");
include_once("class.Geo.php");				// after Pages

include_once(INCPATH . "jfdbg.php");
$noDbg = NODBG_DFLT;
if (isset($_GET['dbg'])) 
	$noDbg = !$_GET['dbg'];				// force debugging (or not)

if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'ajax')  $noDbg = TRUE;	// force clean page for ajax

date_default_timezone_set('America/Los_Angeles');		// now required by PHP

// ---------------- Properties Class, to add useful functions -------------

class WhuProps extends Properties
{
	function __construct($props, $over = array())		// do overrides in one call
	{
		parent::__construct(array_merge($props, $over));
	}
	
	static function verboseDate($str)				{	return date("l F j, Y", strtotime($str));	}
	
	function pagetypekey()
	{
		$numargs = func_num_args();
		    echo "Number of arguments: $numargs \n";
		    if ($numargs >= 2) {
		        echo "Second argument is: " . func_get_arg(1) . "\n";
		    }
		    $arg_list = func_get_args();
		    for ($i = 0; $i < $numargs; $i++) {
		        echo "Argument $i is: " . $arg_list[$i] . "\n";
		    }
	}
	function pagetypekeyX($page, $type = NULL, $key = NULL, $id = NULL)
	{
		$this->set('page', $page);
		if ($type != NULL) return;
		
		$this->set('type', $type);
		if ($key == NULL) return;
		
		$this->set('key', $key);
		if ($id == NULL) return;

		$this->set('id', $id);
	}

	static function parseKeys($str)					// just return names
	{
		$ret = array();
		$vals = explode(',', $str);
		for ($i = 0; $i < sizeof($vals); $i++) 
		{
			$val = explode('=', trim($vals[$i]));
			$ret[] = trim($val[0]);
		}
		sort($ret);
		return $ret;
	}
	static function parseParms($str)				// return key/value pairs
	{
		$ret = array();
		$vals = explode(',', $str);
// dumpVar($vals, "explode(', ', $str)");
		for ($i = 0; $i < sizeof($vals); $i++) 
		{
			$val = explode('=', trim($vals[$i]));
			if (sizeof($val) < 2)
				continue;
			$ret[$val[0]] = trim($val[1]);
		}
		return $ret;
	}
	
	function decodeSearchParms($curkey) 	// 0,1,2,3 == home, one, two, many cats
	{
		if (sizeof(explode(',', $curkey)) == 1 && intval($curkey) > 0)	return 1;
		if (sizeof(explode(',', $curkey)) == 2)	return '2g';
		if ($curkey == 'post') {
			$this->checkedCats = $this->hasPrefix("CHK_", true);
			// dumpVar($this->checkedCats, "this->checkedCats");
			// dumpVar(sizeof($this->checkedCats), "sizeof this->checkedCats");
			switch (sizeof($this->checkedCats)) {
				case '1':			return '1p';
				case '2':			return '2p';
				default:			return 3;
			}
		}
		assert(true, "bad search parms");
		return 0;
	}
	
	// collects the so-frequent date(fmt, strtotime(str)) in one place
	function dateFromString($fmt, $str)	{ return date($fmt, strtotime($str)); }	
}

// ---------------- Template Class, for nothing just yet -------------

class WhuTemplate extends VwTemplate
{}

// ---------------- Start Code ---------------------------------------------

$defaults = array(
	'page' => 'home', 
	'type' => 'home', 
	'key'	 =>	'', 
);

$props = new WhuProps($defaults);		// default settings
$props->set($_POST);						// absorb web parms
$props->set($_GET);							// ... but REQUEST has too much junk
$props->dump('props');

$curpage = $props->get('page');
$curtype = $props->get('type');
$curkey  = $props->get('key');

switch ("$curpage$curtype") 
{
	// New stuff =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

	case 'homehome':		$page = new HomeHome($props);			break;		

	case 'tripshome':		$page = new AllTrips($props);				break;
	case 'tripslist':		$page = new SomeTrips($props);	break;
	case 'tlogid':			$page = new OneTripLog($props);		break;		
	case 'logid':				$page = new OneTripDays($props);		break;	
		
	case 'tripid':			$page = new OneTrip($props);		break;		
	case 'daydate':			$page = new OneDay($props);			break;	
	case 'spotid':			$page = new OneSpot($props);			break;	
	case 'mapid':				$page = new OneMap($props);			break;	

	case 'maptype':					$page = new MapSpotType($props);			break;	
	case 'spotstype':				$page = new SpotsListType($props);			break;	
	case 'thumbstype':			$page = new ThumbsListType($props);			break;	

	case 'mapplaceid':			$page = new MapPlaceId($props);			break;	
	case 'thumbsplaceid':		$page = new ThumbsPlaceId($props);			break;	
	case 'spotsplaceid':		$page = new SpotsPlaceId($props);			break;	

	case 'mapspotid':				$page = new MapSpotId($props);			break;	
	case 'thumbsspotid':		$page = new ThumbsSpotId($props);			break;	
	case 'spotsspotid':			$page = new SpotsSpotId($props);			break;	

	case 'maplocation':			$page = new MapLocation($props);	break;	
	case 'thumbslocation':	$page = new ThumbsLocation($props);	break;	
	case 'spotslocation':		$page = new SpotsLocation($props);	break;	

	case 'mapkeyword':			$page = new SpotKeywordsMap($props);			break;	
	case 'thumbskeyword':		$page = new ThumbsKeywords($props);			break;	
	case 'spotskeyword':		$page = new SpotsKeywords($props); break;
	
	case 'searchhome':		$page = new Search($props);					break;
	case 'searchresults':	$page = new SearchResults($props);	break;

	case 'abouthome':		$page = new About($props);					break;	

	// Old stuff =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

	case 'picsid':			$page = new TripPictures($props);		break;	
	case 'picsdate':		$page = new DateGallery($props);		break;
	case 'picscat': {
		switch ($props->decodeSearchParms($props->get('key')))
		{
			case 0: 		$page = new Search($props);							break;
			case '1': 		$page = new CatOneGallery($props);			break;
			case '1p': 	$page = new CatOnePostGallery($props);			break;
			case '2g': 	$page = new CatTwoGetGallery($props);			break;
			case '2p': 	$page = new CatTwoPostGallery($props);			break;
			case 3: 		$page = new CatPostGallery($props);			break;
		}
		break;			
	}
	case 'picid':				// legacy, still used in Wordpress e.g.
	case 'visid':				$page = new OnePhoto($props);		break;	
	case 'vidshome':		$page = new VideoGallery($props);		break;	
	case 'vidsid':			$page = new TripVideos($props);		break;	
	case 'vidid':				$page = new OneVideo($props);		break;	
	case 'vidsdate':		$page = new DateVideos($props);		break;	
	case 'vidscat':			$page = new CatVideos($props);		break;	

	case 'mapdate':			$page = new DateMap($props);			break;	
	
	case 'spotshome':		$page = new SpotsHome($props);			break;
	case 'spotstype':		$page = new SpotsTypes($props);			break;
	case 'spotscamp':		$page = new SpotsCamps($props);			break;		// type of campground (usfs, usnp, state)
	case 'spotsplace':	$page = new SpotsPlaces($props);		break;		// state/region
	

	default: 
		dumpVar("$curpage$curtype", "Unknown page/type:");
		echo "No Page Handler: <b>$curpage$curtype</b>";
		exit;
}
$savepage = $page;									// hack for Wordpress pages
$page->key = $props->get('key');		// just for convenience, everyone needs it

$templates = array("main" => 'container.ihtml', "the_content" => $page->file);
$page->startPage($templates);
$page->preShowPage();
$page->showPage();

if (!is_object($page))
{
	$page = $savepage;
	dumpVar("NOTICE that \$page got f---d up by calling Wordpress.");
}
$page->setCaption();				// set the <title> and other bookkeeping chores
$page->endPage();
?>
