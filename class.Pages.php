<?php

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $file = "UNDEF";
	var $sansFont = "font-family: Roboto, Arial, sans-serif";
	// var $sansFont = "font-family: 'Montserrat', sans-serif";
	
	// if $caption is non-blank, use it in setCaption(). Otherwise call getCaption()
	var $caption = '';
	
	// what to plug into <meta name="description" /> tag
	var $meta_desc = 'Pictures, Stories, Overnights, Custom Maps';		
	
	var $extralink = '';

	function __construct($p)
	{
		$this->props = $p;
		parent::__construct(new WhuTemplate());
		$pagetype = $this->props->get('page') . $this->props->get('type');
dumpVar(get_class($this), "View class, <b>$pagetype</b> --> <b>{$this->file}</b>");
	}
	function showPage()	{	}
	function preShowPage()
	{
		$this->template->set_var('HEADER_GALLERY', '');		// default is no header gallery
	}
		
	function isRunningOnServer() { return (HOST == 'cloudy'); }
	
	function setCaption()		// also handy place for bookkeeping shared for all pages
	{
		// dumpVar($this->getCaption(), "this->getCaption()");
		// dumpVar($this->caption, "this->caption");
		$this->template->set_var('CAPTION'  , ($this->caption != '') ? $this->caption : $this->getCaption());		
		$this->template->set_var('META_DESC', $this->getMetaDesc());
		dumpVar($this->getMetaDesc(), "this->getMetaDesc()");
		
		if ($this->isRunningOnServer())
			$this->template->setFile('GOOGLE_ANALYTICS', 'googleBlock.ihtml');
		else
			$this->template->set_var('GOOGLE_ANALYTICS', '');
	}
	function getCaption() { return $this->defaultCaption(); }
	function defaultCaption()	
	{
		return sprintf("%s | %s | %s", $this->props->get('page'), $this->props->get('type'), $this->props->get('key'));	
	}
	function getMetaDesc()	{	return $this->meta_desc;	}
	
	function headerGallery($pics)
	{
		$this->template->setFile('HEADER_GALLERY', 'headerGallery.ihtml');		
		$loop = new Looper($this->template, 
							array('parent' => 'HEADER_GALLERY', 'noFields' => true, 'one' =>'header_row', 'none_msg' => 'no pictures'));
		$loop->do_loop($this->makeGalleryArray($pics));		
	}
	function makeGalleryArray($pics, $useThumbs = false)
	{
		for ($i = 0, $rows = array(); $i < $pics->size(); $i++) 
		{
			$pic = $pics->one($i);
			// dumpVar($pic->data, "pic->data");
			// dumpVar(sprintf("id %s, %s: %s", $pic->id(), $pic->filename(), $pic->caption()), "$i Gallery");
		
			$row = array('PIC_ID' => $pic->id(), 'WF_IMAGES_PATH' => $pic->folder(), 'PIC_NAME' => $pic->filename());
			$row['PIC_DESC'] = htmlspecialchars($pic->caption());

			$imageLink = sprintf("%s%s/%s", iPhotoURL, $row['WF_IMAGES_PATH'], $row['PIC_NAME']);
			// $imageLink = sprintf("%spix/iPhoto/%s/%s", iPhotoURL, $row['WF_IMAGES_PATH'], $row['PIC_NAME']);
			if ($useThumbs)
			{
				$thumb = $pic->thumbImage();
				$row['IMG_THUMB'] = "data:image/jpg;base64,$thumb";
				if (strlen($row['img_thumb']) < 100) {							// use the full image if the thumbnail fails on server
					dumpVar($row['PIC_NAME'], "binpic fail");
					$row['IMG_THUMB'] = $imageLink;
				}
				else if (($ratio = ($pic->thumbSize[0] / $pic->thumbSize[1])) > 2.) {	// thumb looks terrible for panorama, use full pic
					// dumpVar($ratio, "ratio");
					// dumpVar($pic->thumbSize, "$i pic->thumbSize");
					$row['IMG_THUMB'] = $imageLink;
				}
			}
			else
				$row['IMG_THUMB'] = $imageLink;
			
			$rows[] = $row;
		}
		dumpVar(sizeof($rows), "makeGalleryArray NPics");
		return $rows;
	}
	
	function addDollarSign($s)	{ return "&#36;$s"; }
	function spotLink($name, $id) { return sprintf("<a href='%s?page=spot&type=id&key=%s'>%s</a>", $this->whuUrl(), $id, $name); }
	function whuUrl() { 	// cheeseball trick to use http locally and https on server
		return sprintf("http%s://%s%s", (HOST == 'cloudy') ? 's' : '', $_SERVER['HTTP_HOST'], parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
	}

	function setLittleMap($coords)
	{
// dumpVar($coords, "setLittleMap");
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		if (!isset($coords['lat']))
		{
			$this->template->set_var("MAP_INSET", isset($coords['geo']) ?
					"<i class=smaller><br />Apparently the camera couldn't <br />geolocate this pic.</i>" :
					"<i class=smaller><br />This camera doesn't do geolocation.</i>");
					// "<i class=smaller><br />No geolocation data for this picture.</i>");
			return false;
		}
		$this->template->setFile('MAP_INSET', 'mapInset.ihtml');
		foreach ($coords as $k => $v) 
		{
			$this->template->set_var("PT_$k", addslashes($v));
		}
		return true;
	}
	function markerIndex($i) { return ($i+1) % 100; }		// do I still run out of maarkers at 100?
	
	// --------------------------------------- Spot header and type lookup code shared between SpotsListType and SpotsListMap

	function makeSpotsListHeader($title, $listtype, $extralink = false)
	{
		$links = array("M" => "map view", "L" => "list view", "T" => "thumbnails");
		$names = array("M" => "map", "L" => "spots", "T" => "thumbs");
		dumpVar($this->caption, "this->caption");
		dumpVar($this->title, "this->title");
		dumpVar($links[$listtype], "title=$title &bull; extralink=$extralink &bull; links[$listtype]");
		assert(isset($links[$listtype]), "bad type for makeSpotsListHeader($title, $listtype, $extralink)");
		
		$this->template->set_var('PAGE_TITLE', $title);
		$this->caption = strip_tags($title);
		
		extract($this->props->props);			// variablize page,type,key
		// if ($type == 'lat')
		// 	$extras = "&lon=$lon&radius=$radius";
		// else
		if ($type == 'location')
			$extras = "&location=$location&radius=$radius";
		else if ($type == 'spotid')
			$extras = "&radius=$radius";
		else
			$extras = "";
			
		$nav = '';
		foreach ($links as $k => $v) 
		{
			if ($k == $listtype)
				continue;			
			$nav .= "<a href='?page={$names[$k]}&type=$type&key=$key$extras'>$v</a> &bull; ";
		}
		
		// dumpVar(boolStr($extralink == 'RoundTripSearchDlg'), "$extralink == 'RoundTripSearchDlg'");
		if ($extralink == 'RoundTripSearchDlg') 
		{
			$nav .= sprintf("<a href='?page=search&type=home&key=&addy=%s&rad=%s'>change search</a> &bull; ", 
					$location, $radius);
		}
		else if ($extralink == 'RoundTripSpotDlg') 
		{
			$nav .= "<a href='?page=spot&type=id&key=$key'>change radius</a> &bull; ";
		}
			
		$this->template->set_var('TITLE_CLAUSE', substr($nav, 0, -8));
	}
	
	function getSpotsByKeyword()	
	{
		$this->title = sprintf("Spots with keyword: %s", str_replace( '_', ' ', $this->key));
		dumpVar($this->title, "this->title");
		$this->spots = $this->build('DbSpots', array('type' => 'keyword', 'data' => $this->key));
	}
	
	var $spotTypes = array(
		'FS' => 'Forest Service campgrounds', 
		'NPS' => 'National Park campgrounds', 
		'BLM' => 'BLM campgrounds', 
		'ACE' => 'Army Corps of Engineers campgrounds', 
		'State' => 'State campgrounds', 
		'County' => 'County/City campgrounds', 
		'boondock' => 'Parking lots and other boondocking', 
		'inside' => 'Hotels and Motels', 
		// not in search
		'NWR' => 'Wildlife Refuges', 
		'HOTSPR' => 'Hot Springs', 
	);
	function getSpotsByType($key)
	{
		switch ($this->key) {
			case 'NPS':				$parms = array('type' => 'partof', 'data' => "National Park");	break;
			case 'FS':				$parms = array('type' => 'partof', 'data' => "National Forest");	break;
			case 'BLM':				$parms = array('type' => 'partof', 'data' => $this->key);	break;
			case 'ACE':				$parms = array('type' => 'partof', 'data' => 'Army Corps');	break;
			case 'State':			$parms = array('type' => 'partof', 'data' => 'State Park');	break;
			case 'County':		$parms = array('type' => 'partof', 'data' => 'County');	break;
			case 'inside':		$parms = array('type' => 'type' , 'data' => 'LODGE');	break;
			case 'boondock':	$parms = array('type' => 'status', 'data' => 'parking');	break;
			case 'NWR':				$parms = array('type' => 'type', 'data' => 'NWR'); break;
			case 'HOTSPR':		$parms = array('type' => 'type', 'data' => 'HOTSPR'); break;			
			default; exit;
		}
		$this->caption = $this->spotTypes[$this->key];
		$this->title = $this->caption;

		$spots = $this->build('DbSpots', $parms);
		if ($this->key == 'County')
			$spots->add($this->build('DbSpots', array('type' => 'partof', 'data' => 'City')));
		if ($this->key == 'boondock')
			$spots->add($this->build('DbSpots', array('type' => 'status', 'data' => 'roadside')));
		
		return $spots;
	}

	function getSpotsByPlace($key)
	{
		$labels = array(
			"113,153,110" => "Oregon, Washington",
			"70" => "Oregon Coast", 
			"108" => "Nevada", 
			"112" => "Utah", 
			"111" => "Idaho", 
			"121" => "Colorado", 
			"119,120" => "Montana/Wyoming", 
			"103,173" => "Arizona/NewMexico", 
			"89"  => "Dakotas, Missouri, Corn Belt", 
			"212" => "Texas, Oklahoma, Arkansas", 
			"128" => "Istanbul Hotels", 
			"208" => "North Sierras", 
			"109" => "North Coast", 
			"107" => "US 395 (Eastern Sierras)", 
			"211" => "Central Valley", 
			"106" => "Other Nor Cal", 
			"105" => "So Cal", 
			"80" => "Tennessee, North Carolina and South", 
			"83,88" => "Kentucky, Virginia and North", 
		);
		
		// assert(isset($labels[$this->key]), "unknown key, SpotsListChildren");
		if (isset($labels[$this->key]))
			$placeName = $labels[$this->key];
		else {
			$cat = $this->build('Category', $this->key);
			$placeName = $cat->name();
		}
		$this->title = "Spots in $placeName";
		
		
		$this->headerClause = "<a href='?page=map&type=spotplaces&key=$this->key'>map view</a> &bull; <a href='?page=map&type=spotplaces&key=$this->key'>list view</a>";

		$placeids = explode(',', $this->key);
		// dumpVar($placeids, "placeids");
		// $isOre = ($this->key == '113,153,110');
		return $this->spots = $this->build('DbSpots', array('type' => 'placekids', 'data' => $placeids));
	}
	
	// ----------------------------------------------------------- base utilities

	function makeTripWpLink($trip)
	{
		$wplink = $trip->wpReferenceId();	// always return an array 
		$this->template->set_var('WP_VIS', '');			// assume visible
		switch ($wplink[0]) {
			case 'cat':			$link = ViewWhu::makeWpCatLink($wplink[1]);		$txt = 'stories';	break;
			case 'post':		$link = ViewWhu::makeWpPostLink($wplink[1]);	$txt = 'story';		break;			
			case 'none':		$this->template->set_var('WP_VIS', "class='hidden'");	$link = $txt = '';	break;			
			default:				dumpVar($wplink, "BAD RETURN! wp_ref");	exit;
		}
		// dumpVar($wplink, "wplink -> $link");
		$this->template->set_var('WP_LINK', $link);
		$this->template->set_var('WP_TEXT', $txt);
		return array($link, $txt);				// hack-a-rama, return these values also, for the fucking trips look
	}
	
	static function makeWpPostLink($wpid, $namelink = '') 
	{ 
		if ($namelink != '')
			$namelink = "#$namelink";
		return sprintf("%s/?p=%s%s", WP_PATH, $wpid, $namelink);	
	}
	static function makeWpCatLink($catid)	{ return sprintf("%s/?cat=%s", WP_PATH, $catid); }
	
	function build ($type = '', $key = '') 
	{
		// dumpVar($type, "VIEW Build: type");
		if ($type == '') {
			throw new Exception("Invalid Thing Type = $type.");
		} 
		else 
		{ 
			$className = 'Whu'.ucfirst($type);

			if (class_exists($className)) {
				return new $className($this->props, $key);
			} else {
				dumpVar( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), "");
				throw new Exception("Thing type $className not found.");
			}
		}
	}
}

