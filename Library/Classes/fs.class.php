<?php


	class FS {
		private $umask = 0;

		function __construct() {
			$GLOBALS["_fs"] = &$this;
		}

		function copy($source, $new_name = null, $overwrite = false) {
			if (is_null($new_name)) $new_name = basename($source);
			if (substr($source, 0, 1) != "/" && substr($source, 0, 4) != "http") $source = Page()->path . $source;
			$this->openMask();
			$path = $this->todaysFolder();
			$file = pathinfo($new_name, PATHINFO_FILENAME) . "." . pathinfo($new_name, PATHINFO_EXTENSION);
			$file = array_value(explode("?",$file));
			while (file_exists(Page()->path . $path . $file) && !$overwrite) {
				if (!isset($incr)) {
					$incr = 1;
				} else $incr++;
				$file = pathinfo($new_name, PATHINFO_FILENAME) . "." . $incr . "." . pathinfo($new_name, PATHINFO_EXTENSION);
				$file = array_value(explode("?",$file));
			}
			$copied = copy($source, Page()->path . $path . $file);
			$this->closeMask();

			return $copied ? $path . $file : false;
		}

		function move($source, $new_name = null, $overwrite = false) {
			if (is_null($new_name)) $new_name = basename($source);
			if (substr($source, 0, 1) != "/" && substr($source, 0, 4) != "http") $source = Page()->path . $source;
			$this->openMask();
			$path = $this->todaysFolder();
			$file = pathinfo($new_name, PATHINFO_FILENAME) . "." . pathinfo($new_name, PATHINFO_EXTENSION);
			$file = array_value(explode("?",$file));
			while (file_exists(Page()->path . $path . $file) && !$overwrite) {
				if (!isset($incr)) {
					$incr = 1;
				} else $incr++;
				$file = pathinfo($new_name, PATHINFO_FILENAME) . "." . $incr . "." . pathinfo($new_name, PATHINFO_EXTENSION);
				$file = array_value(explode("?",$file));
			}
			$copied = is_writable($source) ? rename($source, Page()->path . $path . $file) : false;
			$this->closeMask();

			return $copied ? $path . $file : false;
		}

		function getThumb($source, $width = 0, $height = 0) {
			if (substr($source, 0, 1) != "/" && substr($source, 0, 4) != "http") $source = Page()->path . $source;
			$this->openMask();

			$hash = $this->getHash($source);

			$path = "Cache/Images/" . $width . "x" . $height . "/" . substr($hash, 0, 6) . "/";
			$file = $hash . "." . strtolower(pathinfo($source, PATHINFO_EXTENSION));
			$file = array_value(explode("?",$file));
			if (!is_dir(Page()->path . $path)) {
				mkdir(Page()->path . $path, 0777, true);
			}
			if (!is_file(Page()->path . $path . $file)) {
				// make
				$img = new Image($source);
				if (!$img->width || !$img->height) return false;
				if ($width && $height) {
					if ($img->width < $width || $img->height < $height) $img->resize($width, $height, true);
					$img->crop($width, $height);
				} else {
					$img->resize($width == 0 ? 10000 : $width, $height == 0 ? 10000 : $height);
				}
				$img->save(Page()->path . $path . $file);
			}
			$this->closeMask();

			return $path . $file;
		}

		function remThumb($source) {
			if (substr($source, 0, 1) != "/" && substr($source, 0, 4) != "http") $source = Page()->path . $source;
			$hash = $this->getHash($source);
			$files = glob(Page()->path . "Cache/Images/*/" . substr($hash, 0, 6) . "/" . $hash . "." . strtolower(pathinfo($source, PATHINFO_EXTENSION)));
			foreach ($files as $file) {
				if (file_exists($file)) {
					@unlink($file);
				}
			}
		}

		function openMask() {
			$this->umask = umask(0);
		}

		function closeMask() {
			umask($this->umask);
		}

		function isLocal($source) {
			if (substr($source, 0, 1) != "/" && substr($source, 0, 4) != "http") $source = Page()->path . $source;

			return file_exists($source) && strstr($source, Page()->path, true) === "";
		}

		function relativePath($source) {
			if (substr($source, 0, 1) != "/" && substr($source, 0, 4) != "http") $source = Page()->path . $source;
			if (!$this->isLocal($source)) {
				return $source;
			} else return substr($source, strlen(Page()->path));
		}

		private function getHash($source) {
			return md5($this->relativePath($source));
		}

		private function todaysFolder() {
			$this->openMask();
			$folder = "Uploads/" . date("Y") . "/" . date("m") . "/" . date("d") . "/";
			if (!is_dir(Page()->path . $folder)) {
				mkdir(Page()->path . $folder, 0777, true);
			}
			$this->closeMask();

			return $folder;
		}

		function registerMedia($file, $node, $id, $deleteOld = false) {
			try {
				$arr = array();
				if (is_array($file)) {
					$arr = $file;
				} else if (!file_exists(Page()->path . $file)) {
					$str = $file;
					phpQuery::newDocumentHTML($str);
					foreach (pq("[href][data-local]") as $el) {
						$parts = explode("?", pq($el)->attr("href"));
						$arr[] = $parts[0];
					}
					foreach (pq("[src][data-local]") as $el) {
						$parts = explode("?", pq($el)->attr("src"));
						$arr[] = $parts[0];
					}
				} else {
					$arr = array($file);
				}
				foreach ($arr as $file) {
					$file_id = DataBase()->getVar("SELECT `id` FROM %s WHERE `filepath`='%s'", DataBase()->media, $file);
					if ($file_id) {
						$is_old = DataBase()->getVar("SELECT `media_id` FROM %s WHERE `node_id`='%s' AND `media_subid`='%s'", DataBase()->media_relations, $node, $id);
						if ($is_old && $is_old != $file_id) {
							if ($deleteOld) {
								$old_media = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->media, $is_old);
								if (Page()->path . $old_media["filepath"] && file_exists(Page()->path . $old_media["filepath"]) && is_writable(Page()->path . $old_media["filepath"])) {
									unlink(Page()->path . $old_media["filepath"]);
								}
								DataBase()->queryf("DELETE FROM %s WHERE `id`='%s'", DataBase()->media, $is_old);
								DataBase()->queryf("DELETE FROM %s WHERE `media_id`='%s'", DataBase()->media_relations, $is_old);
							}
						}
						DataBase()->queryf("DELETE FROM %s WHERE `media_id`='%s' AND `node_id`='%s' AND `media_subid`='%s'", DataBase()->media_relations, $file_id, $node, $id);
						DataBase()->insert("media_relations", array(
							"media_id"    => $file_id,
							"node_id"     => $node,
							"media_subid" => $id
						), true);
					}
				}
			} catch (Exception $e) {
				Page()->debug($e);
			}
		}

		function deleteMedia($file, $node, $id) {
			$old_media = DataBase()->getRow("SELECT * FROM %s WHERE `filepath`='%s'", DataBase()->media, $file);
			DataBase()->queryf("DELETE FROM %s WHERE `media_id`='%s' AND `node_id`='%s' AND `media_subid`='%s'", DataBase()->media_relations, $old_media["id"], $node, $id);
			$media_locked = DataBase()->getVar("SELECT '1' FROM %s WHERE `media_id`='%s'", DataBase()->media_relations, $old_media["id"]);

			if (!$media_locked) {
				if (Page()->path . $old_media["filepath"] && file_exists(Page()->path . $old_media["filepath"]) && is_writable(Page()->path . $old_media["filepath"])) {
					unlink(Page()->path . $old_media["filepath"]);
				}
				DataBase()->queryf("DELETE FROM %s WHERE `id`='%s'", DataBase()->media, $old_media["id"]);
			}
		}

		function unregisterMedia($node, $id) {
			// Deletes references but not media itself
			DataBase()->queryf("DELETE FROM %s WHERE `node_id`='%s' AND `media_subid`='%s'", DataBase()->media_relations, $node, $id);
		}
	}

	/**
	 * @return FS Pointer to FS instance
	 */
	function FS() {
		return $GLOBALS["_fs"];
	}

	if (!function_exists("Page")) {
		function Page() {
			$ret = (object)array();
			$ret->path = realpath(__DIR__ . "/../../") . "/";

			return $ret;
		}
	}