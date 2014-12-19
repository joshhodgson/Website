<?php namespace uk\co\la1tv\website\controllers\home;

use uk\co\la1tv\website\controllers\BaseController;
use URL;
use Csrf;
use Auth;
use Config;
use uk\co\la1tv\website\models\Show;
use uk\co\la1tv\website\models\Playlist;
use uk\co\la1tv\website\models\LiveStream;
use Facebook;
use Request;
use MyResponse;
use View;
use URLHelpers;

class HomeBaseController extends BaseController {

	protected $layout = null;
	
	protected function setContent($content, $navPage, $cssPageId, $openGraphProperties=array(), $title=NULL, $statusCode=200) {
		
		$description = "Lancaster University's Student Union TV station.";
	
		$view = View::make("layouts.home.master");
	
		$view->baseUrl = URL::to("/");
		$view->currentNavPage = $navPage;
		$view->cssPageId = $cssPageId;
		$view->title = "LA1:TV";
		if (!is_null($title)) {
			$view->title .= ": ".$title;
		}
		$view->description = $description;
		$view->content = $content;
		$view->allowRobots = true;
		$view->cssBootstrap = asset("assets/css/bootstrap/home.css");
		$view->requireJsBootstrap = asset("assets/scripts/bootstrap/home.js");
		$view->loggedIn = Facebook::isLoggedIn();
		$view->pageData = array(
			"baseUrl"		=> URL::to("/"),
			"cookieDomain"	=> Config::get("cookies.domain"),
			"cookieSecure"	=> Config::get("ssl.enabled"),
			"assetsBaseUrl"	=> asset(""),
			"csrfToken"		=> Csrf::getToken(),
			"loggedIn"		=> Facebook::isLoggedIn(),
			"gaEnabled"		=> Config::get("googleAnalytics.enabled")
		);
		$facebookAppId = Config::get("facebook.appId");
		$defaultOpenGraphProperties = array();
		if (!is_null($facebookAppId)) {
			$defaultOpenGraphProperties[] = array("name"=> "fb:app_id", "content"=> $facebookAppId);
		}
		$defaultOpenGraphProperties[] = array("name"=> "og:title", "content"=> "LA1:TV");
		$defaultOpenGraphProperties[] = array("name"=> "og:url", "content"=> Request::url());
		$defaultOpenGraphProperties[] = array("name"=> "og:locale", "content"=> "en_GB");
		$defaultOpenGraphProperties[] = array("name"=> "og:site_name", "content"=> "LA1:TV");
		$defaultOpenGraphProperties[] = array("name"=> "og:description", "content"=> $description);
		$defaultOpenGraphProperties[] = array("name"=> "og:image", "content"=> Config::get("custom.open_graph_logo_uri"));
		$usedOpenGraphNames = array();
		$finalOpenGraphProperties = array();
		foreach($openGraphProperties as $a) {
			if (!is_null($a['content'])) {
				$finalOpenGraphProperties[] = $a;
			}
			if (!in_array($a['name'], $usedOpenGraphNames)) {
				$usedOpenGraphNames[] = $a['name'];
			}
		}
		foreach($defaultOpenGraphProperties as $a) {
			if (!in_array($a['name'], $usedOpenGraphNames)) {
				$finalOpenGraphProperties[] = $a;
			}
		}
		$view->openGraphProperties = $finalOpenGraphProperties;
		$view->promoAjaxUri = Config::get("custom.live_shows_uri");
		
		$view->loginUri = URLHelpers::generateLoginUrl();
		$view->homeUri = Config::get("custom.base_url");
		$view->guideUri = Config::get("custom.base_url") . "/guide";
		$view->blogUri = Config::get("custom.blog_url");
		$view->contactUri = Config::get("custom.base_url") . "/contact";
		$view->accountUri = Config::get("custom.base_url") . "/account";
		
		// recent shows in dropdown
		$shows = Show::getCachedActiveShows();
		$view->showsDropdown = array();
		foreach($shows as $a) {
			$view->showsDropdown[] = array("uri"=>Config::get("custom.base_url") . "/show/".$a->id, "text"=>$a->name);
		}
		$view->showsUri = Config::get("custom.base_url") . "/shows";
		
		// recent playlists dropdown
		$playlists = Playlist::getCachedActivePlaylists(false);
		$view->playlistsDropdown = array();
		foreach($playlists as $a) {
			$view->playlistsDropdown[] = array("uri"=>Config::get("custom.base_url") . "/playlist/".$a->id, "text"=>$a->name);
		}
		$view->playlistsUri = Config::get("custom.base_url") . "/playlists";
		
		$contentSecurityPolicyDomains = LiveStream::getCachedLiveStreamDomains();
		
		$response = new MyResponse($view, $statusCode);
		$response->setContentSecurityPolicyDomains($contentSecurityPolicyDomains);
		$this->layout = $response;
	}

}