// New stuff =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

class HomeHome extends ViewWhu
{
	var $file = "homehome.ihtml";
	// var $bannerIds = array(7964, 8062, 8097, 8098, 8111, 8236, 8238, 8294, 8306);
	var $recents = array(65, 64, 63, 62, 61, 59);
	var $epics = array(56, 22, 14, 44, 26, 53);
	function showPage()
	{
		$this->template->set_var('REL_PICPATH', iPhotoURL);

		$pics = $this->build('Faves', array('type' =>'all', 'shape' => 'pano'));
		$pic = $pics->favorite();
		$this->template->set_var('BANNER_ID', $pic->id());
		$this->template->set_var('BANNER_FOLDER', $pic->folder());
		$this->template->set_var('BANNER_FILE', $pic->filename());

		shuffle($this->recents);
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'rec_row'));
		$loop->do_loop($this->oneRow(array_slice($this->recents, 0, 3)));

		shuffle($this->epics);
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'epic_row'));
		$loop->do_loop($this->oneRow($this->epics));
				
		parent::showPage();
	}
	function oneRow($ids)
	{
		for ($i = 0, $cells = array(); $i < sizeof($ids); $i++)
		{
	 	 	$trip = $this->build('Trip', $ids[$i]);
			
			// $pics = $this->build('Faves', array('type' =>'folder', 'data' => $trip->folder(), 'shape' => 'landscape'));
			$pics = $this->build('Faves', array('type' =>'folder', 'data' => $trip->folder(), 'shape' => 'lan'));
			if ($pics->size() == 0)
			{
				dumpVar($trip->folder(), "Evidently no landscape Favorites for this trip. Look in all pics.");				
				$pics = $this->build('Pics', array('type' =>'folder', 'data' => $trip->folder(), 'shape' => 'lan'));
			}
			$pic = $pics->favorite();
			$cell = array(
				'trip_id' 		=> $trip->id(),
				'trip_title' 	=> $trip->name(),
				'trip_desc' 	=> $trip->desc(),
				'pic_id'			=> $pic->id(),
				'pic_folder'	=> $pic->folder(),
				'pic_file'		=> $pic->filename()
			);
			$cell['row_sep'] = ($i == 2) ? '</div><div class="row cardrow">' : '';
						
			$cells[] = $cell;
		}
		// dumpVar($cells, "rows");
		return $cells;
	}
}

class OneTrip extends ViewWhu
{
	var $file = "onetrip.ihtml";   
	var $loopfile = 'mapBoundsLoop.js';
	var $marker_color = '#535900';	// '#8c54ba';
	function showPage()	
	{
		$tripid = $this->key;
 	 	$trip = $this->build('Trip', $tripid);	
		$this->template->set_var('TRIP_TITLE', $this->caption = $trip->name());
		$this->template->set_var('TRIP_ID', $tripid);

		// - - - Header PICS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$pics = $this->build('Faves', array('type' =>'folder', 'data' => $trip->folder()));
		$pics->getSome(12);
		$this->headerGallery($pics);
		
		// - - - MAP - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$this->template->setFile('TRIP_MAP', 'mapInsetBig.ihtml');		
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var('PAGE_VAL', 'day');
		$this->template->set_var('TYPE_VAL', 'date');
		$this->template->set_var('MARKER_COLOR', $this->marker_color);

		$this->template->set_var("KML_FILE", $trip->gMapPath());
		$this->template->setFile('JSON_INSERT', 'mapkml.js');
		$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		$this->template->setFile('LOOP_INSERT', $this->loopfile);
				
 	 	$days = $this->build('DbDays', $tripid);
		for ($i = 1, $rows = array(), $prevname = '@'; $i < $days->size(); $i++)
		{
			$day = $this->build('DayInfo', $days->one($i));

			if ($day->noPosition()) {						// skip if no GPS coordinates
				$eventLog[] = "NO POSITION! $i row";
				$eventLog[] = $row;
				continue;
			}

			$row = array('marker_val' => $this->markerIndex($i), 'point_lon' => $day->lon(), 'point_lat' => $day->lat(), 
										'key_val' => $day->date(), 'link_text' => Properties::prettyDate($day->date()));

			$spotName = $day->nightName();
			$row['point_name'] = addslashes($day->hasSpot() ? $this->spotLink($spotName, $day->spotId()) : $spotName);
// dumpVar($row, "row $i");
			if ($spotName == $prevname) {											// skip if I'm at the same place as yesterday
				// $eventLog[] = "skipping same $i: {$spotName}";
				continue;                       
			}
			$prevname = $spotName;

			// dumpVar($row, "$i - row");
			$rows[] = $row;
		}
		// dumpVar($rows, "rows");
		$loop = new Looper($this->template, array('parent' => 'TRIP_MAP', 'noFields' => true, 'one' =>'node_row', 'none_msg' => ""));
		$loop->do_loop($rows);
		
		// - - - Days and Posts - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		// whiffle thru the days and collect all the unique post ids and spot ids
		$days = $this->build('DbDays', $tripid);
		for ($i = $prevPostId = 0, $spotList = $postList = $spotIds = array(); $i < $days->size(); $i++) 
		{
			$day = $this->build('DayInfo', $days->one($i));
			if ($day->hasSpot() && !in_array($day->spotId(), $spotIds))
			{				
				$spot['spot_id'] = $spotIds[] = $day->spotId();
				$spot['spot_name'] = $this->nameFilter($day->nightName());
				$spot['spot_sep'] = ', ';
				$spotList[] = $spot;
			}			
			$iPost = $day->postId();
			// dumpVar($prevPostId, "$i, iP=$iPost, prevPost");
			if ($iPost != $prevPostId)
			{
				$post = $this->build('Post', array('quickid' => $iPost));
				$postList[] = array('post_title' => $post->title(), 'post_link' => $this->makeWpPostLink($iPost));
				$prevPostId = $iPost;
			}
		}

		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'stop_row'));
		$loop->do_loop($spotList);

		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'post_row', 'none_msg' =>'No Stories for this trip'));
		$loop->do_loop($postList);
		
		parent::showPage();
	}
	function nameFilter($name) { return str_replace("Campground", "CG", $name); }
}

