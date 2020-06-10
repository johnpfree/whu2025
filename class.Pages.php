<?php

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $file = "UNDEF";
	var $curpal = NULL;
	const pals =	array(
			"search" => array('boldcolor' => '#D76824', 'bordercolor' => '#999999', 'linkcolor' => '#6D1D00', 'linkhover' => '#87371A', 'bbackcolor' => '#f4e3d9', 'backcolor' => '#C1B0A6'),
			"spot" => 	array('boldcolor' => '#464646', 'bordercolor' => '#d0d0d0', 'linkcolor' => '#54736A', 'linkhover' => '#b0b0b0', 'bbackcolor' => '#f0f0f0', 'backcolor' => '#d5dFdF'),
		// FFE8BB
			"txt" => 		array('boldcolor' => '#4E3508', 'bordercolor' => '#684F22', 'linkcolor' => '#81683B', 'linkhover' => '#9B8255', 'bbackcolor' => '#fffff0', 'backcolor' => '#FFFFD4'), 
			"map" => 		array('boldcolor' => '#6C7200', 'bordercolor' => '#868C1A', 'linkcolor' => '#33A672', 'linkhover' => '#afd6b1', 'bbackcolor' => '#dbecdc'), 
			"pic" => 		array('boldcolor' => '#4B3E0C', 'bordercolor' => '#655826', 'linkcolor' => '#1B4F24', 'linkhover' => '#6A5835', 'bbackcolor' => '#FFFFE7'),
			"log" =>		array('boldcolor' => '#004151', 'bordercolor' => '#007383', 'linkcolor' => '#005A6A', 'linkhover' => '#33A6B6', 'bbackcolor' => '#E5FFFF'), 
			"deflt" => 	array('boldcolor' => '#3A5950', 'bordercolor' => '#e9f0ee', 'linkcolor' => '#b30000', 'linkhover' => '#593A43', 'bbackcolor' => '#d7e5e1'), 
			// "deflt" => 	array('boldcolor' => '#3A5950', 'bordercolor' => '#e9f0ee', 'linkcolor' => '#593A43', 'linkhover' => '#a7c5bc', 'bbackcolor' => '#d7e5e1'),
		);
	var $sansFont = "font-family: Roboto, Arial, sans-serif";
	// var $sansFont = "font-family: 'Montserrat', sans-serif";
	
	var $caption = '';		// if $caption is non-blank, use it in setCaption(). Otherwise call getCaption()
	var $meta_desc = 'Pictures, Stories, Custom Maps';		// if $meta_desc is non-blank, use it in setCaption(). Otherwise call getMetaDesc()

	function __construct($p)
	{
		$this->props = $p;
		parent::__construct(new WhuTemplate());
		$pagetype = $this->props->get('page') . $this->props->get('type');
dumpVar(get_class($this), "View class, <b>$pagetype</b> --> <b>{$this->file}</b>");
	}
	function showPage()	{}
		
	function isRunningOnServer() { return (HOST == 'cloudy'); }
	
	function setCaption()		// also handy place for bookkeeping shared for all pages
	{
		$this->template->set_var('CAPTION'  , ($this->caption != '') ? $this->caption   : $this->getCaption());		
		$this->template->set_var('META_DESC', $this->getMetaDesc());
		dumpVar($this->getMetaDesc(), "this->getMetaDesc()");

		// set active menu
		$page = $this->props->get('page');
		foreach (array('home', 'trips', 'spots', 'about', 'vids', 'search') as $k => $v) 
		{
			$this->template->set_var("ACTIVE_$v", ($page == $v) ? "active" : '');
		}
		
		// set "from" values for contact page
		// DUH, do NOT set them when this IS the contach page
		if ($page == 'contact') {
			$this->template->set_var('FROM_I', $this->props->get('fromp'));
			$this->template->set_var('FROM_T', $this->props->get('fromt'));
			$this->template->set_var('FROM_K', $this->props->get('fromk'));
			$this->template->set_var('FROM_I', $this->props->get('fromi'));
		} else {
			$this->template->set_var('FROM_P', $page);
			$this->template->set_var('FROM_T', $this->props->get('type'));
			$this->template->set_var('FROM_K', $this->props->get('key'));
			$this->template->set_var('FROM_I', $this->props->get('id'));
		}
		if ($this->isRunningOnServer())
			$this->template->setFile('GOOGLE_ANALYTICS', 'googleBlock.ihtml');
		else
			$this->template->set_var('GOOGLE_ANALYTICS', '');
	}
	function getCaption()	
	{
		return sprintf("%s | %s | %s", $this->props->get('page'), $this->props->get('type'), $this->props->get('key'));	
	}
	function getMetaDesc()	{	return $this->meta_desc;	}
	function setStyle($page)
	{
		$pagemap = array('day' => 'log', 'vis' => 'pic');
		if (isset($pagemap[$page]))					// map pages
			$page = $pagemap[$page];

		foreach (self::pals as $k => $v)
		{
			// dumpVar($k, "pt= $page, k");
			if (strpos($page, $k) !== false) {
				$this->curPal = new StyleProps($v, $k);
				break;
			}
		}	
		if (!isset($this->curPal))
			$this->curPal = new StyleProps(self::pals['deflt'], 'default');
		
		$this->template->set_var('SANS_FONT', 	$this->sansFont);				// sometimes the serifs don't look good

		$this->template->set_var('BBACKCOLOR', 	$this->curPal->pageBackColor());
		$this->template->set_var('BODYCOLOR', 	$this->curPal->pageLineColor());
		$this->template->set_var('BACKCOLOR', 	$this->curPal->contBackColor());
		$this->template->set_var('BORDERCOLOR', $this->curPal->borderColor());
		$this->template->set_var('BOLDCOLOR', 	$this->curPal->boldFontColor());
		$this->template->set_var('LINKCOLOR', 	$this->curPal->linkColor()    );
		$this->template->set_var('LINKHOVER', 	$this->curPal->linkHover()    );
		$this->template->set_var('PAL_NAME', 	$this->curPal->palette);
	}
	
	function tripLinkBar($page, $id)
	{
		$allfour = array(
			"map" => "map",
			"log" => "log",
			"pics" => "pictures",
			"txts" => "stories",
			);
		$this->template->setFile('LINK_BAR', 'linkbar.ihtml');
		$trip = $this->build('Trip', $id);
		$i = 1;
		
		foreach ($allfour as $k => $v) 
		{
			$paltag = $k;
			if ($k == $page)	continue;
			switch ($k) {
				case 'pics':	$paltag = 'pic'; $gotSome = $trip->hasWhuPics();	break; // || $trip->hasFlicks();	break;
				case 'txts':	
					 $url = (new WhutxtsidLink($trip))->url($v);
					 $this->template->set_var("LINK$i", ($url == '') ? '-' : $url);
					 return;
			 default:			$gotSome = TRUE;
			}
// dumpVar(boolStr($gotSome), "linkBar($page, $id) $k, $v");

			if ($gotSome) 
			{
				$triplink = new WhuLink($k, 'id', $id, $v);			
				$this->template->set_var("LINK$i", $triplink->url());
			}
			else
				$this->template->set_var("LINK$i", '-');

			$this->template->set_var("BACK$i", self::pals[$paltag]['bbackcolor']);			
			$i++;
		}		
	}
	function dayLinkBar($page, $date)
	{
		// dumpVar('xx', "dayLinkBar($page, $date)");
		$allfour = array(
			"map" => "map",
			"day" => "day",
			"pics" => "pictures",
			"txt" => "story",
			);
		$this->template->setFile('LINK_BAR', 'linkbar.ihtml');
		
		$day = $this->build('DbDay', $date);
		
		$trip = $this->build('DbTrip', $id = $day->tripId());
		$this->template->set_var("TRIP_ID", $id);
		$this->template->set_var("TRIP_NAME", $trip->name());
		
		$i = 1;
		foreach ($allfour as $k => $v) 
		{
			$paltag = $k;
			if ($k == $page)	continue;

			switch ($k) {
				case 'pics':	$gotSome = $day->hasPics();	$paltag = 'pic'; break;
				case 'txt':		$gotSome = $day->hasStory();	break;
				case 'day':		$gotSome = true; $paltag = 'log'; 	break;
				default:			$gotSome = true;
			}
			if ($gotSome) 
			{			
				// dumpVar($v, "WhuLink($k, 'date', $date, v");
				$daylink = new WhuLink($k, 'date', $date, $v);			
				$this->template->set_var("LINK$i", $daylink->url());
			}
			else
				$this->template->set_var("LINK$i", '-');

			$this->template->set_var("BACK$i", self::pals[$paltag]['bbackcolor']);			
			$i++;
		}		
	}
	function pagerBar($page, $type, $settings = array())
	{
		$props = array_merge(array('pager_tag' => 'PAGER_BAR', 'middle' => false, 'pkey' => 0, 'nkey' => 0, 'plab' => "previous", 'nlab' => "next"), $settings);
		// dumpVar($props, "pager props");
		
		$ok = $this->template->setFile($props['pager_tag'], 'pagerbar.ihtml');

		$this->template->set_var("PAGER_PAGE", $page);
		$this->template->set_var("PAGER_TYPE", $type);
		if ($props['pkey'] > 0)
		{
			$this->template->set_var("P_VIS", "");
			$this->template->set_var("P_KEY", $props['pkey']);
			$this->template->set_var("P_LAB", $props['plab']);
		}
		else 
			$this->template->set_var("P_VIS", "class='hidden'");
		
		if ($props['nkey'] > 0)
		{
			$this->template->set_var("N_VIS", "");
			$this->template->set_var("N_KEY", $props['nkey']);
			$this->template->set_var("N_LAB", $props['nlab']);
		}
		else 
			$this->template->set_var("N_VIS", "class='hidden'");
		
		// single picture nav needs an id parm, hack it into the key parm
		// if (isset($props['nid']))
		// {
		// 	$this->template->set_var("P_KEY", sprintf("%s&id=%s", $props['pkey'], $props['pid']));
		// 	$this->template->set_var("N_KEY", sprintf("%s&id=%s", $props['nkey'], $props['nid']));
		// }
			
		if ($props['middle'])
		{
			$this->template->set_var("M_VIS", "");
			$this->template->set_var("M_LAB", $props['mlab']);
		}
		else 
			$this->template->set_var("M_VIS", "class='hidden'");
	}
	function addDollarSign($s)	{ return "&#36;$s"; }
	
	// 	array('zoom' => 7, 'lat' => $center->lat, 'lon' => $center->lon, 'name' => 'Center of the area for this story');
	function setLittleMap($coords, $weathermark = 'false')
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
		$this->template->set_var('WEATHER_MARK', $weathermark);
		foreach ($coords as $k => $v) 
		{
			$this->template->set_var("PT_$k", addslashes($v));
		}
		return true;
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

