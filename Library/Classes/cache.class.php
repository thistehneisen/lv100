<?php

	/**
	 * Created by PhpStorm.
	 * User: Arnolds
	 * Date: 02.12.14
	 * Time: 17:09
	 */
	class Cache {
		private $cacheDir;

		function __construct($cacheDir) {
			$this->cacheDir = $cacheDir;
		}

		function get($name, $subName = null, $ttl = 86400) {
			$file = (is_null($subName) ? "default.cache" : md5($subName).".cache");
			$path = $this->cacheDir."/".md5($name)."/".$file;
			if (is_file($path) && file_exists($path)) {
				if (filemtime($path) < time()-$ttl) {
					unlink($path);
					return null;
				}
				else {
					return unserialize(base64_decode(file_get_contents($path)));
				}
			}
			else {
				return null;
			}
		}

		function put($name, $subName = null, $data) {
			$umask = umask(0);
			$file = (is_null($subName) ? "default.cache" : md5($subName).".cache");
			$path = $this->cacheDir."/".md5($name)."/".$file;
			if (!is_dir($this->cacheDir."/".md5($name))) {
				mkdir($this->cacheDir."/".md5($name),0777,true);
			}
			file_put_contents($path, base64_encode(serialize($data)));
			umask($umask);
		}

		function purge($name, $subName = null) {
			$file = (is_null($subName) ? "*.cache" : md5($subName).".cache");
			$path = $this->cacheDir."/".md5($name)."/".$file;
			$files = glob($path);
			if (is_array($files)) {
				foreach ($files as $file) {
					if (is_file($file) && file_exists($file)) {
						unlink($file);
					}
				}
			}
		}
	}