class OneSpot extends ViewWhu
{
	var $file = "onespot.ihtml";   
	function showPage()	
	{
		$spotid = $this->key;
 	 	$spot = $this->build('DbSpot', $spotid);	
		
		$this->template->set_var('SPOT_NAME', 	$this->caption = $spot->name());
		$this->template->set_var('SPOT_ID', 		$spot->id());
		$this->template->set_var('SPOT_TOWN', 	$spot->town());
		$this->template->set_var('SPOT_PLACE',  $spot->place());
		$this->template->set_var('SPOT_PLACEID', $spot->placeId());

		$this->template->set_var('SPOT_PARTOF', $str = $spot->partof());
		// dumpVar(boolStr($str == 'private'), "str=$str");		
		$hideme = array('private', 'Army Corps of Engineers', 'County Park', 'Bureau of Land Management (BLM)');
		$this->template->set_var('SHOW_PARTOF', in_array($str, $hideme) ? 'class="hideme "' : '');

		
		$this->template->set_var('SPLAT',  	round($spot->lat(), 4));
		$this->template->set_var('SPLON',  	round($spot->lon(), 4));
		$this->template->set_var('SPOT_NUM',  $visits = $spot->visits());
		$this->template->set_var('SPBATH',  	$spot->bath());
		$this->template->set_var('SPWATER',  	$spot->water());
		$this->template->set_var('SPDESC',  	$desc = $spot->htmldesc());
		
		$this->template->set_var('SEARCH_RADIUS_VAL', $this->props->getDefault('radius', '50'));
		dumpVar($visits, "visits");
		if ($visits == 'never')
		{
			$this->template->set_var('DAYS_INFO', 'hideme');							// NO Days!
		// exit;
		}
		else
		{
			$this->template->set_var('DAYS_INFO', '');										// yes, there are days
			
			// --------------------------------------------- show spot keywords, and a little special sauce for phone reception
			$phonemsg = '';
			$keys = $spot->keywords();
			// dumpVar($keys, "keys");
			$sep = "";
			for ($i = 0, $rows = array(); $i < sizeof($keys); $i++) 
			{
				$key = trim($keys[$i]);
				if (substr($key, 3, 1) == '=') 
				{
					$providers = array('att' => 'ATT', 'ver' => 'Verizon', 'tmo' => 'T-Mobile', );
					$msgs = array(
						'good' => "<b>%s</b> cellphone reception was good.",
						'some' => "Some <b>%s</b> cellphone reception but not great.",
						'poor' => "No usable cellphone reception for %s.",
						'none' => "No <b>%s</b> service here.",
					);
					$phoneStat = explode('=', $key);
					dumpVar($phoneStat, "phoneStat");
					
					if (isset($msgs[$phoneStat[1]]) && isset($providers[$phoneStat[0]]))
						$phonemsg = sprintf($msgs[$phoneStat[1]], $providers[$phoneStat[0]]);
					continue;
				}
				$rows[] = array('spot_key' => $key, 'spot_sep' => $sep);
				$sep = "&bull; ";
			}
			$loop = new Looper($this->template, array('parent' => 'the_content', 'one' => 'keyrow', 'none_msg' => "no keywords", 'noFields' => true));
			$loop->do_loop($rows);
			
			dumpVar($phonemsg, "phonemsg");
			$this->template->set_var('ATT_MSG', $phonemsg);

			// ------------------------------------------------------- collect Day info, AND Pic/Faves info, because pics are by day
			$days = $this->build('DbSpotDays', $spot->id());								
			for ($i = $count = 0, $rows = array(); $i < $days->size(); $i++)
			{
				$day = $days->one($i);
				// $day->dump($i);
				$date = $day->date();

				// collect evening and morning pictures for each day
				if ($i == 0) {
					$pics = $this->build('Pics', array('type' => 'night', 'data' => $date));
					// dumpVar($pics->size(), "000 pics->size()");
				}
				else {
					$pics->add($this->build('Pics', array('type' => 'night', 'data' => $date)));
					// dumpVar($pics->size(), "$i. pics->size()");
				}
				
				$row = array('stay_date' => $date = $day->date());
				$row['nice_date'] = Properties::prettyDate($date);
				$row['spdaydesc'] = $day->htmldesc();
				if ($row['spdaydesc'] == $desc || $row['spdaydesc'] == '') 			// don't repeat the main desc
					$row['spdaydesc'] = "<em>(see main description above)</em>";

				if (($cost = $day->cost()) > 0)
				{
					$costs = $this->addDollarSign($cost);
					if ($day->senior() > 0 && $day->senior() != $cost)
						$costs .= ' &bull; '.$this->addDollarSign($day->senior());
				}
				else
					$costs = "free!";
				$row['spcosts'] = $costs;
				if ($day->tripId() > 0)
				{
					$row['use_link'] = '';
					$row['not_link'] = 'hideme';
				}
				else
				{
					$row['use_link'] = 'hideme';
					$row['not_link'] = '';
				}
				// dumpVar($row, "$i row");
				$rows[] = $row;
			}
			$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
			$loop->do_loop($rows);

			$faves = $this->build('Faves', array('type' => 'pics', 'data' => $pics));			// cull out the favorites
			dumpVar($faves->size(), "All N faves->size()");
			$faves->getSome(12, $pics);	
			$this->headerGallery($faves);
		}
		
		$this->setLittleMap(array('lat' => $spot->lat(), 'lon' => $spot->lon(), 'name' => $spot->name(), 'desc' => $spot->town()));
		
		// ------------------------------------------------------- spot type
		$str = '';
		foreach ($spot->prettyTypes() as $k => $v) { $str .= $v . ', '; }		
		$this->template->set_var('SPOT_TYPES', $types = substr($str, 0, -2));

		parent::showPage();
	}
}

class AllTrips extends ViewWhu
{
	var $file = "tripslist.ihtml";   
	static $cats = array(
			'tl_rcnt' => "Most recent", 
			'tl_alll' => "All Trips", 
			'tl_east' => "Epic Cross-country", 
			'tl_ista' => "Istanbul", 
			'tl_fall' => "Fall Colors", 
			'tl_sprg' => "Spring Flowers", 
			'tl_395e' => "Eastern Sierras and US 395", 
			'tl_euka' => "Family Visits to Eureka", 
			'tl_noca' => "All Norcal", 
			'tl_soca' => "Southern Cal", 
			'tl_neva' => "Nevada", 
			'tl_dsrt' => "All Southwest", 
			'tl_nwst' => "All Northwest", 
		);
	function showPage()	
	{
		dumpVar($this->key, "this->key");

		if ($this->props->get("type") == 'home')
		{			
			$this->template->set_var('PAGE_TITLE', $this->pagetitle = "Some Trips");
			$trips = $this->build('Trips');
			$trips->random(15);
		}
		else 
		{
			$this->template->set_var('PAGE_TITLE',  $this->pagetitle = AllTrips::$cats[$this->key] . " Trips");
			$trips = $this->trips;
		}

		$this->template->set_var('WP_PATH', WP_PATH);
		dumpVar(WP_PATH, "WP_PATH");

		for ($i = 0, $rows = array(); $i < $trips->size(); $i++) 
		{
			$trip = $trips->one($i);
			$row = array("TRIP_DATE" => $trip->startDate(), "TRIP_ID" => $trip->id(), "TRIP_NAME" => $trip->name());
			$row['MAP_LINK' ] = (new WhumapidLink ($trip))->url();
			$row['PICS_LINK'] = (new WhupicsidLink($trip))->url();
			$row['VIDS_LINK'] = (new WhuvidsidLink($trip))->url();

			$wpinfo = $this->makeTripWpLink($trip);					// Aug 20 use new WP link code for WP cell
			$row['WP_LINK'] = $wpinfo[0];
			$row['WP_TXT']  = $wpinfo[1];

			// dumpVar($row, "row"); exit;
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'trip_row'));
		$loop->do_loop($rows);
		
		parent::showPage();
	}
	function getCaption()	{	return $this->pagetitle;	}
}
class SomeTrips extends AllTrips
{
	function showPage()	
	{
		$qwhere = '';  
		switch ($this->key) {
			case 'tl_rcnt':
			case 'tl_alll':	{ $qwhere = ""; break;}
			case 'tl_395e':	{ $qwhere = "WHERE wf_trips_2020 REGEXP '395'"; break;}
			case 'tl_noca':	{ $qwhere = "WHERE wf_trips_2020 REGEXP 'norcal'"; break;}
			case 'tl_dsrt':	{ $qwhere = "WHERE wf_trips_2020 REGEXP 'southwest'"; break;}
			case 'tl_nwst':	{ $qwhere = "WHERE wf_trips_2020 REGEXP 'northwest'"; break;}
			
			case 'tl_east':	{ $qwhere = "WHERE wf_trips_types REGEXP 'east'"; break;}
			case 'tl_euka':	{ $qwhere = "WHERE wf_trips_types REGEXP 'eka'"; break;}
			case 'tl_soca':	{ $qwhere = "WHERE wf_trips_types REGEXP 'socal'"; break;}
			case 'tl_neva':	{ $qwhere = "WHERE wf_trips_types REGEXP 'nev'"; break;}
			case 'tl_ista':	{ $qwhere = "WHERE wf_trips_types REGEXP 'asia'"; break;}

			case 'tl_fall':	{ $qwhere = "WHERE MONTH(wf_trips_start) BETWEEN 9 AND 11"; break;}
			case 'tl_sprg':	{ $qwhere = "WHERE MONTH(wf_trips_start) BETWEEN 2 AND 5"; break;}
			
			default: jfDie("no handler");
		}
				
		$this->trips = $this->build('Trips', $qwhere);	

		if ($this->key == 'tl_rcnt') 
			$this->trips->truncate(12);

		parent::showPage();
	}
}

// --------------------------------------------- Spot List

class SpotsList extends ViewWhu
{
	var $file = "spotslist.ihtml";
	var $spotListMax = 30;
	function showPage()	
	{
		dumpVar($this->key, "this->key");
		$this->makeSpotsListHeader($this->title, "L", $this->extralink);
		
		dumpVar($this->spots->size(), "this->spots->size()");
		$this->spots->random($this->spotListMax);

		for ($i = 0, $rows = array(); $i < $this->spots->size(); $i++)
		{
			$spot = $this->spots->one($i);
			$row = array(
				'spot_id' 		=> $spot->id(), 
				'spot_short' 	=> $spot->shortName(), 
				'spot_name' 	=> $spot->name(),
				'spot_where' 	=> $spot->town(),
				'spot_type' 	=> $spot->types(),
				'spot_place' 	=> $spot->place(),
				'spot_part_of' 	=> $spot->partof(),
				'spot_sep' 	=> ',',
			);
			if ($spot->partof() == 'private') {
				$row["spot_part_of"] = $row["spot_sep"] = '';
			}
			$row["spot_desc"] = $spot->desc();
			$row["stripeme"] = ($i % 2) ? 'cell_stripe1' : 'cell_stripe0';
			// dumpVar($row, "$i row");
			$rows[] = $row;
		}

		dumpVar(sizeof($rows), "rows");
		
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'lg_row'));
		$loop->do_loop($rows);				

		parent::showPage();
	}	
}
class SpotsListType extends SpotsList
{
	function showPage()	
	{
		// $this->caption = $this->spotTypes[$this->key];
		$this->spots = $this->getSpotsByType($this->key);
		parent::showPage();
	}
}
class SpotsPlaceId extends SpotsList
{
	function showPage()	
	{
		$this->spots = $this->getSpotsByPlace($this->key);
		parent::showPage();
	}
}
class SpotsListPlaces extends SpotsPlaceId 
{}	
class SpotsKeywords extends SpotsList
{
	function showPage()	
	{
		$this->getSpotsByKeyword();			// set title and get Spots
		dumpVar($this->title, "SpotsKeywords this->title");
		parent::showPage();
	}
}
class SpotsLocation extends SpotsList
{
	function showPage()	
	{
		$geocode = getGeocode($address = $this->props->get('location'));
		dumpVar($geocode, "lox");
		extract($geocode);
		if ($stat != 'success')
			jfDie("getGeocode($address) failed with status=" . $lox['stat']);
		
		$this->title = sprintf("Spots within <b>%s</b> miles of <i>%s</i>", $radius = $this->props->get('radius'), $address);		
		$this->spots = $this->build('DbSpots', array('type' => 'radius', 'lat' => $lat, 'lon' => $lon, 'radius' => $radius));
		dumpVar($this->spots->size(), "NUM spots");

		parent::showPage();
	}
}
class SpotsSpotId extends SpotsList
{
	var $extralink = 'RoundTripSpotDlg';
	function showPage()	
	{
		$spot = $this->build('DbSpot', $this->key);
		$this->title = sprintf("Spots within %s miles of <i>%s</i>", $radius = $this->props->get('radius'), $spot->name());

		$this->spots = $this->build('DbSpots', array('type' => 'radius', 
			'lat' => $lat = $spot->lat(), 
			'lon' => $lon = $spot->lon(), 
			'radius' => $radius));

		parent::showPage();
	}
}

// --------------------------------------------- Spot Thumbs

class SpotsThumbs extends ViewWhu
{
	var $file = "spotsthumbs.ihtml";
	var $spottypes = array(
					'LODGE'		=> 'Lodging',
					'HOTSPR'	=> 'Hot Springs',
					'NWR'			=> 'Wildlife Refuges',
					'CAMP'		=> 'Camping Places',
					);
	
