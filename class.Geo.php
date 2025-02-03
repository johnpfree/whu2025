<?php

class DbWpNewBlog extends DbBase
{
	var $tablepref = '';
	function __construct()
	{
		$data = new DbWpData();
		$this->tablepref 	= $data->tablepref();
    $this->dsn = array_merge($this->dsn, $data->dsn());
		// dumpVar($this->dsn, "this->dsn");
		parent::__construct();
	}	
}

// ---------------------------------------------------------------------------------------  
class WhuLink
{
	var $classes = '';
	var $params = '';
	var $albumList = false;
	// Can really overload param 3 with whatever you want based on the first two

	var $props = '';				 // shut up the 'dynamic property deprecated' message
	var $trip = '';


	function __construct($p, $t, $kparm = '', $txt = '-', $title = '')
	{
		$defaults = array('classes' => '', 'params' => '', 'txt' => $txt, 'page' => $p, 'type' => $t, 'key' => $kparm, 'title' => $title);
		$this->props = new WhuProps($defaults);		// default settings
		$this->pagetype = "$p$t";
	}

	function url()
	{
		$curkey  = $this->props->get('key');
		// dumpVar($this->pagetype, "curkey=$curkey this->pagetype");
		switch ($this->pagetype) {
			case 'txtdate':
				$day = new WhuDbDay($this->props, $curkey);
				$link = ViewWhu::makeWpPostLink($day->postId(), $this->props->get('key'));
				return sprintf("<a target='_blank' href='%s'>%s</a>", $link, $this->props->get('txt'));				
		}
		return $this->canonicalWhu();
	}
		
	function addClass($str)	{	if ($this->classes != '') $this->classes .= ' '; $this->classes .= $str;	}
	function addParam($k,$v)	{	$this->params .= "&$k=$v";	}
	function canonicalWhu()
	{
		return sprintf('<a %s href="?page=%s&type=%s&key=%s%s" title="%s">%s</a>', 
				$this->props->get('classes'), 
				$this->props->get('page'), 
				$this->props->get('type'), 
				$this->props->get('key'), 
				$this->props->get('params'), 
				$this->props->get('title'), 
				$this->props->get('txt')
			);
	}
}
class WhuSimpleLink extends WhuLink
{
	var $type = 'id';
	function __construct($trip)
	{
		$defaults = array('classes' => '', 'params' => '', 'txt' => '', 'page' => $this->page, 'type' => $this->type, 'key' => '');
		$this->props = new WhuProps($defaults);		// default settings
		$this->trip = $trip;
	}
	function url() 
	{
		$this->props->set('key', $this->trip->id());			// overload param 3
		if ($this->hasStuff()) {
			$this->props->set('txt', $this->myIcon);
			$this->props->set('title', $this->title);
			return $this->canonicalWhu();
		}
		else
			return '';			
	}
	function hasStuff() { return false; }
}
class WhumapidLink extends WhuSimpleLink
{
	var $page = 'map';
	var $title = 'map for the trip';
	var $myIcon = '<i class="fa fa-map" aria-hidden="true"></i>';
	function hasStuff() { return $this->trip->hasMap(); }
}
class WhuvidsidLink extends WhuSimpleLink
{
	var $page = 'vids';
	var $title = 'videos';
	var $myIcon = '<i class="fa fa-video-camera" aria-hidden="true"></i>';
	function hasStuff() { return $this->trip->hasVideos(); }
}
class WhupicsidLink extends WhuSimpleLink
{
	var $page = 'pics';
	var $title = 'pictures';
	var $cameraimg = '<i class="fa fa-picture-o" aria-hidden="true"></i>';
	function url()
	{
		$this->props->set('key', $this->trip->id());			// overload param 3
		if ($this->trip->hasWhuPics()) 
		{
			$this->props->set('txt', $this->cameraimg);
			$this->props->set('title', 'pictures');
			return $this->canonicalWhu();
		}
		return '';
	}
}

/* 
Orphans that don't fit into the class structure. Mostly because I don't need to instantiate a WhuThing to use them

-- getGeocode()       uses Google location services to get the GPS of a place

-- getAllSpotKeys()   returns an array of all spot keywords. It does need the database, which I hack up in the call.

-- class AjaxCode			my cute ajax code for the Search page - cleaner, faster, better!
*/
function getGeocode($name)
{
	$coded = str_replace(" ", "%20", $name);
	dumpVar($coded, "$name ==>");
	
	$url =  "https://api.mapbox.com/geocoding/v5/mapbox.places/{$coded}.json?access_token=pk.eyJ1Ijoiam9obnBmcmVlIiwiYSI6ImNpajF5OGk2YjAwY3J1OGx3N3hyNjFvNjUifQ.L8lYX2iaC1iXYY1UXOntzw";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$postjson = curl_exec($ch);
	curl_close($ch);
	$ret = json_decode($postjson, true);
	
	if (sizeof($ret['features']) == 0) 
		return array('stat' => 'FAIL', 'name' => $name);
	
	return array('stat' => 'success', 'name' => $name, 
		'lat' => $ret['features'][0]['center'][1], 			// the returned values are ***LON,LAT*** not lat,lon
		'lon' => $ret['features'][0]['center'][0]);
}
// ---------------------------------------------------------------------------------------  
function getAllSpotKeys($db)
{
	$items = $db->getAll("select * from wf_spot_days order by wf_spots_id");

	$singlekeys = array();
	$keypairs = array();
	$allkeys = array();
	for ($i = 0, $str = ''; $i < sizeof($items); $i++) 
	{
		$vals = explode(',', $str = $items[$i]['wf_spot_days_keywords']);
// dumpVar($vals, "explode($str)");
		for ($j = 0; $j < sizeof($vals); $j++) 
		{
			$val = explode('=', trim($vals[$j]));
			// dumpVar($val, "i,j $i,$j");
			if (sizeof($val) == 1)
			{
				if (empty($singlekeys[$val[0]]))
					$singlekeys[$val[0]] = array($items[$i]['wf_spots_id']);
				else if ($singlekeys[$val[0]][sizeof($singlekeys[$val[0]])-1] != $items[$i]['wf_spots_id']) // tricky: save only one instance of spot id         d
					$singlekeys[$val[0]][] = $items[$i]['wf_spots_id'];
			}
			else if (sizeof($val) >= 1)
				$keypairs[trim($val[0])] = trim($val[1]);
			else
				jfdie("parsed poorly: $val");
		}
	}
	unset($singlekeys['']);   // a SpotDay with no keywords shows up as a blank, remove that bunch
	// dumpVar($singlekeys, "singlekeys");
	ksort($singlekeys);
	return $singlekeys;
}

