<?php

	if (ActiveUser()->canWrite("media")) {

		// Init
		$session_id = session_id();
		session_write_close();
		set_time_limit(5 * 60);
		umask(0);
		list($sec, $ms) = explode(",", microtime(true));

		// No-cache headers
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		Page()->setType("text/json");

		if ($_GET["delete"]) {
			$file = DataBase()->getRow("SELECT * FROM %s WHERE `id`='%s'", DataBase()->media, $_GET["delete"]);
			if ($file && !DataBase()->getVar("SELECT COUNT(*) FROM %s WHERE `media_id`='%s'", DataBase()->media_relations, $file["id"])) {
				DataBase()->queryf("DELETE FROM %s WHERE `id`='%s'", DataBase()->media, $file["id"]);
				if (file_exists(Page()->path . $file["filepath"])) unlink(Page()->path . $file["filepath"]);
			}
			header("Location: {$_SERVER["HTTP_REFERER"]}");
			exit;
		}

		if ($_GET["raw_crop"]) {
			$file = $_GET["i"];
			$thumb = FS()->copy((!preg_match("#^http[s]?\:\/\/#i", $file) ? Page()->path : "") . $file);

			$img = new Image(Page()->path . $thumb);
			$img->_crop((int)$_GET["x"], (int)$_GET["y"], $_GET["x"] + $_GET["w"], $_GET["y"] + $_GET["h"]);
			$img->save(Page()->path . $thumb, Page()->maxImageQuality);
			if ($_GET["r"]) {
				$img = new Image(Page()->path . $thumb);
				$img->resize($_GET["r"]["w"], $_GET["r"]["h"]);
				$img->save(Page()->path . $thumb, Page()->maxImageQuality);
			}
			if ($_GET["m"]["w"] && $_GET["m"]["h"]) {
				$img = new Image(Page()->path . $thumb);
				$img->resize($_GET["m"]["w"], $_GET["m"]["h"]);
				$img->save(Page()->path . $thumb, Page()->maxImageQuality);
			}
			$imgsize = getimagesize(Page()->path . $thumb);

			$original = 0;
			$filedata = DataBase()->getRow("SELECT * FROM %s WHERE `filepath`='%s'", DataBase()->media, str_replace(Page()->host, "", $file));
			if ($filedata) $original = $filedata["id"];

			DataBase()->insert("media", array(
				"filepath" => $thumb,
				"filename" => basename($thumb),
				"ext"      => pathinfo($file, PATHINFO_EXTENSION),
				"type"     => "photo",
				"size"     => filesize(Page()->path . $thumb),
				"width"    => (int)$imgsize[0],
				"height"   => (int)$imgsize[1],
				"created"  => strftime("%F %X"),
				"original" => $original
			));

			die(json_encode(array(
				"jsonrpc"      => "2.0",
				"fileOriginal" => $file,
				"fileThumb"    => $thumb,
				"fileName"     => basename($file),
				"size"         => array("w" => $imgsize[0], "h" => $imgsize[1])
			)));
		}

		// Upload to temp
		$targetDir = sys_get_temp_dir();
		$cleanupTargetDir = true;
		$maxFileAge = 5 * 3600;

		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
		$fileName = md5($_REQUEST["name"] . $session_id);

		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);
			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b)) {
				$count++;
			}
			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}
		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
		if (!file_exists($targetDir)) {
			@mkdir($targetDir);
		}
		if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
					@unlink($tmpfilePath);
				}
			}
			closedir($dir);
		} else {
			print(json_encode(array(
				"jsonrpc" => "2.0",
				"error"   => array(
					"code"    => 100,
					"message" => "Failed to open temp directory.",
					"id"      => "id"
				)
			)));
			exit;
		}

		$contentType = "";
		if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
		}
		if (isset($_SERVER["CONTENT_TYPE"])) {
			$contentType = $_SERVER["CONTENT_TYPE"];
		}
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
				if ($out) {
					$in = fopen($_FILES['file']['tmp_name'], "rb");
					if ($in) {
						while ($buff = fread($in, 4096)) {
							fwrite($out, $buff);
						}
					} else {
						print(json_encode(array(
							"jsonrpc" => "2.0",
							"error"   => array(
								"code"    => 101,
								"message" => "Failed to open input stream.",
								"id"      => "id"
							)
						)));
						exit;
					}
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else {
					print(json_encode(array(
						"jsonrpc" => "2.0",
						"error"   => array(
							"code"    => 102,
							"message" => "Failed to open output stream.",
							"id"      => "id"
						)
					)));
					exit;
				}
			} else {
				print(json_encode(array(
					"jsonrpc" => "2.0",
					"error"   => array(
						"code"    => 103,
						"message" => "Failed to move uploaded file.",
						"id"      => "id"
					)
				)));
				exit;
			}
		} else {
			$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				$in = fopen("php://input", "rb");
				if ($in) {
					while ($buff = fread($in, 4096)) {
						fwrite($out, $buff);
					}
				} else {
					print(json_encode(array(
						"jsonrpc" => "2.0",
						"error"   => array(
							"code"    => 101,
							"message" => "Failed to open input stream.",
							"id"      => "id"
						)
					)));
					exit;
				}
				fclose($in);
				fclose($out);
				@chmod("{$filePath}.part", 0777);
			} else {
				print(json_encode(array(
					"jsonrpc" => "2.0",
					"error"   => array(
						"code"    => 102,
						"message" => "Failed to open output stream.",
						"id"      => "id"
					)
				)));
				exit;
			}
		}

		// We got a file
		if (!$chunks || $chunk == $chunks - 1) {
			$fileName = $_REQUEST["name"];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);

			if ($ext == "php" || empty($ext)) {
				print(json_encode(array(
					"jsonrpc" => "2.0",
					"error"   => array(
						"code"    => 403,
						"message" => "PHP files and files without extensions not allowed.",
						"id"      => "id"
					)
				)));
				exit;
			}

			$file = FS()->move($filePath . ".part", $fileName);

			$original = false;
			if ($_GET["keep"]) {
				$original = FS()->copy(Page()->path . $file);
			}

			$return = array(
				"jsonrpc" => "2.0",
				"file"    => $file,
				"name"    => basename($file)
			);

			if (in_array(strtolower($ext), array("jpg", "jpeg", "png", "gif"))) {
				$_GET["type"] = "photo";
			}

			if ($_GET["type"] == "photo") {
				// Photo manipulations
				$img = new Image(Page()->path . $file);

				$box = false;
				$method = "";
				if (isset($_GET["resize"]) || isset($_GET["crop"])) {
					$box = explode("x", $_GET["resize"] ?: $_GET["crop"]);
					$method = $_GET["resize"] ? "resize" : "crop";
				} else if (!isset($_GET["hq"])) {
					$box = Page()->defaultImageBox;
					$method = "resize";
				}

				if ($method) {
					if ($img && $img->image) {
						$img->{$method}($box[0], $box[1]);
					}
				}

				$img->save(Page()->path . $file, Page()->maxImageQuality);

				if (isset($_GET["make_thumb"])) {
					$box = explode("x", $_GET["make_thumb"]);
					$return["thumb"] = FS()->getThumb(Page()->path . $file, $box[0], $box[1]);
				}

				$return["size"] = array($img->width, $img->height);
			}

			$originalId = 0;
			if ($original && $_GET["type"] == "photo") {
				$img = new Image(Page()->path . $original);

				$return["opts"] = array(
					"filename" => basename($original),
					"filepath" => $original,
					"width"    => $img->width,
					"height"   => $img->height,
					"type"     => "photo"
				);
				DataBase()->insert("media", array(
					"filename" => basename($original),
					"filepath" => $original,
					"ext"      => $ext,
					"type"     => $_GET["type"] == "photo" ? "photo" : "other",
					"size"     => filesize(Page()->path . $original),
					"width"    => $img->width,
					"height"   => $img->height,
					"created"  => strftime("%F %X"),
					"original" => 0
				));
				$originalId = DataBase()->insertid;
			}

			DataBase()->insert("media", array(
				"filepath" => $return["file"],
				"filename" => $return["name"],
				"ext"      => $ext,
				"type"     => $_GET["type"] == "photo" ? "photo" : "other",
				"size"     => filesize(Page()->path . $file),
				"width"    => (int)$return["size"][0],
				"height"   => (int)$return["size"][1],
				"created"  => strftime("%F %X"),
				"original" => $originalId
			));

			$return["id"] = DataBase()->insertid;

			if ($_POST["fileid"]) $return["fileid"] = $_POST["fileid"];

			if ($_GET["subtype"] == "swf") $return["thumb"] = "Library/Assets/swf.png";

			if ($_GET["gallery"]) {
				DataBase()->insert("gallery", array(
					"parent"  => $_GET["gallery"],
					"type"    => "photo",
					"path"    => $file,
					"caption" => "",
					"added"   => date("Y-m-d H:i:s"),
					"sort"    => 0
				));
				$id = DataBase()->insertid;
				$return["id"] = $id;
				$img = new Image(Page()->path . $file);
				$watermark = new Image(Page()->path . "Library/Assets/logo_watermark.png");
				$img->watermark($watermark);
				$img->save(Page()->path . $file);
			}

			print(json_encode($return));
			exit;
		}
	}