	var $searchterms = array('CAMP' => 'wf_spots_types', 'usfs' => 'wf_spots_status', 'usnp' => 'wf_spots_status');
	var $title = "Spots";
	var $searchParm = "spots";
	var $headerClause = '';
	
	function showPage()	
	{
		$this->makeSpotsListHeader($this->title, "T", $this->extralink);
		
		// ------------------------------------------------------- caller has already gotten the spots object
		dumpVar($this->spots->size(), "this->spots->size()");
		$this->spots->random(21);

		for ($i = 0, $rows = array(); $i < $this->spots->size(); $i++)
		{
			$spot = $this->spots->one($i);
			$row = array(
				'spot_id' 		=> $spot->id(), 
				'spot_short' 	=> $spot->shortName(), 
				'spot_name' 	=> $spot->name(),
				'spot_where' 	=> $spot->town(),
				'spot_type' 	=> $spot->types(),
				'spot_place' 	=> $spot->place(),
				'spot_part_of' 	=> $spot->partof(),
				'spot_sep' 	=> ',',
			);

			if ($spot->partof() == 'private') {
				$row["spot_part_of"] = "<i>private business</i>";
				$row["spot_sep"] = '';
			}
			$row["spot_desc"] = $spot->desc();
			
			$row["stripeme"] = ($i % 2) ? 'cell_stripe1' : 'cell_stripe0';
				
				// a bunch of grief for pictures:   pic one to show, collect all for banner. Don't mess with favorites
			$spotpics = $spot->pics(array('shape' => 'lan'));
			$pic = $spotpics->randomOne();
			if ($pic == NULL) 
			{
				$row["picshow_0"] = ' hideme';
				$row["nopic_0"] = '';
				// $row["picshow_0"] = '';
				// $row["picid_0"] = '';
				// $row["pictitle_0"] = 'no pictures';
				// $row["picfilename_0"] = $pic->filename();
				// $row['picfolder'] }= $pic->folder();
			}
			else 
			{
				$row["picshow_0"] = '';
				$row["nopic_0"] = ' hideme';
				$row["picid_0"] = $pic->id();
				$row["pictitle_0"] = $pic->caption();
				$row["picfilename_0"] = $pic->filename();
				$row['picfolder'] = $pic->folder();
			}			
			if ($i == 0)
				$pics = $spotpics;				// start collection
			else
				$pics->add($spotpics);		// add to collection
			// dumpVar($pics->size(), "$i pics->size(), ipic=$");
			
			$rows[] = $row;
		}
		dumpVar(sizeof($rows), "rows");
		for ($i = 0, $rows1 = $rows2 = $rows3 = array(); $i < sizeof($rows); $i += 3) 
		{
			$rows1[] = $rows[$i];
			$j = $i + 1;
			if ($j < sizeof($rows))
				$rows2[] = $rows[$j];
			// dumpVar($rows[$j]['spot_name'], "$i: rows[$j][spot_name]");
			$j++;
			if ($j < sizeof($rows))
				$rows3[] = $rows[$j];
			// dumpVar($rows[$j]['spot_name'], "$i: rows[$j][spot_name]");
		}
		
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'lg1_row'));
		$loop->do_loop($rows1);				
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'lg2_row'));
		$loop->do_loop($rows2);				
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'lg3_row'));
		$loop->do_loop($rows3);

		parent::showPage();
		return;
	}
}
class SpotsSome extends SpotsThumbs					// list a subset of Camping spots
{
	var $headerClause = 'browse all Overnights on <a href="?page=search&type=home&key=spots">Search page</a>';
	function showPage()	
	{
		$this->caption = "Some Overnights";
		$this->spots = $this->build('DbSpots', array('type' => 'type', 'data' => "CAMP"));
		dumpVar($this->spots->size(), "this->spots->size()");		
		$this->spots->random(21);
		parent::showPage();
	}
}
class ThumbsListType extends SpotsThumbs					// BLM, Park Service, etc
{
	function showPage()
	{
		// $this->caption = $this->spotTypes[$this->key];
		// $this->headerClause = "<a href='?page=map&type=spottype&key=$this->key'>view as map</a>";
		$this->spots = $this->getSpotsByType($this->key);
		parent::showPage();
	}
}
class SpotsTypes extends SpotsThumbs					// list HOTSPR or NWR
{
	function showPage()	
	{
		$this->caption = $this->spottypes[$this->key];
		$this->headerClause = "<a href='?page=map&type=spottype&key=$this->key'>view as map</a>";
		$this->spots = $this->build('DbSpots', array('type' => 'type', 'data' => $this->key));
		parent::showPage();
	}
}
class XXSpotsListPlaces extends SpotsThumbs					// Place id(s) and all their descendents EXCEPT for Oregon!
{
	function showPage()
	{
		$labels = array(
			"113,153,110" => "Oregon, Washington",
			"70" => "Oregon Coast", 
			"108" => "Nevada", 
			"112" => "Utah", 
			"111" => "Idaho", 
			"121" => "Colorado", 
			"119,120" => "Montana/Wyoming", 
			"103,173" => "Arizona/NewMexico", 
			"89"  => "Dakotas, Missouri, Corn Belt", 
			"212" => "Texas, Oklahoma, Arkansas", 
			"128" => "Istanbul Hotels", 
			"208" => "North Sierras", 
			"109" => "North Coast", 
			"107" => "US 395 (Eastern Sierras)", 
			"211" => "Central Valley", 
			"106" => "Other Nor Cal", 
			"105" => "So Cal", 
			"80" => "Tennessee, North Carolina and South", 
			"83,88" => "Kentucky, Virginia and North", 
		);
		assert(isset($labels[$this->key]), "unknown key, SpotsListChildren");
		$this->caption = "Spots in {$labels[$this->key]}";
		// $this->headerClause = "<a href='?page=map&type=spotplaces&key=$this->key'>view as map</a>";
		$this->headerClause = "<a href='?page=map&type=spotplaces&key=$this->key'>map view</a> &bull; <a href='?page=map&type=spotplaces&key=$this->key'>list view</a>";

		$placeids = explode(',', $this->key);
		// dumpVar($placeids, "placeids");
		$isOre = ($this->key == '113,153,110');
		$this->spots = $this->build('DbSpots', array('type' => ($isOre ? 'place' : 'placekids'), 'data' => $placeids));
			// all spots for thee ids and their descendants
		parent::showPage();
	}
}
class ThumbsKeywords extends SpotsThumbs
{
	var $searchParm = "spotkey";
	function showPage()	
	{
		$this->getSpotsByKeyword();			// set title, caption, aand gets Spots
		parent::showPage();
	}
}
class ThumbsPlaceId extends SpotsThumbs
{
	function showPage()	
	{
		$this->spots = $this->getSpotsByPlace($this->key);
		parent::showPage();
	}
}
class ThumbsLocation extends SpotsThumbs
{
	function showPage()	
	{
		$geocode = getGeocode($address = $this->props->get('location'));
		dumpVar($geocode, "lox");
		extract($geocode);
		if ($stat != 'success')
			jfDie("getGeocode($address) failed with status=" . $lox['stat']);
		
		$this->title = sprintf("Spots within <b>%s</b> miles of <i>%s</i>", $radius = $this->props->get('radius'), $address);		
		$this->spots = $this->build('DbSpots', array('type' => 'radius', 'lat' => $lat, 'lon' => $lon, 'radius' => $radius));
		dumpVar($this->spots->size(), "NUM spots");

		parent::showPage();
	}
}
class ThumbsSpotId extends SpotsThumbs
{
	var $extralink = 'RoundTripSpotDlg';
	function showPage()	
	{
		$spot = $this->build('DbSpot', $this->key);
		$this->title = sprintf("Spots within %s miles of <i>%s</i>", $radius = $this->props->get('radius'), $spot->name());

		$this->spots = $this->build('DbSpots', array('type' => 'radius', 
			'lat' => $lat = $spot->lat(), 
			'lon' => $lon = $spot->lon(), 
			'radius' => $radius));

		parent::showPage();
	}
}
class XXXSpotsRadius extends SpotsThumbs
{
	var $searchParm = "spotkey";
	function showPage()	
	{
		if ($this->props->isProp('spotlist_location'))
		{
			$geocode = getGeocode($address = $this->props->get('spotlist_location'));
			dumpVar($geocode, "lox");
			$this->caption = sprintf("Spots within <b>%s</b> miles of <i>%s</i>", $radius = $this->props->get('spotlist_radius'), $address);
			$this->headerClause = sprintf("<a href='?page=map&type=radius&key=%s&lat=%s&lon=%s'>View as Map</a>", $radius, $geocode['lat'], $geocode['lon']);
		}
		else if ($this->key == 'id')
		{
			$spot = $this->build('DbSpot', $spotid = $this->props->get('id'));
			$geocode = array(
				'lat' => ($lat = $spot->lat()), 
				'lon' => ($lon = $spot->lon()), 
			);
			$this->caption = sprintf("Spots within <b>%s</b> miles of %s", $radius = $this->props->get('radius'), $spot->name());
			$this->headerClause = sprintf("<a href='?page=map&type=spot&key=%s&radius=%s'>View as Map</a>", $spotid, $radius);
		}
		else
		{
			$geocode = array(
				'lat' => $this->props->get('lat'), 
				'lon' => $this->props->get('lon'), 
			);
			$this->caption = sprintf("Spots within <b>%s</b> miles of <i>(%s,%s)</i>", $radius = $this->key, $geocode['lat'], $geocode['lon']);
			$this->headerClause = sprintf("<a href='?page=map&type=radius&key=%s&lat=%s&lon=%s'>View as Map</a>", $radius, $geocode['lat'], $geocode['lon']);
		}

		$this->spots = $this->build('DbSpots', array('type' => 'radius', 'lat' => $geocode['lat'], 'lon' => $geocode['lon'], 'radius' => $radius));
		

		dumpVar($this->spots->size(), "this->spots->size()");		
		$this->spots->random(21);
		parent::showPage();
	}
}