// ---------------------------------------------------------------------------------------  

class SaveForm
{
	function __construct($p)
	{
		$this->props = $p;
		
		$file = getcwd() . '/feedback.csv';
		dumpVar($file, "file");include 'class.Geo.php';
		
		$this->out = new FileWrite($file, 'a');
		$this->out->dodump = false;
	}
	function write($post, $src)
	{
		// date time, purpose, name, email, topic, content, url
		$str = sprintf("%s,%s,%s,%s,%s,%s,%s", date("Y-m-d H:i:s"),
						$this->props->get('choose_purpose'), $this->massageForCsv('f_ndata'), $this->props->get('f_edata'), 
						$this->massageForCsv('f_topic'), $this->massageForCsv('f_comment'), $this->props->get('f_url'));
		
		$this->out->write("$str");
	}
	function massageForCsv($prop)
	{
		$txt = $this->props->get($prop);          // specialized, get the prop here
		$txt = str_ireplace('"', '"""', $txt);    // double quotes in text are doubled
		return '"' . $txt . '"';
	}
}

// ---------------------------------------------------------------------------------------  

class AjaxCode 
{
	var $colWid = 2;
	var $page = 'spots';

	var $oneLink = 
		// <div class="col-md-%s">
		// 	<a class="onecheck" href="?page=%s&type=%s&key=%s">%s (%s)</a>
		// </div>
		// save this CSS in case we resurrect the div style:
		// .onecheck {
		// 	white-space: nowrap;
		// 	padding: .2em .8em;
		// }
		
<<<HTML
		<button class="btn btn-outline-success" type="button"><a href="?page=%s&type=%s&key=%s">%s (%s)</a></button>
HTML;
}

class SpotLocation extends AjaxCode 
{
	var $type = 'place';
	function result($page)
	{
		$placeCats = array(-106, 109, -113, 70, 110, 111, 120, 121, 112, 108, 107, 105, 103, 173, 91, 83, 80, 128);
		for ($i = 0, $str = ''; $i < sizeof($placeCats); $i++) 
		{
			$parms = array('wf_categories_id' => ($id = abs($placeCats[$i])));
			$parms['kids'] = ($placeCats[$i] > 0);    // little hack, negative number above means do NOT loop through children

			$spots = $page->build('DbSpots', $parms);
			$cat = $page->build('Category', $id);
			$str .= sprintf($this->oneLink, $this->page, $this->type, $id, $cat->name(), $spots->size());
		}
		return $str;
	}
}
class SpotType extends AjaxCode 
{
	var $colWid = 6;
	var $type = 'camp';
	function result($page)
	{
		$types = WhuDbSpot::$CAMPTYPES;
		// dumpVar($types, "types");
		$str ='';
		foreach ($types as $k => $v)
		{
			$parms = array('camp_type' => $k);
			$spots = $page->build('DbSpots', $parms);
			$str .= sprintf($this->oneLink, $this->page, $this->type, $k, $v, $spots->size());
		}
		return $str;
	}
}
class SpotKey extends AjaxCode 
{
	var $type = 'key';
	function result($page)
	{
		$spotkeys = getAllSpotKeys(new DbWhufu(new Properties(array())));
		// dumpVar($spotkeys, "types");
		$str ='';
		foreach ($spotkeys as $k => $v)
		{
			if (($nv = sizeof($v)) < 2)
				continue;
			$str .= sprintf($this->oneLink, $this->page, $this->type, $k, $k, $nv);
		}
		return $str;
	}
}

class PicPlace extends AjaxCode 
{
	var $page = 'pics';
	var $type = 'cat';
	var $colWid = 3;
	function result($page)
	{
		$cats = $page->build('Categorys', 'all');
		$catlist = $cats->traverse($page->build('Category', $this->root($cats)));
		
		for ($i = 0, $str = ''; $i < sizeof($cats->descendantList()); $i++) 
		{
			$cat = $cats->descendantList()[$i];
			// dumpVar($cat->name(), sprintf("%s. d=%s, id=%s", $i, $cat->depth(), $cat->id()));

			if (($npic = $cat->nPics()) < 2)
				continue;

			$str .= sprintf($this->oneLink, $this->page, $this->type, $cat->id(), sprintf("%s %s", str_repeat('&bull;', $cat->depth()-1), $cat->name()), $npic);
		}
		return $str;
	}
	function root($cats)  { return $cats->placesRoot(); }
}
class PicCat extends PicPlace 
{
	function root($cats)  { return $cats->picCatsRoot();  }
}

?>
