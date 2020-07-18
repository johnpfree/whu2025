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
	
	function pagetypekey($page, $type = NULL, $key = NULL, $id = NULL)
	{
		$this->set('page', $page);
		if ($type == NULL) return;
		
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
	// collects the so-frequent date(fmt, strtotime(str)) in one place
	function dateFromString($fmt, $str)		{ 		return date($fmt, strtotime($str)); 		}	
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

$curpage = $props->get('page');
$curtype = $props->get('type');
$curkey  = $props->get('key');

// do some redirecting for Ajax early before anything is written to page
//
if ($curpage == 'ajax') {
	$page = new HomeHome($props);							// need a simple page object to run build()
	switch ("$curtype$curkey") 
	{
		case 'searchSpLoc':			$ajax = new SpotLocation();	echo $ajax->result($page);	exit;
		case 'searchSpType':		$ajax = new SpotType();			echo $ajax->result($page);	exit;
		case 'searchSpKey':			$ajax = new SpotKey();			echo $ajax->result($page);	exit;
		case 'searchPicPlace':	$ajax = new PicPlace();			echo $ajax->result($page);	exit;
		case 'searchPicCat':		$ajax = new PicCat();				echo $ajax->result($page);	exit;
	}
	jfDie("unknown ajax key-$key");
}

$props->dump('props');
// grab form requests and package them for the factory below
if ($props->isProp('do_text_search'))	{				// text search
	$props->pagetypekey('results', 'text', $props->get('search_text'));
}
else if ($props->isProp('comment_form')) 		// comment form
{
	/* July2017 - I have left the math thing in the form, but am now ignoring it in favor of the hidden field trick. 
		The field has name="email", and if it contains stuff I kill the program. Hope this works.
	Aug 2017 - didn't work. Juggling names: former real names are now dummy names, they all have value...
	*/
	if ($props->get('email') != '.')
		exit;
	if ($props->get('f_email') != '.')
		exit;
	if ($props->get('f_name') != '.')
		exit;
	// if ($props->get('cap') == $props->get('user_id'))			// ignore if the math isn't correct
	{
		$savecmt = new SaveForm($props);
		$savecmt->write($_REQUEST, 'cloudy');	
	}
	$props->set('type', 'thx');
}
else if ($props->isProp('search_near_spot')) {		// form has the correct parms as hidden data, nothing to do here
}
else if ($props->isProp('search_near_loc')) {	
	$props->pagetypekey('map', 'near', 'location');	
}
else if ($props->isProp('search_places')) {	
	$props->pagetypekey('map', 'near', $props->get('search_radius'));	
}
else if ($props->isProp('search_types')) {	
	$props->pagetypekey('map', 'near', $props->get('search_types'));	
}

$curpage = $props->get('page');	// again! in case the blocks above modified them
$curtype = $props->get('type');

switch ("$curpage$curtype") 
{
	// New stuff =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

	case 'homehome':		$page = new HomeHome($props);			break;		

	case 'tripid':			$page = new OneTrip($props);		break;		
	case 'spotid':			$page = new OneSpot($props);			break;	
	case 'tripshome':		$page = new AllTrips($props);				break;

	case 'spotslist':		$page = new SpotsList($props);			break;
	// case 'spotslist':		{
	// 	switch ($props->get('key')) {
	// 		case 'NWR':			$page = new SpotsNWR($props);			break;
	// 		case 'HOTSPR':	$page = new SpotsHOTSPR($props);			break;
	// 		case 'CAMP':		$page = new SpotsCamp($props);			break;
	// 	}
	// }
	
	case 'logid':				$page = new OneTripLog($props);		break;		
	case 'tlogid':			$page = new TestNewTripLog($props);		break;		
	case 'daydate':			$page = new OneDay($props);			break;	
	
	case 'searchhome':	$page = new Search($props);					break;
	case 'abouthome':		$page = new About($props);					break;	

	// Old stuff =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

	case 'picsid':			$page = new TripPictures($props);		break;	
	case 'picsdate':		$page = new DateGallery($props);		break;
	case 'picscat':			$page = new CatGallery($props);		break;	
	case 'picid':				// legacy, still used in Wordpress e.g.
	case 'visid':				$page = new OnePhoto($props);		break;	
	case 'vidshome':		$page = new VideoGallery($props);		break;	
	case 'vidsid':			$page = new TripVideos($props);		break;	
	case 'vidid':				$page = new OneVideo($props);		break;	
	case 'vidsdate':		$page = new DateVideos($props);		break;	
	case 'vidscat':			$page = new CatVideos($props);		break;	

	case 'mapid':				$page = new OneMap($props);			break;	
	case 'mapdate':			$page = new DateMap($props);			break;	
	case 'mapspot':			$page = new SpotMap($props);			break;	
	case 'mapnear':			$page = new NearMap($props);			break;	
	case 'mapplace':		$page = new PlaceMap($props);			break;	
	
	case 'txtwpid':			$page = new TripStory($props);				break;
	case 'txtdate':			$page = new TripStoryByDate($props);	break;
	
	case 'spotshome':		$page = new SpotsHome($props);			break;
	case 'spotstype':		$page = new SpotsTypes($props);			break;
	case 'spotskey':		$page = new SpotsKeywords($props);	break;		// for a keyword (from a spot OR the search page)
	case 'spotscamp':		$page = new SpotsCamps($props);			break;		// type of campground (usfs, usnp, state)
	case 'spotsplace':	$page = new SpotsPlaces($props);		break;		// state/region
	
	case 'resultstext':	$page = new SearchResults($props);	break;

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