class OneTripLog extends ViewWhu
{
	var $file = "triplog.ihtml";   
	function showPage()	
	{
		$trip = $this->build('Trip', array('type' => 'id', 'data' => ($tripid = $this->key)));
		$days = $this->build('DbDays', $tripid);
		$this->template->set_var('TRIP_NAME', $this->caption = $trip->name());
		$this->template->set_var('TRIP_ID', $trip->id());
		$this->template->set_var('REL_PICPATH', iPhotoURL);
		$this->makeTripWpLink($trip);					// Aug 20 use new WP link code for WP cell
		
		// whiffle the days for this trip 
		for ($i = $iPost = $prevPostId = 0, $nodeList = array(); $i < $days->size(); $i++) 
		{
			$day = new WhuDayInfo($days->one($i));
			$day = $this->build('DayInfo', $days->one($i));

			// easy stuff - date mileage name ...
			$row = array('day_name' => $day->dayName(), 'miles' => $day->miles(), 'cum_miles' => number_format($day->cumulative()));
			$row['nice_date'] = Properties::prettyShort($row['day_date'] = $day->date(), "M"); 
			$row['short_date'] = Properties::prettyShortest($row['day_date']); 
			$row['title_date'] = Properties::prettyDate($row['day_date']); 
			$row['trip_year'] = substr($row['day_date'], 0, 4); 
			$row['day_number'] = (($i < 9) ? 'day ' : '') . ($i + 1);

			// Stop or Spot?
			$parms = array('day', 'date', $day->date());
			if ($day->hasSpot())
				$parms = array('spot', 'id', $day->spotId());
			// $parms = $day->hasSpot() ? array('spot', 'id', $day->spotId()) : array('stop', 'date', $day->date());
			$j = 0;
			foreach (array('SDPAGE', 'SDTYPE', 'SDKEY') as $v)
			{
				$row[$v] = $parms[$j++];
			}
			$row['stop_name'] = $day->nightName();				
			// $row['stop_desc'] = $day->baseExcerpt($day->nightDesc(), 30);   // NOT used in OneTripDays
			
			$this->picStuff($i, $day, $row);
			
			// which post?
			$row['wp_id'] = $day->postId();
			if ($row['wp_id'] > 0) 
			{
				if ($prevPostId != $row['wp_id']) {
					$prevPostId = $row['wp_id'];
			// dumpVar($row, "$i row, pp=$prevPostId");
					$post = $this->build('Post', array('quickid' => $prevPostId));
					$pName = $post->baseExcerpt($post->title(), 25);
					$iPost++;
				}			
				$row['day_post'] = $pName;
				$row['story_link'] = $this->makeWpPostLink($prevPostId, $row['day_date']);
				$row['POST_CLASS'] = '';
			}
			else
				$row['POST_CLASS'] = "class='hidden'";
			// if ($i > 5)			exit;
			$nodeList[] = $row;		
		}		
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));                                
		$loop->do_loop($nodeList);

		$faves = $this->build('Faves', array('type' => 'tripdates', 'start' => $trip->startDate(), 'end' => $trip->endDate()));
		$faves->getSome(12);
		$this->headerGallery($faves);

		parent::showPage();
	}
	function picStuff($i, $day, &$row) 
	{
		$row['PIC_LINK'] = (($npic = $day->pics()->size()) > 0) ? (new WhuLink('pics', 'date', $day->date(), "[$npic]", "today's images"))->url() : '';
	}
}
class OneTripDays extends OneTripLog
{
	var $file = "tripdays.ihtml"; 
	 
	function picStuff($i, $day, &$row) 
	{
		$pic = $day->pics()->randomOne();
		if ($pic == NULL) {
			$row["picshow_0"] = ' hidden';
			$row["nopic_0"] = ' hideme';
			// $row["nopic_0"] = '';
		}
		else {
			$row["picshow_0"] = '';
			$row["nopic_0"] = ' hideme';
			$row["picid_0"] = $pic->id();
			$row["pictitle_0"] = $pic->caption();
			$row["picfilename_0"] = $pic->filename();
			$row['picfolder'] = $pic->folder();
		}
				
		$row['day_desc'] = $day->dayDesc();
		$row['week_day'] = $day->weekday();
	}
}

class OneDay extends ViewWhu
{
	var $file = "oneday.ihtml";   
	function showPage()	
	{
		$dayid = $this->key;
 	 	$day = $this->build('DayInfo', $dayid);

		$this->caption = Properties::prettyDate($date = $day->date());
		$this->template->set_var('DATE', $date);
		$this->template->set_var('PRETTY_DATE', WhuProps::verboseDate($date));
		
		$this->template->set_var('ORDINAL', $day->day());
		$this->template->set_var('MILES', $day->miles());
		$this->template->set_var('CUMMILES', $day->cumulative());
		
		if ($day->hasStory())
		{
			$this->template->set_var("VIS_CLASS_TXT", "");
			$this->template->set_var('WPID', $wpid = $day->postId());
			assert($wpid > 0, "should have post id");			
			$this->template->set_var('STORY', $this->build('Post', array('quickid' => $wpid))->title());
			$this->template->set_var('STORY_LINK', $this->makeWpPostLink($wpid));
		}
		else
			$this->template->set_var("VIS_CLASS_TXT", "class='hidden'");

		$this->template->set_var('DAY_NAME', $day->dayName());
		$this->template->set_var('DAY_DESC', $day->dayDesc());
		$this->template->set_var('PM_STOP', $day->nightNameUrl());
		
		$this->template->set_var('NIGHT_DESC', $desc = $day->nightDesc());
		$this->template->set_var('NIGHT_VIS', ($desc == '') ? 'hideme' : '');
		
		// do next|prev nav - as long as I have yesterday, show where I woke up today
		$navday = $this->build('DbDay', $d = $day->yesterday());
		
		if ($navday->hasData)			// for the first day of the trip, there is no yesterday
		{
			$this->template->set_var('AM_STOP', $this->build('DayInfo', $d)->nightNameUrl());
			$this->template->set_var('P_VIS', '');
			$this->template->set_var('P_KEY', $d);
		}
		else {
			$this->template->set_var('AM_STOP', 'home');
			$this->template->set_var('P_VIS', 'hidden');
		}
		$navday = $this->build('DbDay', $d = $day->tomorrow());
		if ($navday->hasData)			// check for the very last day
		{
			$this->template->set_var('N_VIS', '');
			$this->template->set_var('N_KEY', $d);
		}
		else {
			$this->template->set_var('N_VIS', 'hidden');
		}

		$trip = $this->build('DbTrip', $id = $day->tripId());
		$this->template->set_var("TRIP_ID", $id);
		$this->template->set_var("TRIP_NAME", $trip->name());
		
		$faves = $this->build('Faves', array('type' => 'oneday', 'date' => $date));
		$faves->getSome(12, $this->build('Pics', array(	'date' => $date)));
		$this->headerGallery($faves);

		parent::showPage();
	}
}

class OnePhoto extends ViewWhu
{
	var $file = "onepic.ihtml";   
	function showPage()	
	{
		parent::showPage();
 	 	$pic = $this->build('Pic', $picid = $this->key);		
		
		$this->template->set_var('COLLECTION_NAME', Properties::prettyDate($date = $pic->date()));
		$this->caption = sprintf("%s on %s", $pic->kind(), Properties::prettyShort($date));

		$this->template->set_var('DATE', $date);
		$this->template->set_var('PRETTIEST_DATE', WhuProps::verboseDate($date));
		$this->template->set_var('PIC_TIME', Properties::prettyTime($pic->time()));
		$this->template->set_var('PIC_CAMERA', $pic->cameraDesc());
		$this->template->set_var('PIC_PLACE', $pic->place());
		$this->template->set_var('PICFILE_NAME', $pic->filename());
		$this->template->set_var('WF_IMAGES_PATH', $pic->folder());
		$this->template->set_var('WF_IMAGES_FILENAME', $pic->filename());
		$this->template->set_var('VIS_NAME', $name = $pic->caption());
		$this->template->set_var('REL_PICPATH', iPhotoURL);
		$this->template->set_var('VID_SPOT_VIS', 'hideme');
		
		$shape = $pic->shape();
		dumpVar($pic->rawshape(), "shape = $shape --- pic->rawshape()");
		$colwids = array('por' => 5, 'lan' => 7, 'pano' => 12, 'xx' => 6);
		$this->template->set_var('PIC_COL_WID', $colwids[$shape]);

		
 	 	$trip = $this->build('Trip', $date);
		$this->template->set_var('TRIP_NAME', $trip->name());
		$this->template->set_var('TRIP_ID', $trip->id());
		
 	 	$day = $this->build('DayInfo', $date);
		$this->template->set_var('WPID', $wpid = $day->postId());										// is there a story?
		if ($wpid > 0)
		{
			$this->template->set_var('STORY', $this->build('Post', array('quickid' => $wpid))->title());
			// $this->template->set_var('STORY', $this->build('Post', $wpid)->title());
			$this->template->set_var('STORY_LINK', $this->makeWpPostLink($wpid));
			$this->template->set_var("STORY_VIS", '');
		}
		else
			$this->template->set_var('STORY_VIS', 'hideme');
		
		
		$this->template->set_var('PM_STOP', $day->nightNameUrl());
		
		$keys = $this->build('Categorys', array('picid' => $picid));
		for ($i = 0, $rows = array(); $i < $keys->size(); $i++)
		{
			$key = $keys->one($i);
			$row = array('WF_CATEGORIES_ID' => $key->id(), 'WF_CATEGORIES_TEXT' => $key->name());
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		$gps = $pic->latlon();
		if ($pic->cameraDoesGeo())
			$gps['geo'] = true;
		dumpVar($gps, "gps");
		if ($this->setLittleMap(array_merge($gps, array('name' => Properties::prettyDate($pic->date()), 'desc' => $name))))
		{
			$this->template->set_var('GPS_VIS', '');
			$this->template->set_var('GPS_LAT', $gps['lat']);
			$this->template->set_var('GPS_LON', $gps['lon']);
		}
		else
			$this->template->set_var('GPS_VIS', 'hideme');
		
		$this->template->set_var('PREVPIC', $id = $pic->prev()->id());
		$this->template->set_var('P_VIS', ($id == 0) ? 'class="hidden"' : '');
		$this->template->set_var('NEXTPIC', $id = $pic->next()->id());
		$this->template->set_var('N_VIS', ($id == 0) ? 'class="hidden"' : '');
	}
}
class OneNewStylePhoto extends OnePhoto
{
	var $file = "oneNewStylepic.ihtml";
}
class OneMap extends ViewWhu
{
	var $file = "onemap.ihtml";
	var $loopfile = 'mapBoundsLoop.js';
	var $marker_color = '#535900';	// '#8c54ba';

