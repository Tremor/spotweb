<?php
error_reporting(E_ALL & ~8192 & ~E_USER_WARNING);	# 8192 == E_DEPRECATED maar PHP < 5.3 heeft die niet

require_once "lib/SpotClassAutoload.php";
SpotTiming::enable();
SpotTiming::start('total');
SpotTiming::start('settings');
require_once "settings.php";
SpotTiming::stop('settings');

#- main() -#
try {
	# database object
	$db = new SpotDb($settings['db']);
	$db->connect();

	# Creer het settings object
	$settings = SpotSettings::singleton($db, $settings);

	# enable of disable de timer
	if (!$settings->get('enable_timing')) {
		SpotTiming::disable();
	} # if
	
	# Controleer eerst of het schema nog wel geldig is
	if (!$db->schemaValid()) {
		die("Database schema is gewijzigd, draai upgrade-db.php aub" . PHP_EOL);
	} # if

	# Controleer dat er wel een password salt ingevuld is
	if ($settings->get('pass_salt') == 'unieke string') {
		die("Verander de setting 'pass_salt' in je ownsettings.php naar iets unieks!" . PHP_EOL);
	} # if

	# helper functions for passed variables
	$req = new SpotReq();
	$req->initialize($settings);
	$page = $req->getDef('page', 'index');

	# Haal het userobject op dat 'ingelogged' is
	SpotTiming::start('auth');
	$spotUserSystem = new SpotUserSystem($db, $settings);
	if ($req->doesExist('username') && $req->doesExist('apikey')) {
		$currentSession = $spotUserSystem->verifyApi($req->getDef('username', ''), $req->getDef('apikey', ''));
	} else {
		$currentSession = $spotUserSystem->useOrStartSession();
	} # if
	SpotTiming::stop('auth');

	SpotTiming::start('renderpage');
	switch($page) {
		case 'render' : {
				$page = new SpotPage_render($db, $settings, $currentSession, $req->getDef('tplname', ''),
							Array('search' => $req->getDef('search', $settings->get('index_filter')),
								  'messageid' => $req->getDef('messageid', ''),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', '')));

				$page->render();
				break;
		} # render
		
		case 'getspot' : {
				if (strpos($_SERVER['HTTP_USER_AGENT'], "SABnzbd+") === 0) {
					$page = new SpotPage_getnzb($db, $settings, $currentSession, 
						Array('messageid' => $req->getDef('messageid', ''),
							'action' => $req->getDef('action', 'display'),
							'username' => $req->getDef('username', ''),
							'apikey' => $req->getDef('apikey', '')));
				} else {
					$page = new SpotPage_getspot($db, $settings, $currentSession, $req->getDef('messageid', ''));
				} # else
				$page->render();
				break;
		} # getspot

		case 'getnzb' : {
				$page = new SpotPage_getnzb($db, $settings, $currentSession, 
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display'),
									  'username' => $req->getDef('username', ''),
									  'apikey' => $req->getDef('apikey', '')));
				$page->render();
				break;
		}
		
		case 'getspotmobile' : {
				$page = new SpotPage_getspotmobile($db, $settings, $currentSession, $req->getDef('messageid', ''));
				$page->render();
				break;
		} # getspotmobile

		case 'getnzbmobile' : {
				$page = new SpotPage_getnzbmobile($db, $settings, $currentSession,
								Array('messageid' => $req->getDef('messageid', ''),
									  'action' => $req->getDef('action', 'display')));
				$page->render();
				break;
		} # getnzbmobile		

		case 'erasedls' : {
				$page = new SpotPage_erasedls($db, $settings, $currentSession);
				$page->render();
				break;
		} # erasedls
		
		case 'catsjson' : {
				$page = new SpotPage_catsjson($db, $settings, $currentSession);
				$page->render();
				break;
		} # getspot
		
		case 'markallasread' : {
				$page = new SpotPage_markallasread($db, $settings, $currentSession);
				$page->render();
				break;
		} # markallasread

		case 'getimage' : {
			$page = new SpotPage_getimage($db, $settings, $currentSession,
								Array('messageid' => $req->getDef('messageid', ''),
									  'image' => $req->getDef('image', Array())));
			$page->render();
			break;
		}

		case 'selecttemplate' : {
				$page = new SpotPage_selecttemplate($db, $settings, $currentSession, $req);
				$page->render();
				break;
		} # selecttemplate

		case 'rss' : {
			$page = new SpotPage_rss($db, $settings, $currentSession,
					Array('search' => $req->getDef('search', $settings->get('index_filter')),
						  'page' => $req->getDef('page', 0),
						  'sortby' => $req->getDef('sortby', ''),
						  'sortdir' => $req->getDef('sortdir', ''),
						  'username' => $req->getDef('username', ''),
						  'apikey' => $req->getDef('apikey', ''))
			);
			$page->render();
			break;
		} # rss		
		
		case 'statics' : {
				$page = new SpotPage_statics($db, $settings, $currentSession,
							Array('type' => $req->getDef('type', '')));
				$page->render();
				break;
		} # statics

		case 'createuser' : {
				$page = new SpotPage_createuser($db, $settings, $currentSession,
							Array('createuserform' => $req->getForm('createuserform', array('submit'))));
				$page->render();
				break;
		} # createuser

		case 'edituser' : {
				$page = new SpotPage_edituser($db, $settings, $currentSession,
							Array('edituserform' => $req->getForm('edituserform', array('submitedit', 'submitdelete')),
								  'userid' => $req->getDef('userid', '')));
				$page->render();
				break;
		} # edituser
		
		case 'listusers' : {
				$page = new SpotPage_listusers($db, $settings, $currentSession, array());
				$page->render();
				break;
		} # listusers

		case 'login' : {
				$page = new SpotPage_login($db, $settings, $currentSession,
							Array('loginform' => $req->getForm('loginform', array('submit'))));
				$page->render();
				break;
		} # login

		case 'postcomment' : {
				$page = new SpotPage_postcomment($db, $settings, $currentSession,
							Array('commentform' => $req->getForm('postcommentform', array('submit')),
								  'inreplyto' => $req->getDef('inreplyto', '')));
				$page->render();
				break;
		} # postcomment

		case 'logout' : {
				$page = new SpotPage_logout($db, $settings, $currentSession);
				$page->render();
				break;
		} # logout

		case 'sabapi' : {
			$page = new SpotPage_sabapi($db, $settings, $currentSession);
			$page->render();
			break;
		} # sabapi
		
		default : {
				if (@$_SERVER['HTTP_X_PURPOSE'] == 'preview') {
					$page = new SpotPage_speeddial($db, $settings, $currentSession);
				} else {
					$page = new SpotPage_index($db, $settings, $currentSession,
							Array('search' => $req->getDef('search', $settings->get('index_filter')),
								  'pagenr' => $req->getDef('pagenr', 0),
								  'sortby' => $req->getDef('sortby', ''),
								  'sortdir' => $req->getDef('sortdir', ''),
								  'messageid' => $req->getDef('messageid', ''),
								  'action' => $req->getDef('action', ''))
					);
				}
				$page->render();
				break;
		} # default
	} # switch
	SpotTiming::stop('renderpage');

	# timing
	SpotTiming::stop('total');

	# enable of disable de timer
	if (($settings->get('enable_timing')) && (!in_array(SpotReq::getDef('page', ''), array('catsjson', 'statics', 'getnzb', 'markallasread', 'rss')))) {
		SpotTiming::display();
	} # if
	
}
catch(Exception $x) {
	var_dump($x);
	die($x->getMessage());
} # catch
