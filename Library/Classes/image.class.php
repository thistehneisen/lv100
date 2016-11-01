<?php


	class Image {

		public $image;
		public $name;
		public $width;
		public $height;
		public $orig_ratio;
		public $orig_type;
		public $orig_mime;
		public $transparencyChecked = false;

		function __construct($filename) {
			$this->name = basename($filename);
			$size = getimagesize($filename);
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			switch ($size[2]) {
				case IMAGETYPE_JPEG:
					$this->image = imagecreatefromjpeg($filename);
					break;
				case IMAGETYPE_PNG:
					$this->image = imagecreatefrompng($filename);
					break;
				case IMAGETYPE_GIF:
					$this->image = imagecreatefromgif($filename);
					break;
				default:
					switch (strtolower($extension)) {
						case "jpeg":
						case "jpg":
							$this->image = imagecreatefromjpeg($filename);
							break;
						case "png":
							$this->image = imagecreatefrompng($filename);
							break;
						case "gif":
							$this->image = imagecreatefromgif($filename);
							break;
						default:
							$this->image = null;
							break;
					}
					break;
			}
			imageinterlace($this->image, true);
			$this->orig_type = $size[2];
			$this->orig_mime = $size['mime'];
			if ($size[1]) $this->orig_ratio = $size[0] / $size[1];
			$this->width = $size[0];
			$this->height = $size[1];
		}


		function resize($nwidth, $nheight, $min = false, $keepratio = true) {
			if ($min == false && $keepratio) {
				$pnh = $nheight;
				$pnw = floor($pnh * $this->orig_ratio);
				if ($pnw > $nwidth) {
					$pnw = $nwidth;
					$pnh = floor($pnw / $this->orig_ratio);
				}
			} else if ($keepratio) {
				$pnh = $nheight;
				$pnw = floor($pnh * $this->orig_ratio);
				if ($pnw < $nwidth) {
					$pnw = $nwidth;
					$pnh = floor($pnw / $this->orig_ratio);
				}
			} else {
				$pnh = $nheight;
				$pnw = $nwidth;
			}
			if ($this->width <= $nwidth && $this->height <= $nheight && !$min) {
				$pnh = $this->height;
				$pnw = $this->width;
			}
			$tmp_image = imagecreatetruecolor($pnw, $pnh);
			if (($this->orig_type == IMAGETYPE_GIF) || ($this->orig_type == IMAGETYPE_PNG)) {
				$transparency = imagecolortransparent($this->image);
				$palletsize = imagecolorstotal($this->image);
				if ($transparency >= 0 && $transparency < $palletsize) {
					$transparent_color = imagecolorsforindex($this->image, $transparency);
					$transparency = imagecolorallocate($tmp_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
					imagefill($tmp_image, 0, 0, $transparency);
					imagecolortransparent($tmp_image, $transparency);
				} elseif ($this->orig_type == IMAGETYPE_PNG) {
					imagealphablending($tmp_image, false);
					$color = imagecolorallocatealpha($tmp_image, 0, 0, 0, 127);
					imagefill($tmp_image, 0, 0, $color);
					imagesavealpha($tmp_image, true);
					$this->transparencyChecked = true;
				}
			}
			imagecopyresampled($tmp_image, $this->image, 0, 0, 0, 0, $pnw, $pnh, $this->width, $this->height);
			$this->image = $tmp_image;
			$this->width = $pnw;
			$this->height = $pnh;

			return $this->image;
		}


		function crop($width, $height, $resize = true) {
			if ($resize) $this->resize($width, $height, true);
			if ($this->width <= $width) {
				// croping verticaly
				$src_x = 0;
				$src_y = floor(($this->height - $height) / 2);
			} else {
				// croping horizontaly
				$src_x = floor(($this->width - $width) / 2);
				$src_y = 0;
			}
			$tmp_image = imagecreatetruecolor($width, $height);
			if (($this->orig_type == IMAGETYPE_GIF) || ($this->orig_type == IMAGETYPE_PNG)) {
				$transparency = imagecolortransparent($this->image);
				$palletsize = imagecolorstotal($this->image);
				if ($transparency >= 0 && $transparency < $palletsize) {
					$transparent_color = imagecolorsforindex($this->image, $transparency);
					$transparency = imagecolorallocate($tmp_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
					imagefill($tmp_image, 0, 0, $transparency);
					imagecolortransparent($tmp_image, $transparency);
				} elseif ($this->orig_type == IMAGETYPE_PNG) {
					imagealphablending($tmp_image, false);
					$color = imagecolorallocatealpha($tmp_image, 0, 0, 0, 127);
					imagefill($tmp_image, 0, 0, $color);
					imagesavealpha($tmp_image, true);
					$this->transparencyChecked = true;
				}
			}
			imagecopyresampled($tmp_image, $this->image, 0, 0, $src_x, $src_y, $width, $height, $width, $height);
			$this->image = $tmp_image;
			$this->width = $width;
			$this->height = $height;

			return $this->image;
		}


		function _crop($x, $y, $x2 = 0, $y2 = 0) {
			$x = round($x);
			$x2 = round($x2);
			$y = round($y);
			$y2 = round($y2);
			$tmp_image = imagecreatetruecolor($x2 - $x, $y2 - $y);
			if (($this->orig_type == IMAGETYPE_GIF) || ($this->orig_type == IMAGETYPE_PNG)) {
				$transparency = imagecolortransparent($this->image);
				$palletsize = imagecolorstotal($this->image);
				if ($transparency >= 0 && $transparency < $palletsize) {
					$transparent_color = imagecolorsforindex($this->image, $transparency);
					$transparency = imagecolorallocate($tmp_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
					imagefill($tmp_image, 0, 0, $transparency);
					imagecolortransparent($tmp_image, $transparency);
				} elseif ($this->orig_type == IMAGETYPE_PNG) {
					imagealphablending($tmp_image, false);
					$color = imagecolorallocatealpha($tmp_image, 0, 0, 0, 127);
					imagefill($tmp_image, 0, 0, $color);
					imagesavealpha($tmp_image, true);
					$this->transparencyChecked = true;
				}
			}
			imagecopyresampled($tmp_image, $this->image, 0, 0, $x, $y, $x2 - $x, $y2 - $y, $x2 - $x, $y2 - $y);
			$this->image = $tmp_image;
			$this->width = $x2 - $x;
			$this->height = $y2 - $y;

			return $this->image;
		}


		function rotate($angle) {
			$angle = (-1) * $angle;
			if (abs($angle) > 360) $angle = $angle % 360;
			if ($angle < 0) $angle = 360 + $angle;
			if ($angle == 360) $angle = 0;
			//imagerotate ( resource $image , float $angle , int $bgd_color [, int $ignore_transparent = 0 ] )
			$tmp_image = imagerotate($this->image, $angle, 0, false);
			imagealphablending($tmp_image, true);
			imagesavealpha($tmp_image, true);

			$this->image = $tmp_image;
			$this->width = imagesx($this->image);
			$this->height = imagesy($this->image);

			return $this->image;
		}

		function watermark($watermark) {
			//return true;
			$stamp = $watermark->image;
			$marge_right = 10;
			$marge_bottom = 10;
			$sx = imagesx($stamp);
			$sy = imagesy($stamp);
			$dy = imagesy($this->image)/10;
			$dx = $sx*$dy/$sy;

			imagecopyresampled($this->image, $stamp, imagesx($this->image) - $dx - $marge_right, imagesy($this->image) - $dy - $marge_bottom, 0, 0, $dx, $dy, $sx, $sy);
			//imagecopy($this->image, $stamp, imagesx($this->image) - $dx - $marge_right, imagesy($this->image) - $dy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
		}


		function save($filename, $quality = 100) {
			$pngQuality = ($quality - 100) / 11.111111;
			$pngQuality = round(abs($pngQuality));
			if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), array("jpg", "jpeg"))) {
				imagejpeg($this->image, $filename, $quality);
			} else if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) == "png") {
				if (!$this->transparencyChecked) {
					imagealphablending($this->image, false);
					imagesavealpha($this->image, true);
				}
				imagepng($this->image, $filename, $pngQuality);
			} else if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) == "gif") {
				imagegif($this->image, $filename);
			}
		}


		function read($quality = 100) {
			$pngQuality = ($quality - 100) / 11.111111;
			$pngQuality = round(abs($pngQuality));
			imagepng($this->image, null, $pngQuality);
		}


		function __destruct() {
			if (is_resource($this->image)) {
				imagedestroy($this->image);
			}
		}


	}


?>