	var $tripMap = '';
	var $spotlistMap = 'hideme';
	// var $spotlistMap = 'class="hideme"';
	function showPage()	
	{
		$eventLog = array();
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var('PAGE_VAL', 'day');
		$this->template->set_var('TYPE_VAL', 'date');
		$this->template->set_var('MARKER_COLOR', $this->marker_color);
		$this->template->set_var('WHU_URL', $this->whuUrl());

		$this->template->set_var('TRIP_MAP'    , $this->tripMap);
		$this->template->set_var('SPOTLIST_MAP', $this->spotlistMap);
		dumpVar($this->spotlistMap, "OneMap Title visibility: TRIP_MAP=$this->tripMap, SPOTLIST_MAP");

		$tripid = $this->trip();		// local function		
 	 	$trip = $this->build('Trip', $tripid);		
		$this->template->set_var('PAGE_TITLE', $this->name = $trip->name());
		$this->template->set_var('TRIP_ID', $tripid);
		$this->caption = "Map for $this->name";
		$this->makeTripWpLink($trip);

		// - - - Header PICS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// $pics = $this->build('Faves', array('type' =>'folder', 'data' => $trip->folder()));
		// $pics->getSome(12);
		// $this->headerGallery($pics);
		
		if ($trip->hasMapboxMap())
		{
			$filename = $trip->mapboxJson();
			$fullpath = MAP_DATA_PATH . $filename;
dumpVar($fullpath, "Mapbox fullpath");
			$this->template->set_var("MAP_JSON", file_get_contents($fullpath));
			$this->template->setFile('JSON_INSERT', 'mapjson.js');
			$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		}	
		else if ($trip->hasGoogleMap())
		{
			$this->template->set_var("KML_FILE", $trip->gMapPath());
			$this->template->setFile('JSON_INSERT', 'mapkml.js');
			$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		}	
		else 			// NO map, do our connect the dots trick
		{	
			$this->template->set_var("JSON_INSERT", '');
			$this->template->set_var("CONNECT_DOTS", 'true');		// there is no route map, so connect the dots with polylines
		}
		dumpVar($this->loopfile, "this->loopfile");
		$this->template->setFile('LOOP_INSERT', $this->loopfile);
		
 	 	$days = $this->build('DbDays', $tripid);
		for ($i = 0, $rows = array(), $prevname = '*'; $i < $days->size(); $i++)
		{
			$day = $this->build('DayInfo', $days->one($i));

			$row = array('marker_val' => $this->markerIndex($i), 'point_lon' => $day->lon(), 'point_lat' => $day->lat(), 
										'key_val' => $day->date(), 'link_text' => Properties::prettyDate($day->date()));

			$spotName = $day->nightName();
			$row['point_name'] = addslashes($day->hasSpot() ? $this->spotLink($spotName, $day->spotId()) : $spotName);
						 
// dumpVar($row, "row $i");
			if ((float)$row['point_lat'] * (float)$row['point_lon'] == 0) {						// skip if no position
				$eventLog[] = "NO POSITION! $i row";
				$eventLog[] = $row;
				continue;
			}
			if ($spotName == $prevname) {											// skip if I'm at the same place as yesterday
				// $eventLog[] = "skipping same $i: {$spotName}";
				continue;                       
			}
			$prevname = $spotName;

			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'one' =>'node_row', 'none_msg' => "", 'noFields' => true));
		$loop->do_loop($rows);
				
		if (sizeof($eventLog))
			dumpVar($eventLog, "Event Log");
		parent::showPage();
	}
	// function getMetaDesc()	{	return "Map for the WHUFU trip called '$this->name'";	}
	function trip()		{ 
		return $this->key; 
	}
}

class RadiusMapBase extends OneMap
{
	var $tripMap = 'hideme';
	var $spotlistMap = '';
	function showPage()	
	{
		$this->makeSpotsListHeader($this->title, "M", $this->extralink);
		$this->caption = "Map of " . $this->title;

		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var("JSON_INSERT", '');
		$this->template->setFile('LOOP_INSERT', $this->loopfile);
		$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		$this->template->set_var('PAGE_VAL', 'spot');
		$this->template->set_var('TYPE_VAL', 'id');
		$this->template->set_var('WHU_URL', $this->whuUrl());

		$this->template->set_var('TRIP_MAP'    , $this->tripMap);
		$this->template->set_var('SPOTLIST_MAP', $this->spotlistMap);
		dumpVar($this->spotlistMap, "RadiusMapBase Title visibility: TRIP_MAP=$this->tripMap, SPOTLIST_MAP");

		$this->template->set_var('MAP_LAT', $this->props->get('lat'));
		$this->template->set_var('MAP_LON', $this->props->get('lon'));
		
		$markers = array('CAMP' => 'campsite', 'LODGE' => 'lodging', 'HOTSPR' => 'swimming', 'PARK' => 'parking', 'NWR' => 'wetland');
		$hiPriority = array('CAMP', 'LODGE', 'PARK');		// earliest type wins

		for ($i = 0; $i < $this->spots->size(); $i++)
		{
			$spot = $this->spots->one($i);

			if ($spot->noPosition()) {						// skip if no GPS coordinates
				$eventLog[] = "NO POSITION! $i row";
				$eventLog[] = $row;
				continue;
			}
		
			$row = array('point_lon' => $spot->lon(), 'point_lat' => $spot->lat(), 'marker_color' => $this->markerColor($i),
										'point_name' => addslashes($spot->name()), 'key_val' => $spot->id(), 'link_text' => 'info page');
			
			$types = $spot->prettyTypes();
			foreach ($types as $k => $v)	{
				// dumpVar($v, "$i $k v");
				if ($k == 'CAMP' && $v == 'parking lot')
					$k = 'PARK';
				if (!isset($markers[$k]))						// don't show some types (eg HOUSE = friend's house)
					break;
				$row['marker_val'] = $markers[$k];
				if (in_array($k, $hiPriority))			// this means I can control who wins by listing, say LODGE before CAMP
					break;
			}
			if (isset($row['marker_val']))				// don't show if I didn't give it a marker
				$this->rows[] = $row;										
			// dumpVar($row, "$i row");
		}
		// dumpVar($rows[2], "rows[2]");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'node_row'));
		$loop->do_loop($this->rows);
		
		ViewWhu::showPage();
	}
	// function getMetaDesc()	{	return $this->title;	}
	function markerColor($i)	{	return $this->marker_color;	}
}
class XXXRadiusMap extends RadiusMapBase
{	
	function showPage()	
	{
		$this->spots = $this->build('DbSpots', array('type' => 'radius', 
			'lat' => ($lat = $this->props->get('lat')), 
			'lon' => ($lon = $this->props->get('lon')), 'radius' => $this->key));
		dumpVar($this->spots->size(), "NUM spots");
	
		// Start the map with a fake point that shows the center
		$center = array('point_lat' => $lat, 'point_lon' => $lon,
												'key_val' => 0, 'link_text' => '', 'marker_val' => 'cross', 'marker_color' => '#000');
		$center['point_name'] = "Spots within $this->key miles of <i>($lat, $lon)</i>";				
		$this->rows = array($center);
		
		$this->template->set_var('PAGE_TITLE', $this->title = $center['point_name']);
		$this->template->set_var('RADIUS', $this->key);
		$this->template->set_var('LAT', $lat);
		$this->template->set_var('LON', $lon);

		parent::showPage();
	}
}
class MapSpotId extends RadiusMapBase
{
	var $extralink = 'RoundTripSpotDlg';
	function showPage()	
	{
		// get the spot which defines the center of the search
		$spot = $this->build('DbSpot', $this->key);
		// $spot = $this->build('DbSpot', array('type' => 'id', 'data' => $this->key));
		
		// now get the spots
		$this->spots = $this->build('DbSpots', array('type' => 'radius', 
			'lat' => $lat = $spot->lat(), 
			'lon' => $lon = $spot->lon(), 
			'radius' => ($radius = $this->props->get('radius'))));

		$this->rows = array();
		$this->title = $this->caption = sprintf("Spots within %s miles of <i>%s</i>", $radius, $spot->name());

		parent::showPage();
	}
	function markerColor($i) { return ($i <= 0) ? '#060059' : $this->marker_color; }
}
class MapSpotType extends RadiusMapBase
{
	var $radiusMap = 'class="hideme"';
	var $spotlistMap = '';
	function showPage()	
	{
		$this->spots = $this->getSpotsByType($this->key);
		// $this->caption = $this->spotTypes[$this->key];
		parent::showPage();
	}
}
class MapPlaceId extends RadiusMapBase
{
	function showPage()	
	{
		$this->spots = $this->getSpotsByPlace($this->key);
		parent::showPage();
	}
}
class SpotKeywordsMap extends RadiusMapBase
{
	function showPage()	
	{
		$this->getSpotsByKeyword();			// set title and get Spots
		parent::showPage();
	}
}
class MapLocation extends RadiusMapBase 
{
	var $extralink = 'RoundTripSearchDlg';
	function showPage()	
	{
		$geocode = getGeocode($address = $this->props->get('location'));
		dumpVar($geocode, "lox");
		extract($geocode);
		if ($stat != 'success')
			jfDie("getGeocode($address) faled with status=" . $lox['stat']);
		
		$this->title = sprintf("Spots within <b>%s</b> miles of <i>%s</i>", $radius = $this->props->get('radius'), $address);
		$this->spots = $this->build('DbSpots', array('type' => 'radius', 'lat' => $lat, 'lon' => $lon, 'radius' => $radius));
		dumpVar($this->spots->size(), "NUM spots");
	
		// Start the map with a fake point that shows the center
		$center = array('point_lat' => $lat, 'point_lon' => $lon, 'point_name' => $address, 
												'key_val' => 0, 'link_text' => '', 'marker_val' => 'cross', 'marker_color' => '#000');
		$this->rows = array($center);

		parent::showPage();
	}
}

class About extends ViewWhu
{
	var $file = "about.ihtml";   
	var $caption   = "All about WHUFU";		
	var $meta_desc = "All about WHUFU";		
	function showPage()	
	{
		$site = $this->build('Trips', 'handle');
		$this->template->set_var('N_TXT', $site->numPosts());
		$this->template->set_var('N_PIC', $site->numPics());
		$this->template->set_var('N_SPO', $site->numSpots());
		$this->template->set_var('N_TRI', $site->numTrips());

		parent::showPage();
	}
}
class Search extends ViewWhu
{
	var $file = "search.ihtml";   
	var $caption = "Find Spots. Browse Pictures"; 
	var $meta_desc = "Searching for WHUFU Campgrounds, Hot Springs, Refuges";		
	 
