<?php namespace uk\co\la1tv\website\serviceProviders\cosign;

// http://webapps.itcs.umich.edu/cosign/index.php/Cosign_Wiki:CosignFilterSpec
class Cosign {
	
	private $service;
	private $filterDbLocation;
	private $username = null;
	private $ip = null;
	private $factors = null;
	private $realm = null;
	private $requested = false;
	
	public function __construct($service, $filterDbLocation="/var/cosign/filter") {
		$this->service = $service;
		$this->filterDbLocation = $filterDbLocation;
	}
	
	private function makeRequest() {
		if ($this->requested) {
			return;
		}
		$this->requested = true;
		
		// get the cosign cookie val
		$cookieName = str_replace(".", "_", $this->service);
		$key = isset($_COOKIE[$cookieName]) ? $_COOKIE[$cookieName] : null;
		
		echo($key);
		if (is_null($key)) {
			dd("not found");
			return;
		}
		if (preg_match("/^[A-Za-z0-9\+-_\/ ]+$/", $key) !== 1) {
			// key contains unexpected characters
			dd("failed");
			return;
		}
		// TODO: add @
		$handle = fopen($this->filterDbLocation."/".$cookieName."=".str_replace(" ", "+", explode("/", $key, 2)[0]), "r");
		if ($handle === FALSE) {
			dd("failed read");
			return;
		}
		
		while (($line = fgets($handle)) !== false) {
			if (strlen($line) < 2) {
				continue;
			}
			$type = substr($line, 0, 1);
			$value = substr($line, 1);
			echo($type."=".$value);
			if ($type === "i") {
				$this->ip = $value;
			}
			else if ($type === "p") {
				$this->username = $value;
			}
			else if ($type === "r") {
				$this->realm = $value;
			}
			else if ($type === "f") {
				if (is_null($this->factors)) {
					$this->factors = array();
				}
				$this->factors[] = $value;
			}
		}
		fclose($handle);
		
		if ($contents === FALSE) {
			return;
		}
		
	}
	
	public function getUsername() {
		$this->makeRequest();
		return $this->username;
	}
	
	public function getIp() {
		$this->makeRequest();
		return $this-ip;
	}
	
	public function getFactors() {
		$this->makeRequest();
		return $this->factors;
	}
	
	public function getRealm() {
		$this->makeRequest();
		return $this->realm;
	}

}