class SpotsHome extends ViewWhu
{
	var $file = "spotshome.ihtml";
	var $searchterms = array('CAMP' => 'wf_spots_types', 'usfs' => 'wf_spots_status', 'usnp' => 'wf_spots_status');
	var $title = "Spots";
	function showPage()	
	{
		parent::showPage();
		
		$spots = $this->build('DbSpots', $this->searchterms);
		
		$maxrows = 60;
		if ($spots->size() > $maxrows)
		{
			shuffle($spots->data);
			$this->template->set_var("TITLE", "A Random Selection of " . $this->title);
		}
		else
			$this->template->set_var("TITLE", $this->title);
		$this->caption = "Browse " . $this->title;
		
		for ($i = 0, $rows = array(); $i < min($maxrows, $spots->size()); $i++)
		{
			$spot = $spots->one($i);
			$row = array(
				'spot_id' 		=> $spot->id(), 
				'spot_short' 	=> $spot->shortName(), 
				'spot_name' 	=> $spot->name(),
				'spot_part_of' => $spot->partof(),
				'spot_where' 	=> $spot->town(),
				'spot_type' 	=> $spot->types(),
 				);
			$rows[] = $row;
		}
		// do loop twice, for mobile version and for full version
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'one' =>'lg_row'));
		$loop->do_loop($rows);		
	}
}
class SpotsTypes extends SpotsHome
{
	function showPage()	
	{
		$spottypes = array(
					'LODGE'		=> 'Lodging',
					'HOTSPR'	=> 'Hot Springs',
					'NWR'			=> 'Wildlife Refuges',
					);
		$this->title = $spottypes[$this->key];
		$this->searchterms = array($this->key => 'wf_spots_types');
		parent::showPage();
	}
}
class SpotsCamps extends SpotsHome
{
	function showPage()	
	{
		$this->title = WhuDbSpot::$CAMPTYPES[$this->key];
		$this->searchterms = array('camp_type' => $this->key);
		parent::showPage();
	}
}
class SpotsKeywords extends SpotsHome
{
	function showPage()	
	{
		$this->title = sprintf("Spots with keyword: <i>%s</i>", $this->key);
		$this->searchterms = array('wf_spot_days_keywords' => $this->key);
		parent::showPage();
	}
}
class SpotsPlaces extends SpotsHome
{
	function showPage()	
	{
		$this->searchterms = array('wf_categories_id' => $this->key, 'kids' => 1);
		$cat = $this->build('Category', $this->key);	
		$this->title = sprintf("Spots in: <i>%s</i>", $cat->name());
		parent::showPage();
	}
}

