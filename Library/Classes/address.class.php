<?php


	/**
	 * Created by Burzum.
	 * User: Arnolds
	 * Date: 19.07.16
	 * Time: 15:07
	 */
	class Address {
		private $source;

		public function __construct($url) {
			if (!function_exists("Page") || !(Page() instanceof Page)) throw new Exception("Page() required.");
			$this->source = $url;

			return $this;
		}

		public function isLocal() {
			if (strpos($this->source, Page()->host) === 0) return true;
			if (strpos($this->source, "http://") === false) return true;

			return false;
		}

		public function isNode() {
			if ($this->isLocal()) {
				$first_match = Page()->getNodeByAddress($this->source, true);
				if ($first_match) {
					if ($first_match->original) {
						$first_match = Page()->getNode($first_match->original);
					}

					return $first_match->id;
				}
			}

			return false;
		}

		public function getURL() {
			$source_parts = parse_url($this->source);
			$isNode = $this->isNode();
			if ($isNode) {
				$address = Page()->getNode($isNode)->fullAddress;
				if ($source_parts["query"]) {
					$address .= "?" . $source_parts["query"];
				}
				if ($source_parts["fragment"]) {
					$address .= "#" . $source_parts["fragment"];
				}

				return $address;
			} else return $this->source;
		}
	}

	function Address($str) {
		$a = new Address($str);
		return $a->getURL();
	}