	function showPage()	
	{
		// $searchtype = array('spots' => 'SPOT_1');
		$this->template->set_var('SHOW_1', ($this->key == 'spots'   ) ? ' show' : '');
		$this->template->set_var('SHOW_2', ($this->key == 'trips'   ) ? ' show' : '');
		$this->template->set_var('SHOW_3', ($this->key == 'pics'    ) ? ' show' : '');
		$this->template->set_var('SHOW_4', ($this->key == 'spotkey' ) ? ' show' : '');
		
		$this->template->set_var('SPOTSEARCH_LOCATION_VAL', $this->props->getDefault('addy', ''));
		$this->template->set_var('SPOTSEARCH_RADIUS_VAL'  , $this->props->getDefault('rad', '100'));

		// key = spots -- has no code, it's all hard coded links!
		// key = trips -- populate the Trip categories
		foreach (AllTrips::$cats as $k => $v) 
		{
			$this->template->set_var("LISTNAME_" . substr($k, 3), $v) . " Trips";
		}
		
		// key = pics -- build huge string of picture keywords (categories). Not bothering to make it a template loop right now
 	 	$cats = $this->build('Categorys', array('type' => 'piccats'));
		// dumpVar(sizeof($cats->data), "cats->data");
		// dumpVar($cats->data[0], "cats->data[0]");
		for ($i = 0, $rows = array(), $str = ''; $i < $cats->size(); $i++)
		{
			$cat = $cats->one($i);
			if (($num = $cat->npics()) == 0)
				continue;
			$str .= sprintf('<label class="form-check-label"><input class="form-check-input" type="checkbox" name="CHK_%s">%s (%s)</label>', $cat->id(), $cat->name(), $num);
		}
		$this->template->set_var('PIC_CATS', $str);

		// key = spotkey -- build huge string of Spot keywords. Again, don;t bothering to make it a template loop right now
		// get the Spot keyword list from the dummy Thing
		$keyObj = $this->build('UIThing', '');	
		$keywords = $keyObj->getSpotKeywords();
		// dumpVar($keywords, "keywords");
		for ($i = 0, $str = ''; $i < sizeof($keywords); $i++) 
		{
			$keyword = $keywords[$i];
			$str .= sprintf("<a href=?page=spots&type=keyword&key=%s>%s(%s)</a>, &nbsp;", $keyword[0], str_replace( '_', ' ', $keyword[0]), $keyword[1]);
		}
		$this->template->set_var('SPOT_KEYS', $str);
		
		parent::showPage();
	}
}
class SearchResults extends ViewWhu
{
	var $file = "searchresults.ihtml";   
	function showPage()	
	{
		$searchterm = $this->props->get('search_text');

		$this->template->set_var('SEARCHTERM', $searchterm);
		$qterm = sprintf("%%%s%%", $searchterm);
		dumpVar($qterm, "qterm");
		
		$pics = $this->build('Pics', array('type' =>'textsearch', 'data' => $qterm)); // always show header
		$pics->getSome(12);
		$this->headerGallery($pics);

		if ($this->props->get("CHK_pic") == "on")			// ---------------------------------- pictures
		{
			for ($i = 0, $days = array(); $i < $pics->size(); $i++)
			{
				$pic = $pics->one($i);
				if (isset($days[$date = $pic->date()]))
					$days[$date]++;
				else
					$days[$date] = 1;
			}	
			$str = '';
			foreach ($days as $k => $v) 
			{
				$str .= sprintf("<li><a href='?page=pics&type=date&key=%s'>(%s) %s</a></li>", $k, $v, Properties::prettyDate($k));
			}
			$this->template->set_var('PICLIST', $str);			
			$this->template->set_var('SHOW_pic', "");
		}
		else
			$this->template->set_var('SHOW_pic', "class='hideme'");
			
		if ($this->props->get("CHK_spo") == "on")				// ---------------------------------- spots
		{
			$spots = $this->build('DbSpots', array('type' =>'textsearch', 'data' => $qterm));
			for ($i = 0, $str = '', $rows = array(); $i < $spots->size(); $i++)
			{
				$spot = $spots->one($i);
				$str .= sprintf("<li><a href='?page=spot&type=id&key=%s'>%s</a></li>", $spot->id(), $spot->name());
			}
			if ($str == '')
				$str = "<b>$searchterm</b> not found in Spots.";
			$this->template->set_var('SPOTLIST', $str);
			$this->template->set_var('SHOW_spo', "");
		}
		else
			$this->template->set_var('SHOW_spo', "class='hideme'");
			
		if ($this->props->get("CHK_log") == "on")				// ---------------------------------- logs
		{
			$days = $this->build('Dbdays', array('searchterm' => $qterm));
			// $days = $this->build('Dbdays', array('type' =>'textsearch', 'data' => $qterm));
			for ($i = 0, $str = '', $rows = array(); $i < $days->size(); $i++)
			{
				$day = $days->one($i);
				$str .= sprintf("<li><a href='?page=day&type=date&key=%s'>%s</a></li>", $day->date(), Properties::prettyDate($day->date()));
			}	
			$this->template->set_var('DAYLIST', $str);
			$this->template->set_var('SHOW_log', "");
		}
		else
			$this->template->set_var('SHOW_log', "class='hideme'");

		if ($this->props->get("CHK_txt") == "on")				// ---------------------------------- txts
		{
			$txts = $this->build('Posts', array('type' =>'textsearch', 'data' => $searchterm));
			for ($i = 0, $str = '', $rows = array(); $i < $txts->size(); $i++)
			{
				$txt = $txts->one($i);
				// dumpVar($txt->wpid(), "txt->wpid()");
				$str .= sprintf("<li><a href='%s'>%s &ndash; %s</a></li>", $this->makeWpPostLink($txt->wpid()), $txt->date(), $txt->title());
				// $str .= sprintf("<a href='%s'>%s</a> &bull; ", $this->makeWpPostLink($txt->wpid()), $txt->title());
			}	
			$this->template->set_var('TXTLIST', $str);
			$this->template->set_var('SHOW_txt', "");
		}
		else
			$this->template->set_var('SHOW_txt', "class='hideme'");

		parent::showPage();
	}
}

// Old stuff =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

class TripPictures extends ViewWhu
{
	var $file = "trippics.ihtml";
	function showPage()	
	{
		parent::showPage();
		
		$trip = $this->build('Trip', array('type' => 'id', 'data' => $this->key));
		$this->template->set_var('GAL_TITLE', $this->caption = $trip->name());
		$this->template->set_var('TRIP_ID', $this->key);
		$this->makeTripWpLink($trip);
		
		// $tripvids = $this->build('Vids', array('tripid' => $this->key));
		if (($nvid = $trip->hasVideos()) == 0)		// hack: hasVideos returns the number
		{
			$this->template->set_var('AND_VIDS', '');
			$this->template->set_var('NUM_VIDS', '');
		}
		else
		{
			$this->template->set_var('AND_VIDS', '/videos');
			$this->template->set_var('NUM_VIDS', " &bull; $nvid videos");
		}

		$this->template->set_var('REL_PICPATH', iPhotoURL);
		$days = $this->build('DbDays', $this->key);	
		for ($i = $count = 0, $rows = array(); $i < $days->size(); $i++)
		{
			$day = $days->one($i);
			$row = array('gal_date' => $date = $day->date());

			$pics = $this->build('Pics', array('date' => $date));
			$row['date_count'] = $dc = $pics->size();

			if ($dc == 0)		// all done if there's no pix or vids
				continue;
			
			if (($nvid = $day->hasVideos()) > 0)
			{
				$row['vid_link'] = sprintf(" &bull; <a href='?page=vids&type=date&key=%s'>video%s</a>", $date, (($nvid > 1) ? 's' : ''));
			}
			else 
				$row['vid_link'] = '';
			
			$row['nice_date'] = Properties::prettyShortest($date);
			$pic = $pics->favored();		// returns one picture

			$row['pic_name'] = $pic->filename();
	 		$row['wf_images_path'] = $pic->folder();
			// dumpVar($row, "row day $i");
			// exit;
			$row['binpic'] = $pic->thumbImage();
			// if (strlen($row['binpic']) > 100) {			// hack to show the slow image if the thumbnail fails on server
			// 	$row['use_binpic'] = '';
			// 	$row['use_image']  = 'hideme';
			// } else {
				$row['use_binpic'] = 'hideme';
				$row['use_image']  = '';
			// }
			$rows[] = $row;
			$count += $dc;
		}
		// dumpVar($rows, "rows $count");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		$this->template->set_var('NUM_DAYS', $days->size());
		$this->template->set_var('NUM_PICS', $count);
		
		$this->meta_desc = "Image galleries for the WHUFU trip called '$this->caption'";		
	}
}
class VideoGallery extends ViewWhu
{
	var $file = "videos.ihtml";   
	var $galtype = "vid";
	var $message = '';
	function showPage()	
	{
		parent::showPage();
		
		$videos = $this->build('Visuals', (array('vid' => 'all'))); 	
		// dumpVar($videos->data[0], "vids->data[0]");
		
		for ($i = 0, $rows = array(), $fold = ''; $i < $videos->size(); $i++) 
		{
			$video = $videos->one($i);
			// dumpVar(sprintf("id %s, vid? %s: %s", $video->id(), $video->dbValue('wf_resources_id'), $video->caption()), "$i Gallery");
			
			$vid = $this->build('Video', $video);
			// $vid->dump('VideoGallery');
			// dumpVar($vid->token(), "vid->token()");
			$row = array('PIC_ID' => $vid->id(), 'VID_TOKEN' => $vid->token(), 'VID_CAPTION' => $vid->caption());
			$rows[] = $row;	

			$this->collectHilites($i, $vid);
		}
		dumpVar($rows[0], "rows");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);

		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' => 'hilite_row'));
		$loop->do_loop($this->showHilites());
	}
	function collectHilites($i, $vid) {}
	function showHilites() { return array(array()); }
}
class TripVideos extends VideoGallery
{
	// var $hilites = array();
	// function collectHilites($i, $vid) {
	// 	$trip = $this->build('DbTrip', $vid->date());
	// 	dumpVar(boolStr($trip->hasData), "collectHilites($i) " . $vid->date());
	// 	dumpVar($trip->data, "trip->data");
	// 	exit;
	// 	if ($trip->hasData) {
	// 		$this->hilites[] = array('vindex' => $i + 1);
	// 	}
	// }
	// function showHilites() { return array($this->hilites); }
}
class OneVideo extends ViewWhu
{
	var $file = "onevid.ihtml";   
	function showPage()	
	{
		parent::showPage();
 	 	$vid = $this->getVideo();
		// $vid->dump("OneVideo");
		
		$this->template->set_var('COLLECTION_NAME', Properties::prettyDate($date = $vid->date()));
		$this->template->set_var('DATE', $date);
		$this->template->set_var('PRETTIEST_DATE', WhuProps::verboseDate($date));
		$this->template->set_var('PIC_TIME', Properties::prettyTime($vid->time()));
		$this->template->set_var('PIC_CAMERA', $vid->cameraDesc());
		$this->template->set_var('VIS_NAME', $vid->name());
		$this->template->set_var('VID_TOKEN', $vid->token());
		
 	 	$trip = $this->build('DbTrip', $date);
		$this->template->set_var('PIC_TRIP', $trip->name());
		$this->template->set_var('TRIPID', $trip->id());
		
 	 	$day = $this->build('DayInfo', $date);
		$this->template->set_var('WPID', $wpid = $day->postId());
		if ($wpid > 0)
		{
			$this->template->set_var('STORY', $this->build('Post', array('quickid' => $wpid))->title());
			$this->template->set_var('STORY_LINK', $this->makeWpPostLink($wpid));
			$this->template->set_var("STORY_VIS", '');
		}
		else
			$this->template->set_var('STORY_VIS', 'hideme');

		if (($spotId = $vid->spotId()) > 0)
		{
			$this->template->set_var('SPOT_ID', $spotId);
			$this->template->set_var('SPOT_NAME', $this->build('DbSpot', $spotId)->name());
			$this->template->set_var("SPOT_VIS", '');
		}
		else
			$this->template->set_var('SPOT_VIS', 'hideme');
			
		if ($this->setLittleMap(
							array('lat' => $vid->lat(), 'lon' => $vid->lon(), 'name' => Properties::prettyDate($date), 'desc' => $vid->name())))
		{
			$this->template->set_var('GPS_VIS', '');
			$this->template->set_var('GPS_LAT', $vid->lat());
			$this->template->set_var('GPS_LON', $vid->lon());
		}
		else
			$this->template->set_var('GPS_VIS', 'hideme');
		
		// keywords
		$keys = $this->build('Categorys', array('picid' => $vid->id()));
		for ($i = 0, $rows = array(), $ksep = ''; $i < $keys->size(); $i++)
		{
			$key = $keys->one($i);	
			$row = array('WF_CATEGORIES_ID' => $key->id(), 'WF_CATEGORIES_TEXT' => $key->name(), 'K_SEP' => $ksep);
			$rows[] = $row;
			$ksep = '&bull; ';
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);

		$this->caption = sprintf("%s on %s", $vid->kind(), Properties::prettyShort($date));

		$pageprops = array();
		$pageprops['pkey'] = $vid->prev()->id();
		$pageprops['nkey'] = $vid->next()->id();
		$pageprops['middle'] = true;		
		$pageprops['mlab'] = '<a href="?page=vids&type=home">back to Videos page</a>';		
	}
	function getVideo() 
	{
 	 	return $this->build('Video', $this->build('Visual', $vidid = $this->key));
	}
}
class DateVideos extends OneVideo
{
	function getVideo() 
	{
 	 	$vids = $this->build('Videos', array('date' => $this->key));
		// dumpVar($vids->data, "vids->data");
		return $vids->one(0);
	}
}

