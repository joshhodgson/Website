<?php namespace uk\co\la1tv\website\serviceProviders\smartCache;

use Cache;
use Carbon;
use Event;
use Queue;
use App;

class SmartCacheManager {
	
	// if the object is cached and not old return cached version.
	// otherwise cache object and return it
	// $forceRefresh will force cache to be updated if it is older than half of the timeout period
	// $providerName is the name registered in the IOC container.
	// $providerMethod is the name of the method to call on the provider
	// $providerMethodArgs is an array of arguments to supply to the provider method
	public function get($key, $seconds, $providerName, $providerMethod, $providerMethodArgs=array(), $forceRefresh=false) {
		// the first time the : must appear must be straight before $key
		// otherwise there could be conflicts
		$keyStart = "smartCache";
		$fullKey = $keyStart . ":" . $key;
		// the key that will exist if the cache item is currently being created
		$creatingCacheKey = $keyStart . ".creating:" . $key;
		// time to wait in seconds before presuming item could not be created in cache because
		// there was an issue.
		$creationTimeout = 60;
		
		// get the cached version if there is one
		$responseAndTime = Cache::get($fullKey, null);
		
		if (!is_null($responseAndTime)) {
			// check it hasn't expired
			// cache driver only works in minutes which is why this is necessary
			if ($responseAndTime["time"] < Carbon::now()->timestamp - $seconds) {
				// it's expired. pretend it's not in the cache
				$responseAndTime = null;
			}
		}
		
		if (is_null($responseAndTime)) {
			// check to see if the cache is currently being updated and wait, then try agian, if it is
			$now = Carbon::now()->timestamp;
			$timeStartedCreating = Cache::get($creatingCacheKey, null);
			if (!is_null($timeStartedCreating) && $timeStartedCreating >= $now-$creationTimeout) {
				// no point forcing a refresh as a refresh is already happening,
				// so the latest version will be retrieved anyway
				$forceRefresh = false;
				// wait for cache to contain item, or timeout creating item
				for ($i=0; $i<($creationTimeout-($now-$timeStartedCreating))*10; $i++) {
					usleep(100 * 1000); // 0.1 seconds
					if (is_null(Cache::get($creatingCacheKey, null))) {
						// item created or key removed because timed out
						break;
					}
				}
				// try again
				return $this->get($key, $seconds, $providerName, $providerMethod, $providerMethodArgs, $forceRefresh);
			}	
		}
		
		if ($forceRefresh && !is_null($responseAndTime)) {
			if (Carbon::now()->timestamp - $responseAndTime["time"] <= $seconds / 2) {
				// don't force a refresh because the cache isn't older than half the time period
				$forceRefresh = false;
			}
		}
		
		if (!is_null($responseAndTime)) {
			if (Carbon::now()->timestamp - $responseAndTime["time"] > $seconds / 2) {
				// refresh the cache in the background as > half the time has passed
				// before a refresh would be required
				// the app.finish event is fired after the response has been returned to the user.
				Event::listen('app.finish', function() use (&$key, &$seconds, &$providerName, &$providerMethod, &$providerMethodArgs) {
					Queue::push("uk\co\la1tv\website\serviceProviders\smartCache\SmartCacheQueueJob", [
						"key"					=> $key,
						"seconds"				=> $seconds,
						"providerName"			=> $providerName,
						"providerMethod"		=> $providerMethod,
						"providerMethodArgs"	=> $providerMethodArgs
					]);
				});
			}
		}
		
		if (is_null($responseAndTime) || $forceRefresh) {
			// create the key which will be checked to determine that work is being done.
			// it is possible for this point in the code to be reached by several processes at the same time,
			// but it is unlikely, and if it happens it just means the cache will be updated several times
			// which isn't a huge issue. Otherwise would need to use Semaphores and this gets a bit messy in php
			Cache::put($creatingCacheKey, Carbon::now()->timestamp, ceil($creationTimeout/60));
			$responseAndTime = [
				"time"		=> Carbon::now()->timestamp,
				"response"	=> call_user_func_array([App::make($providerName), $providerMethod], $providerMethodArgs)
			];
			// the cache driver only works in minutes
			Cache::put($fullKey, $responseAndTime, ceil($seconds/60));
			Cache::forget($creatingCacheKey);
		}
		return $responseAndTime["response"];
	}
}