class AllTrips extends ViewWhu
{
	var $file = "tripslist.ihtml";   
	function showPage()	
	{
		parent::showPage();
		dumpVar(WP_PATH, "WP_PATH");
		$this->template->set_var('WP_PATH', WP_PATH);
		
		$tripTypes = array(
			array('tag' => 'main' , 'SHOWTAG' => 'in', 'TYPE_NAME' => 'Big Trips'),
			array('tag' => 'eka'  , 'SHOWTAG' => '', 'TYPE_NAME' => 'Eureka Trips'),
			array('tag' => 'small', 'SHOWTAG' => '', 'TYPE_NAME' => 'Short Trips')
		);
		
		$loop = new TripLooper($this);	
		$loop->do_loop($tripTypes);
	}
	function getCaption()	{	return "Browse All Trips";	}
}
class TripLooper extends MultiLooper
{
	var $viewHandle;				// need this for the build() function below
	function __construct($t)
	{
		$this->viewHandle = $t;
		parent::__construct(2, $t->template, NULL, 	// third param is db, but don't need it here
				array('triptype_row', 'trip_row'), array('noFields' => true, 'noFields1' => true, 'parent' => 'the_content'));
	}
	function getInner($item)
	{		
		$this->template->set_var("GROUPTAG", ($tag = $item['tag']));
		$trips = $this->viewHandle->build('Trips', array('filter' => $tag));
		for ($i = 0, $rows = array(); $i < $trips->size(); $i++) 
		{
			$trip = $trips->one($i);
			$row = array("TRIP_DATE" => $trip->startDate(), "TRIP_ID" => $trip->id(), "TRIP_NAME" => $trip->name());
			$row['TXTS_LINK'] = (new WhutxtsidLink($trip))->url();
			$row['MAP_LINK' ] = (new WhumapidLink ($trip))->url();
			$row['PICS_LINK'] = (new WhupicsidLink($trip))->url();
			$row['VIDS_LINK'] = (new WhuvidsidLink($trip))->url();

			// dumpVar($row, "row"); exit;
			$rows[] = $row;
		}
		return $rows;
	}
}

class OneTripLog extends ViewWhu
{
	var $file = "triplog.ihtml";   
	function showPage()	
	{
		$tripid = $this->key;
 	 	$trip = $this->build('DbTrip', $tripid);	
		$days = $this->build('DbDays', $tripid);
		$this->template->set_var('TRIP_NAME', $this->caption = $trip->name());
		
		// whiffle the days for this trip 
		for ($i = $iPost = $prevPostId = 0, $nodeList = array(); $i < $days->size(); $i++) 
		{
			// $day = new WhuDayInfo($days->one($i));
			$day = $this->build('DayInfo', $days->one($i));

			// easy stuff - date mileage name ...
			$row = array('day_name' => $day->dayName(), 'miles' => $day->miles(), 'cum_miles' => number_format($day->cumulative()), 'day_number' => $i+1);
			$row['nice_date'] = Properties::prettyShort($row['day_date'] = $day->date(), "M"); 
			$row['short_date'] = Properties::prettyShortest($row['day_date']); 
			$row['title_date'] = Properties::prettyDate($row['day_date']); 
			$row['trip_year'] = substr($row['day_date'], 0, 4); 

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
			$row['stop_desc'] = $day->baseExcerpt($day->nightDesc(), 30);

			if (($npic = $day->pics()->size()) > 0) 
			{
				$picu = new WhuLink('pics', 'date', $day->date(), "[$npic]", "today's images");
				$row['PIC_LINK'] = $picu->url();			// pic link
			}
			else
				$row['PIC_LINK'] = '';
			
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

		$this->tripLinkBar('log', $tripid);	
		$this->meta_desc = sprintf("Mileage log for the WHUFU trip: '%s &ndash; %s'", $this->caption, $trip->desc());		
		parent::showPage();
	}
}

class TripPictures extends ViewWhu
{
	var $file = "trippics.ihtml";
	function showPage()	
	{
		parent::showPage();
		
		$trip = $this->build('Trip', $this->key);
		$this->template->set_var('GAL_TITLE', $this->caption = $trip->name());
		
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
			$row['binpic'] = $pic->thumbImage();
			if (strlen($row['binpic']) > 100) {			// hack to slow the slow image if the thumbnail fails on server
				$row['use_binpic'] = '';
				$row['use_image']  = 'hideme';
			} else {
				$row['use_binpic'] = 'hideme';
				$row['use_image']  = '';
			}		

			$rows[] = $row;
			$count += $dc;
		}
		// dumpVar($rows, "rows $count");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		$this->template->set_var('NUM_DAYS', $days->size());
		$this->template->set_var('NUM_PICS', $count);
		