class Gallery extends ViewWhu
{
	var $file = "gallery.ihtml";
	var $galtype = "UNDEF";  
	var $message = '';
	var $titlePrefix = '';
	var $afterMessage = '';
	function showPage()	
	{
		$this->template->set_var('GAL_TYPE', $this->galtype);
		$this->template->set_var('GALLERY_TITLE', $this->galTitle);
		$this->template->set_var('GAL_COUNT', $this->props->get('extra'));
		$this->template->set_var('TODAY', $this->galTitle);
		$this->template->set_var('REL_PICPATH', iPhotoURL);
		$this->template->set_var('IPIC', 0);
		$this->template->set_var('RGMSG', $this->message);

		$this->template->set_var('SHOW_AFTER', ($this->afterMessage == '') ? ' hideme' : '');	
		$this->template->set_var('AFTER_MESSAGE', $this->afterMessage);	
		
		$pics = $this->getPictures($this->key);			
		dumpVar($pics->size(), "pics->size()");	
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'none_msg' => 'no pictures'));
		$loop->do_loop($this->makeGalleryArray($pics));		
		
		parent::showPage();
	}
}
class DateGallery extends Gallery
{
	var $galtype = "date";   
	function showPage()	
	{
		$this->template->set_var("DATE_GAL_VIS", '');
		$this->template->set_var("CAT_GAL_VIS" , 'hideme');
		$this->template->set_var("CAT_GAL_VIS1" , 'hideme');
		
		$trip = $this->build('DbTrip', (array('type' => 'date', 'data' => $this->key)));
		$this->template->set_var("TRIP_ID" , $trip->id());
		$this->template->set_var("TRIP_NAME" , $trip->name());
		
		// --  build title bar
		$this->galTitle = Properties::prettyDate($this->key);
		
		$date = $this->build('DayInfo', $this->key);
		// dumpVar($date->data, "date->data");
		if ($date->spotId() == 143)		// home!
			$place = "<i>last day of trip</i>";
		else
			$place = $date->nightNameUrl();
		$this->template->set_var("GALLERY_PLACE", $place);
		
		$this->message = sprintf("<a href='?page=pics&type=date&key=%s'>previous day</a> | <a href='?page=pics&type=date&key=%s'>next day</a>", $date->previousDayGal(), $date->nextDayGal());
		// --  end title bar
		
		$this->meta_desc = sprintf("WHUFU Picture Gallery for %s", $this->galTitle);
		parent::showPage();
	}
	function getPictures($key)	{ return $this->build('Pics', (array('date' => $key))); }
	function getCaption()				{	return "Pictures for " . $this->key;	}
	// function galleryTitle($key)	{	return Properties::prettyDate($key); }
}
class DateNoJustifyGallery extends DateGallery
{
	// var $file = "galleryNoJustify.ihtml";
}
class CatGallery extends Gallery
{
	var $maxGal = 40; 
	var $galtype = "cat";
	var $titlePrefix = '';
	function showPage()	
	{
		$this->template->set_var("DATE_GAL_VIS", 'hideme');
		$this->template->set_var("CAT_GAL_VIS" , '');
		$this->template->set_var("TITLE_PREFIX" , $this->titlePrefix);
		parent::showPage();
		
	}
	function getPictures($key)	{ return $this->pics; }	
}
class CatOneGallery extends CatGallery
{
	var $titlePrefix = 'Image keyword:';
	function showPage()	
	{
		$this->template->set_var("CAT_GAL_VIS1" , '');
		
		dumpVar($this->key, "this->key");
		$cat = $this->build('Category', $this->key);
		$this->galTitle = $cat->name();
		// dumpVar($cat, "cat");
		//  		$items = $cat->getAll("SELECT * FROM wf_categories WHERE wf_categories_id=17");
		// dumpVar($items, "items");
		
		$q = "CREATE TEMPORARY TABLE cat_pics select i.wf_images_id id, im.wf_id_2 cat, substring(i.wf_images_localtime, 1, 16) datetime, wf_images_filename, wf_images_path from wf_images i join wf_idmap im on i.wf_images_id=im.wf_id_1 where wf_type_1='pic' and wf_type_2='cat' and wf_id_2=$this->key AND i.wf_resources_id=0;";
		$cat->query($q);		
		
		$q = "SELECT count(im.wf_id_2) count, wc.wf_categories_id id, wc.wf_categories_text from cat_pics c join wf_idmap im on c.id=im.wf_id_1 JOIN wf_categories wc on wc.wf_categories_id=im.wf_id_2 where wf_type_1='pic' and wf_type_2='cat' group by wc.wf_categories_text order by count DESC";
		$otherpics = $cat->getAll($q);
		// dumpVar(sizeof($otherpics), "$q otherpics1");
		// dumpVar($otherpics, "otherpics");
		$thecat = array_shift($otherpics);
		assert($thecat['id'] == $this->key, "most numerous!");

		for ($i = 0; $i < sizeof($otherpics); $i++) 
		{
			$this->template->set_var("CAT_$i", sprintf("%s,%s", $otherpics[$i]['id'], $this->key));
			$this->template->set_var("LISTNAME_$i", sprintf("%s(%s)", $otherpics[$i]['wf_categories_text'], $otherpics[$i]['count']));
			if ($i > 8)
				break;
		}
		for (; $i < 9; $i++) 
		{
			$this->template->set_var("LISTNAME_$i", '');
			dumpVar($i, "skipping");
		}

		// do stuff below so I can create the message before the call
		$this->pics = $this->build('Pics', (array('type' => 'cat', 'data' => $this->key)));  //, 'max' => $this->key
		if (($size = $this->pics->size()) > $this->maxGal)
		{
			dumpVar($this->maxGal, "size=$size this->maxGal");
			$this->message = sprintf("Showing %s of %s images, <a href='?page=pics&type=cat&key=%s'>refresh</a> to reselect", $this->maxGal, $size, $this->key);			
			$this->pics->random($this->maxGal);
		}
		dumpVar($this->pics->size(), "NEW this->pics->size()");
		parent::showPage();
	}		
}
class CatTwoGallery extends CatGallery
{
	var $afterMessage = "<a href='?page=search&type=home&key=pics'>Choose other keyword combos</a>";
	var $titlePrefix = 'Images with both keywords:';
	function showPage()	
	{
		dumpVar($this->keys, "keys");
		$mainkey = $this->keys[0];													// $this->keys must be supplied by one of the classes below
		
		$cat = $this->build('Category', $mainkey);
		$this->template->set_var("TRIP_ID", $mainkey);
		$this->template->set_var("TODAY", '');
		$this->message = "";
		
		$q = "CREATE TEMPORARY TABLE cat_pics select i.wf_images_id id, im.wf_id_2 cat, substring(i.wf_images_localtime, 1, 16) datetime, wf_images_filename, wf_images_path from wf_images i join wf_idmap im on i.wf_images_id=im.wf_id_1 where wf_type_1='pic' and wf_type_2='cat' and wf_id_2=$mainkey AND i.wf_resources_id=0;";
		$cat->query($q);

		$this->template->set_var("CAT_GAL_VIS1" , 'hideme');
		$cat2 = $this->build('Category', $key2 = $this->keys[1]);
		// dumpVar($cat2->data, "k2=$key2 cat2->data");
		$this->galTitle = sprintf("<a href='?page=pics&type=cat&key=%s'>%s</a> and <a href='?page=pics&type=cat&key=%s'>%s</a>", $cat->id(), $cat->name(), $cat2->id(), $cat2->name());

		$q = "select c.id, c.cat, im.wf_id_2, wc.wf_categories_text, i.* from cat_pics c JOIN wf_idmap im on c.id=im.wf_id_1 JOIN wf_categories wc on wc.wf_categories_id=im.wf_id_2 JOIN wf_images i ON c.id=i.wf_images_id where wf_type_1='pic' and wf_type_2='cat' and wf_id_2=$key2";		
		$otherpics = $cat->getAll($q);
		// dumpVar(sizeof($otherpics), "$q otherpics2");
		// dumpVar($otherpics, "otherpics");
		$this->pics = $this->build('Pics', array('type' => 'pics', 'data' => $otherpics));
		dumpVar($this->pics->size(), "this->pics->size()");

		parent::showPage();
	}
	function getCaption()				{	return "Pictures for category: " . strip_tags($this->galTitle);	}
}
class CatTwoGetGallery extends CatTwoGallery
{
	function showPage()	
	{
		$this->keys = explode(',', $this->key);							// submitted as a second chouce on the OneCat page
		parent::showPage();
	}
}
class CatOnePostGallery extends CatOneGallery
{
	function showPage()	
	{
		$this->key = $this->props->checkedCats[0];						// aubmitted from checkboxes in Search
		parent::showPage();
	}
}
class CatTwoPostGallery extends CatTwoGallery
{
	function showPage()	
	{
		$this->keys = $this->props->checkedCats;						// aubmitted from checkboxes in Search
		parent::showPage();
	}
}

class DateMap extends OneMap
{
	var $loopfile = 'mapCenteredLoop.js';
	function trip()
	{
		$day = $this->build('DayInfo', $this->key);		// get this day

		dumpVar($day->lon(), "day->lon()");
		$this->template->set_var('CENTER_LAT', $day->lon());
		$this->template->set_var('CENTER_LON', $day->lat());
		$this->template->set_var('ZOOM', '9');

		$this->caption = "Trip map near " . Properties::prettyShort($day->date());

		return $day->tripId();
	}
	function getMetaDesc()	{	return sprintf("Local WHUFU Map for the day %s", Properties::prettyDate($this->key));	}
}
?>
