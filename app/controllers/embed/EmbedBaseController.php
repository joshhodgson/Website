<?php namespace uk\co\la1tv\website\controllers\embed;

use uk\co\la1tv\website\controllers\BaseController;
use URL;
use Csrf;
use Config;
use App;
use View;
use uk\co\la1tv\website\models\LiveStream;
use MyResponse;
use Facebook;
use DebugHelpers;

class EmbedBaseController extends BaseController {

	protected $layout = null;
	
	protected function setContent($content, $cssPageId, $title=NULL) {
		$view = View::make("layouts.embed.master");
		
		$view->baseUrl = URL::to("/");
		$view->cssPageId = $cssPageId;
		$view->title = !is_null($title) ? $title : "LA1:TV";
		$view->description = "";
		$view->content = $content;
		$view->allowRobots = false;
		$view->cssBootstrap = asset("assets/css/bootstrap/embed.css");
		$view->requireJsBootstrap = asset("assets/scripts/bootstrap/embed.js");	
		$view->pageData = array(
			"baseUrl"		=> URL::to("/"),
			"cookieDomain"	=> Config::get("cookies.domain"),
			"cookieSecure"	=> Config::get("ssl.enabled"),
			"assetsBaseUrl"	=> asset(""),
			"logUri"		=> Config::get("custom.log_uri"),
			"debugId"		=> DebugHelpers::getDebugId(),
			"csrfToken"		=> Csrf::getToken(),
			"loggedIn"		=> Facebook::isLoggedIn(),
			"gaEnabled"		=> Config::get("googleAnalytics.enabled"),
			"env"			=> App::environment()
		);
		
		$contentSecurityPolicyDomains = LiveStream::getCachedLiveStreamDomains();
		$response = new MyResponse($view);
		$response->setContentSecurityPolicyDomains($contentSecurityPolicyDomains);
		$this->layout = $response;
	}

}