 		$this->tripLinkBar('pics', $this->props->get('key'));
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
		$this->pagerBar('vid', 'id', $pageprops);	
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
	function showPage()	
	{
		$this->template->set_var('GAL_TYPE', $this->galtype);
		$this->template->set_var('GAL_TITLE', $this->galleryTitle($this->key));
		$this->template->set_var('GAL_COUNT', $this->props->get('extra'));
		$this->template->set_var('TODAY', $this->galleryTitle($this->key));
		$this->template->set_var('REL_PICPATH', iPhotoURL);
		$this->template->set_var('IPIC', 0);
		$this->template->set_var('RGMSG', $this->message);	
		
		$this->doNav();			// do nav (or not)

		$pics = $this->getPictures($this->key);
				
		for ($i = 0, $rows = array(); $i < $pics->size(); $i++) 
		{
			$pic = $pics->one($i);
			// dumpVar(sprintf("id %s, %s: %s", $pic->id(), $pic->filename(), $pic->caption()), "$i Gallery");
			
			$row = array('PIC_ID' => $pic->id(), 'WF_IMAGES_PATH' => $pic->folder(), 'PIC_NAME' => $pic->filename());
			$row['PIC_DESC'] = htmlspecialchars($pic->caption());

			$row['img_full'] = sprintf("%spix/iPhoto/%s/%s", iPhotoURL, $row['WF_IMAGES_PATH'], $row['PIC_NAME']);
			$thumb = $pic->thumbImage();
			$row['img_thumb'] = "data:image/jpg;base64,$thumb";
			if (strlen($row['img_thumb']) < 100) {																	// hack to use the full image if the thumbnail fails on server
				dumpVar($row['PIC_NAME'], "binpic fail");
				$row['img_thumb'] = $row['img_full'];
			}
			else if (($ratio = ($pic->thumbSize[0] / $pic->thumbSize[1])) > 2.) {		// also use the full image for panoramas 'cuz thumb looks terrible
				dumpVar($ratio, "ratio");
				// dumpVar($pic->thumbSize, "$i pic->thumbSize");
				$row['img_thumb'] = $row['img_full'];
			}
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true, 'none_msg' => 'no pictures'));
		$loop->do_loop($rows);
		
		parent::showPage();
	}
	function galleryTitle($key)				{	return "Undefined!";	}
}
class DateGallery extends Gallery
{
	var $galtype = "date";   
	function showPage()	
	{
		$this->template->set_var("DATE_GAL_VIS", '');
		$this->template->set_var("CAT_GAL_VIS" , 'hideme');

		$this->dayLinkBar('pics', $this->key);
		$this->meta_desc = sprintf("WHUFU Picture Gallery for %s", $this->galleryTitle($this->key));
		parent::showPage();
	}
	function getPictures($key)	{ return $this->build('Pics', (array('date' => $key))); }
	function getCaption()				{	return "Pictures for " . $this->key;	}
	function galleryTitle($key)	{	return Properties::prettyDate($key); }
	function doNav()
	{
		$date = $this->build('DbDay', $this->key);
		$pageprops = array('middle' => true);
		$pageprops['plab'] = Properties::prettyDate($pageprops['pkey'] = $date->previousDayGal(), "M");
		$pageprops['nlab'] = Properties::prettyDate($pageprops['nkey'] = $date->nextDayGal(), "M");
		$pageprops['mlab'] = $this->galleryTitle($this->key);
		$this->pagerBar('pics', 'date', $pageprops);		
	}
}
class CatGallery extends Gallery
{
	var $maxGal = 40; 
	var $galtype = "cat";
	function showPage()	
	{
		$cat = $this->build('Category', $this->key);

		$this->template->set_var("DATE_GAL_VIS", 'hideme');
		$this->template->set_var("CAT_GAL_VIS" , '');
		
		$this->template->set_var("TRIP_ID", $this->key);
		$this->template->set_var("TRIP_NAME", $this->name = $cat->name());		// save name for caption call below
		$this->template->set_var("TODAY", '');
		$this->template->set_var('LINK_BAR', '');
		
		// do stuff below so I can create the message before the call
		$this->pics = $this->build('Pics', (array('cat' => $this->key))); 
		if (($size = $this->pics->size()) > $this->maxGal)
		{
// <span class="genericon genericon-shuffle"></span>

			// $this->message = sprintf("A selection of %s out of %s &bull; <a href=\"?page=pics&type=cat&key=%s\">reselect</a>", $this->maxGal, $size, $this->key);			
			$this->message = sprintf("A selection of %s out of %s, refresh to reselect.", $this->maxGal, $size, $this->key);			
			$this->pics->random($this->maxGal);
		}
		
		parent::showPage();
	}
	function getCaption()				{	return "Pictures for category: " . $this->name;	}
	function galleryTitle($key)	{	return ''; }
	function getPictures($key)	{ return $this->pics; }	
	function doNav() { $this->template->set_var('PAGER_BAR', ''); }
}

class OneMap extends ViewWhu
{
	var $file = "onemap.ihtml";
	var $loopfile = 'mapBoundsLoop.js';
	var $marker_color = '#535900';	// '#8c54ba';
	function showPage()	
	{
		$eventLog = array();
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var('PAGE_VAL', 'day');
		$this->template->set_var('TYPE_VAL', 'date');
		$this->template->set_var('MARKER_COLOR', $this->marker_color);
		
		// cheeseball trick to use http locally and https on server :<
		$this->template->set_var('WHU_URL', sprintf("http%s://%s%s", (HOST == 'cloudy') ? 's' : '', $_SERVER['HTTP_HOST'], parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)));

		$tripid = $this->trip();		// local function		
 	 	$trip = $this->build('Trip', $tripid);		
		$this->template->set_var('MAP_NAME', $this->name = $trip->name());
		$this->caption = "Map for $this->name";
		
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
		
		$this->template->setFile('LOOP_INSERT', $this->loopfile);
		
 	 	$days = $this->build('DbDays', $tripid);
		for ($i = 0, $rows = array(), $prevname = '@'; $i < $days->size(); $i++)
		{
			$day = $this->build('DayInfo', $days->one($i));

			$row = array('marker_val' => ($i+1) % 100, 'point_lon' => $day->lon(), 'point_lat' => $day->lat(), //'point_loc' => $day->town(), 
										'point_name' => addslashes($day->nightName()), 'key_val' => $day->date(), 
										'link_text' => Properties::prettyDate($day->date()));
						
						// dumpVar($row, "row $i");
			if ($row['point_lat'] * $row['point_lon'] == 0) {						// skip if no position
				$eventLog[] = "NO POSITION! $i row";
				$eventLog[] = $row;
				continue;
			}
			if ($row['point_name'] == $prevname) {											// skip if I'm at the same place as yesterday
				// $eventLog[] = "skipping same $i: {$row['point_name']}";
				continue;                       
			}
			$prevname = $row['point_name'];

// dumpVar($row, "$i - row");
			$rows[] = $row;
		}
		// dumpVar($rows, "rows");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'none_msg' => "", 'noFields' => true));
		$loop->do_loop($rows);
		
		$this->tripLinkBar('map', $tripid);	
		
		if (sizeof($eventLog))
			dumpVar($eventLog, "Event Log");
		parent::showPage();
	}
	function getMetaDesc()	{	return "Map for the WHUFU trip called '$this->name'";	}
	function trip()		{ 
		return $this->key; 
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
class SpotMap extends OneMap
{
	function showPage()	
	{
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var('LINK_BAR', '');
		$this->template->set_var("JSON_INSERT", '');
		$this->template->setFile('LOOP_INSERT', $this->loopfile);
		$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		$this->template->set_var('PAGE_VAL', 'spot');
		$this->template->set_var('TYPE_VAL', 'id');
		$this->template->set_var('WHU_URL', $t = sprintf("http://%s%s", $_SERVER['HTTP_HOST'], parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)));
		// dumpVar($t, "WHU_URL");
		
		$this->setTitle($this->rad = $this->props->get('search_radius'));
		$items = $this->getSpots($this->rad);
		
		$markers = array('CAMP' => 'campsite', 'LODGE' => 'lodging', 'HOTSPR' => 'swimming', 'PARK' => 'parking', 'NWR' => 'wetland');	// , 'veterinary', 
		$hiPriority = array('CAMP', 'LODGE', 'PARK');		// this type wins
		
		$rows = $this->initRows();
		$spots = $this->build('DbSpots', $items);
		for ($i = 0; $i < $spots->size(); $i++)
		{
			$spot = $spots->one($i);
		
			$row = array('point_lon' => $spot->lon(), 'point_lat' => $spot->lat(), 
										'point_name' => addslashes($spot->town()), 'key_val' => $spot->id(), 'link_text' => addslashes($spot->name()));

			$row['marker_color'] = $this->markerColor($i);
										
			$types = $spot->prettyTypes();
			foreach ($types as $k => $v)	{
				// dumpVar($v, "$i $k v");
				if ($k == 'CAMP' && $v == 'parking lot')
					$k = 'PARK';
				$row['marker_val'] = $markers[$k];
				if (in_array($k, $hiPriority))			// this means I can control who wins by listing, say LODGE before CAMP
					break;
			}

			if ($row['point_lat'] * $row['point_lon'] == 0) {						// skip if no position
				dumpVar($row, "NO POSITION! $i row");
				continue;
			}
			$rows[] = $row;
			// if ($i == 1)	dumpVar($rows, "rows");
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		ViewWhu::showPage();
	}
	function setTitle($rad) 
	{ 
 	 	$this->spot = $this->build('DbSpot', $this->key);		
		$this->template->set_var('MAP_NAME', $t = sprintf("Spots in a %s mile radius of %s", $rad, $this->name = $this->spot->name()));
		$this->caption = $t;
	}
	function getSpots($rad) 
	{ 
		return $this->spot->getInRadius($rad);
	}
	function initRows() { return array(); }
	function markerColor($i) { return ($i <= 0) ? '#000' : $this->marker_color; }
	function getMetaDesc()	{	return "Map of all WHUFU Spots in a {$this->rad} of {$this->name}";	}
}
class NearMap extends SpotMap
{
	function setTitle($rad) 
	{ 
		$this->template->set_var('MAP_NAME', $t = sprintf("Spots in a %s mile radius of \"%s\"", $rad, $this->props->get('search_term')));
	}
	function getSpots($rad) 
	{ 
		$this->loc = getGeocode($this->props->get('search_term'));
		dumpVar($this->loc, "lox");
		
		$fakeSpot = $this->build('DbSpot', array('wf_spots_id' => 9999, 'wf_spots_lon' => $this->loc['lon'], 'wf_spots_lat' => $this->loc['lat']));
		return $fakeSpot->getInRadius($rad);
	}	
	function initRows() 
	{
		$centerRow = array('point_lon' => $this->loc['lon'], 'point_lat' => $this->loc['lat'], 'key_val' => 0, 'link_text' => '', 'marker_val' => 'cross', 'marker_color' => '#000');
		$centerRow['point_name'] = sprintf("Search from \"%s\"", addslashes($this->loc['name']));
		return array($centerRow);
	}
	function markerColor($i) { return $this->marker_color; }
}

class OnePhoto extends ViewWhu
{
	var $file = "onepic.ihtml";   
	function showPage()	
	{
		parent::showPage();
 	 	$vis = $this->build('Visual', $visid = $this->key);		
		
		$this->template->set_var('COLLECTION_NAME', Properties::prettyDate($date = $vis->date()));
		$this->template->set_var('DATE', $date);
		$this->template->set_var('PRETTIEST_DATE', WhuProps::verboseDate($date));
		$this->template->set_var('PIC_TIME', Properties::prettyTime($vis->time()));
		$this->template->set_var('PIC_CAMERA', $vis->cameraDesc());
		$this->template->set_var('PIC_PLACE', $vis->place());
		$this->template->set_var('PICFILE_NAME', $vis->filename());
		$this->template->set_var('WF_IMAGES_PATH', $vis->folder());
		$this->template->set_var('WF_IMAGES_FILENAME', $vis->filename());
		$this->template->set_var('VIS_NAME', $name = $vis->caption());
		$this->template->set_var('REL_PICPATH', iPhotoURL);
		$this->template->set_var('VID_SPOT_VIS', 'hideme');
		
 	 	$day = $this->build('DayInfo', $date);
		$this->template->set_var('WPID', $wpid = $day->postId());
		if ($wpid > 0)
		{
			$this->template->set_var('STORY', $this->build('Post', array('quickid' => $wpid))->title());
			// $this->template->set_var('STORY', $this->build('Post', $wpid)->title());
			$this->template->set_var('STORY_LINK', $this->makeWpPostLink($wpid));
			$this->template->set_var("STORY_VIS", '');
		}
		else
			$this->template->set_var('STORY_VIS', 'hideme');

		// Details info	- keywords
		$keys = $this->build('Categorys', array('picid' => $visid));
		for ($i = 0, $rows = array(); $i < $keys->size(); $i++)
		{
			$key = $keys->one($i);	
			$row = array('WF_CATEGORIES_ID' => $key->id(), 'WF_CATEGORIES_TEXT' => $key->name());
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
	
 	 	$vis = $this->build('Pic', $vis);			// NOTE I am recasting my generic vis to a picture
		$gps = $vis->latlon();
		if ($vis->cameraDoesGeo())
			$gps['geo'] = true;
		if ($this->setLittleMap(array_merge($gps, array('name' => Properties::prettyDate($vis->date()), 'desc' => $name))))
		{
			$this->template->set_var('GPS_VIS', '');
			$this->template->set_var('GPS_LAT', $gps['lat']);
			$this->template->set_var('GPS_LON', $gps['lon']);
		}
		else
			$this->template->set_var('GPS_VIS', 'hideme');

		$this->caption = sprintf("%s on %s", $vis->kind(), Properties::prettyShort($date));

		$pageprops = array();
		$pageprops['pkey'] = $vis->prev()->id();
		$pageprops['nkey'] = $vis->next()->id();
		$this->pagerBar('vis', 'id', $pageprops);		
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
		
		$this->template->set_var('WPID', $wpid = $day->postId());
		if ($wpid > 0)
			$this->template->set_var('STORY', $this->build('Post', array('quickid' => $wpid))->title());
			// $this->template->set_var('STORY', $this->build('Post', $wpid)->title());
		$this->template->set_var("VIS_CLASS_TXT", $day->hasStory() ? '' : "class='hidden'");
		$this->template->set_var('STORY_LINK', $this->makeWpPostLink($wpid));
		
		$this->template->set_var('DAY_NAME', $day->dayName());
		$this->template->set_var('DAY_DESC', $day->dayDesc());
		$this->template->set_var('PM_STOP', $day->nightNameUrl());
		
		$this->template->set_var('NIGHT_DESC', $desc = $day->nightDesc());
		$this->template->set_var('NIGHT_VIS', ($desc == '') ? 'hideme' : '');
		
		// do next|prev nav - as long as I have yesterday, show where I woke up today
		$pageprops = array();
		$navday = $this->build('DbDay', $d = $day->yesterday());
		if ($navday->hasData)			// for the first day of the trip, there is no yesterday
		{
			$this->template->set_var('AM_STOP', $this->build('DayInfo', $d)->nightNameUrl());
			$pageprops['plab'] = 'yesterday';
			$pageprops['pkey'] = $d;
		}
		else {
			$this->template->set_var('AM_STOP', 'home');
		}		

		$pageprops['nlab'] = 'tomorrow';       
		$navday = $this->build('DbDay', $d = $day->tomorrow());
		if ($navday->hasData)			// day after the last day has no data
			$pageprops['nkey'] = $d;
		$this->pagerBar('day', 'date', $pageprops);		

		$this->dayLinkBar('day', $dayid);		
		$this->meta_desc = sprintf("WHUFU log for %s &ndash; slept at %s", $this->caption, $day->nightName());		
		parent::showPage();
	}
}

class OneSpot extends ViewWhu
{
	var $file = "onespot.ihtml";   
	function showPage()	
	{
		$spotid = $this->props->get('key');
 	 	$spot = $this->build('DbSpot', $spotid);		

		$this->template->set_var('SPOT_NAME', 	$this->caption = $spot->name());
		$this->template->set_var('SPOT_ID', 		$spot->id());
		$this->template->set_var('SPOT_TOWN', 	$spot->town());
		$this->template->set_var('SPOT_PARTOF', $spot->partof());
		$this->template->set_var('SPOT_PLACE',  $spot->place());
		$this->template->set_var('SPOT_NUM',  	$visits = $spot->visits());
		
		$this->template->set_var('SPLAT',  	$spot->lat());
		$this->template->set_var('SPLON',  	$spot->lon());
		$this->template->set_var('SPBATH',  	$spot->bath());
		$this->template->set_var('SPWATER',  	$spot->water());
		$this->template->set_var('SPDESC',  	$desc = $spot->htmldesc());

		$this->template->set_var('DFLT_LINKCOLOR', self::pals['deflt']['linkcolor' ]);

		$types = $spot->prettyTypes();
		// dumpVar($types, "types"); exit;
		$str = '';
		foreach ($types as $k => $v) 
		{
			$str .= $v . ', ';
		}		
		$this->template->set_var('SPOT_TYPES', $types = substr($str, 0, -2));
		
		//----------------------------- weather tab ---------------
		$info = getWeatherInfo(($this->props->get("weather") != 1), $spot->lat(), $spot->lon());
		// dumpVar($info, "info");
		$this->template->set_var($info);

		if ($visits == 'never')
		{
			$this->template->set_var('DAYS_INFO', 'hideme');								// NO Days!
		}
		else
		{
			$this->template->set_var('DAYS_INFO', '');											// yes, there are days

			$keys = $spot->keywords();																			// ---------- keywords
			for ($i = 0, $rows = array(), $keylist = ''; $i < sizeof($keys); $i++) 
			{
				// dumpVar($keys[$i], "keys[$i]");
				$rows[] = array('spot_key' => $keys[$i]);
				$keylist .= $keys[$i] . ', ';
			}
			// dumpVar($rows, "rows");
			$loop = new Looper($this->template, array('parent' => 'the_content', 'one' => 'keyrow', 'none_msg' => "no keywords", 'noFields' => true));
			$loop->do_loop($rows);

			// ------------------------------------------------------------ days loop NOTE that I collect pictures in this loop also
			$days = $this->build('DbSpotDays', $spot->id());								
			for ($i = $count = 0, $rows = array(); $i < $days->size(); $i++)
			{
				$day = $days->one($i);
				// $day->dump($i);
				$row = array('stay_date' => $date = $day->date());
				$row['nice_date'] = Properties::prettyDate($date);
				$row['spdaydesc'] = $day->htmldesc();
				if ($row['spdaydesc'] == $desc || $row['spdaydesc'] == '') 			// don't repeat the main desc
					$row['spdaydesc'] = "<em>(see main description above)</em>";

				if (($cost = $day->cost()) > 0)
				{
					$costs = $this->addDollarSign($cost);
					if ($day->senior() > 0 && $day->senior() != $cost)
						$costs .= ' | '.$this->addDollarSign($day->senior());
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

				// collect evening and morning pictures for each day
				if ($i == 0) {
					$pics = $this->build('Pics', array('night' => $date));
					dumpVar($pics->size(), "000 pics->size()");
				}
				else {
					$pics->add($this->build('Pics', array('night' => $date)));
					dumpVar($pics->size(), "$i. pics->size()");
				}
			}
			$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
			$loop->do_loop($rows);
		
			//----------------------------- pictures tab ---------------
			$this->template->set_var('REL_PICPATH', iPhotoURL);
			$pics->random(9);
			dumpVar($pics->size(), "pics->size()");
			
			
			for ($i = 0, $rows = array(); $i < $pics->size(); $i++)
			{
				$pic = $pics->one($i);
				$use_image = $use_binpic = $use_vidtmb = $use_flick = 'hideme';		// hide everbody, then un-hide one
				$row = array('gal_date' => $day->date(), 'wf_images_path' => $pic->folder(), 'pic_name' => $pic->filename(), 'pic_id' => $pic->id(), 'pano_symb' => '');

				if ($pic->isVideo())
				{
					$use_vidtmb = '';
					$vid = $this->build('Video', $pic);
					// dumpVar($vid->token(), "vid->token()");
					$row = array_merge(array('vis_page' => 'vid', 'pic_id' => $vid->id(), 'vid_token' => $vid->token(), 'bin_pic' => ''), $row);
				}
				else
				{
					$row['binpic'] = $pic->thumbImage();
					if (strlen($row['binpic']) > 100) {			// hack to show the slow image if the thumbnail fails on server
						$use_binpic = '';
					} else {
						$use_image = '';
					}
				}

				$row['use_image'] = $use_image;
				$row['use_binpic'] = $use_binpic;
				$row['use_vidtmb'] = $use_vidtmb;
				$row['use_flick'] = $use_flick;
				$row['pano_symb'] = $pic->picPanoSym();
				// dumpVar($row, "$i row");
				$rows[] = $row;
			}
			$loop = new Looper($this->template, array('parent' => 'the_content', 'one' => 'picrow', 'none_msg' => "no pics!", 'noFields' => true));
			$loop->do_loop($rows);
		}

		$this->setLittleMap(array('lat' => $spot->lat(), 'lon' => $spot->lon(), 'name' => $spot->name(), 'desc' => $spot->town()));
		$this->meta_desc = sprintf("Description of the WHUFU Spot %s: %s &ndash; %s &ndash; %s &ndash; %s", $this->caption, $spot->town(), $spot->partof(), $types, substr($keylist, 0, -2));		
		parent::showPage();
	}
}

class TripStory extends ViewWhu
{
	var $file = "onestory.ihtml";   
	function showPage()	
	{
		$postid = $this->key;
 	 	$post = $this->build('Post', array('wpid' => $postid));	

		$this->template->set_var('POST_TITLE', $this->caption = $post->title());
		$this->template->set_var('POST_CONTENT', $post->content());
		
		$pageprops = array();
 	 	$navpost = $this->build('Post', array('wpid' => $navid = $post->previous()));			
		$pageprops['plab'] = $navpost->title();
		$pageprops['pkey'] = $navid;
 	 	$navpost = $this->build('Post', array('wpid' => $navid = $post->next()));			
		$pageprops['nlab'] = $navpost->title();
		$pageprops['nkey'] = $navid;
		$this->pagerBar('txt', 'wpid', $pageprops);		
		// $dates = $post->dates();
		// $days = $this->build('DbDays', $dates);
		// dumpVar($post->firstDate(), "post->firstDate()");
		if ($this->props->get('type') != 'date')
			$this->dayLinkBar('txt', $post->firstDate());		// if type==date we have already done linkbar
		parent::showPage();
	}
}
class TripStoryByDate extends TripStory
{
	function showPage()	
	{
		$this->dayLinkBar('txt', $this->key);				// the key is the magic value here
		$day = $this->build('DbDay', $this->key);		// key here is the date
		$this->key = $day->postId();
		parent::showPage();
	}
}

class HomeHome extends ViewWhu
{
	var $file = "homehome.ihtml";
	function showPage()	
	{		
		$site = $this->build('Trips');
		$this->template->set_var('N_MAP', $site->numMaps());
		$this->template->set_var('N_TXT', $site->numPosts());
		$this->template->set_var('N_PIC', $site->numPics());
		$this->template->set_var('N_VID', $site->numVids());
		$this->template->set_var('N_SPO', $site->numSpots());

		$this->template->set_var('WP_PATH', WP_PATH);
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
		$this->template->set_var('WP_PATH', WP_PATH);
		parent::showPage();
	}
}
class Search extends ViewWhu
{
	var $file = "search.ihtml";   
	var $caption = "Find Spots. Browse Pictures"; 
	var $meta_desc = "Searching for WHUFU Campgrounds, Hot Springs, Image Tags";		
	 
	function showPage()	
	{
		$this->template->set_var('SPOTS_BACK', self::pals['spot']['linkcolor' ]);
		$this->template->set_var('SPOTS_FORE', self::pals['spot']['backcolor' ]);
		$this->template->set_var('PICS_BACK' , self::pals['pic'] ['linkhover' ]);
		$this->template->set_var('PICS_FORE' , self::pals['pic'] ['bbackcolor']);
		parent::showPage();
	}
}
class SearchResults extends ViewWhu
{
	var $file = "searchresults.ihtml";   
	function showPage()	
	{
		$this->template->set_var('SEARCHTERM', $this->key);
		$qterm = sprintf("%%%s%%", $this->key);
		dumpVar($qterm, "qterm");
		
		$spots = $this->build('DbSpots', $qterm);
		for ($i = 0, $str = '&bull; ', $rows = array(); $i < $spots->size(); $i++)
		{
			$spot = $spots->one($i);
			$str .= sprintf("<a href='?page=spot&type=id&key=%s'>%s</a> &bull; ", $spot->id(), $spot->name());
		}	
		$this->template->set_var('SPOTLIST', $str);
		
		$days = $this->build('Dbdays', array('searchterm' => $qterm));
		for ($i = 0, $str = '&bull; ', $rows = array(); $i < $days->size(); $i++)
		{
			$day = $days->one($i);
			$str .= sprintf("<a href='?page=day&type=date&key=%s'>%s</a> &bull; ", $day->date(), Properties::prettyDate($day->date()));
		}	
		$this->template->set_var('DAYLIST', $str);
		
		$pics = $this->build('Pics', array('searchterm' => $qterm));
		for ($i = 0, $days = array(); $i < $pics->size(); $i++)
		{
			$pic = $pics->one($i);
			if (isset($days[$date = $pic->date()]))
				$days[$date]++;
			else
				$days[$date] = 1;
		}	
		$str = '&bull;';
		foreach ($days as $k => $v) 
		{
			$str .= sprintf("<a href='?page=pics&type=date&key=%s'>%s(%s)</a> &bull; ", $k, Properties::prettyDate($k), $v);
		}
		$this->template->set_var('PICLIST', $str);

		$txts = $this->build('Posts', array('searchterm' => $this->key));
		for ($i = 0, $str = '&bull; ', $rows = array(); $i < $txts->size(); $i++)
		{
			$txt = $txts->one($i);
			$str .= sprintf("<a href='%s'>%s</a> &bull; ", $this->makeWpPostLink($txt->wpid()), $txt->title());
			// $str .= sprintf("<a href='?page=txt&type=wpid&key=%s'>%s</a> &bull; ", $txt->wpid(), $txt->title());
		}	
		$this->template->set_var('TXTLIST', $str);

		// $q = sprintf("select * from %sposts where post_status='publish' AND post_title LIKE %s OR post_content LIKE %s and post_type='post'", $this->tablepref, $term, $term);
		parent::showPage();
	}
}
class ContactForm extends ViewWhu
{
	var $file = "contact.ihtml";   
	function showPage()	
	{
		$this->template->set_var("HIDE_THANKS", 'hideme');
		$this->template->set_var("HIDE_FORM", '');
		
		$x1 = rand(2, 9);
		$x2 = rand(2, 9);
		dumpVar($x1 * $x2, "$x1 * $x2");
		$this->template->set_var('MATH_Q', "$x1 * $x2");
		$this->template->set_var('MATH_A', $x1 * $x2);

		$this->template->set_var('FROM_P', $this->props->get('fromp'));
		$this->template->set_var('FROM_T', $this->props->get('fromt'));
		$this->template->set_var('FROM_K', $this->props->get('fromk'));
		$this->template->set_var('FROM_I', $this->props->get('fromi'));

		parent::showPage();
	}
}
class ContactThanks extends ContactForm
{
	function showPage()	
	{
		$this->template->set_var("HIDE_THANKS", '');
		$this->template->set_var("HIDE_FORM", 'hideme');
		
		$this->template->set_var('FROM_P', $this->props->get('fromp'));
		$this->template->set_var('FROM_T', $this->props->get('fromt'));
		$this->template->set_var('FROM_K', $this->props->get('fromk'));
		$this->template->set_var('FROM_I', $this->props->get('fromi'));

		ViewWhu::showPage();
	}
}
?>