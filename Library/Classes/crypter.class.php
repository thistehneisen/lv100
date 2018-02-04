<?php //ā
/********************************************************************
                      IMPLEMENTING FUNCTIONS...
********************************************************************/
class Crypter {
	public $obj = array('mode' => MCRYPT_MODE_CBC,
		'padding' => "",
		'KeySize' => MCRYPT_RIJNDAEL_128);

	function encrypt($plaintext, $key) {
		$obj          = json_decode(json_encode($this->obj));
		$mode         = $obj->{'mode'};
		$padding      = $obj->{'padding'};
		$KeySize      = $obj->{'KeySize'};
		$KeySizeInt   = $this->parseKeyInt($KeySize);

		$salt         = $this->generate_iv($KeySize, $mode);
		$key          = $this->PBKDF2($key, $salt, 1, $KeySizeInt); // key
		$iv           = $this->generate_iv($KeySize, $mode);        // IV

		try  {
			// encrypt
			$ciphertext = mcrypt_encrypt($KeySize, $key, $plaintext, $mode, $iv);
		}


		catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}


		$json = array('iv' => base64_encode($iv),
			'mode' => $mode,
			'padding' => "",
			'KeySize' => $KeySize,
			'cipher' => "aes",
			'salt' => base64_encode($salt),
			'ciphertext' => base64_encode($ciphertext)
		);
		$json_string = json_encode($json);

		return base64_encode($json_string);
	}


	function decrypt($json_ciphertext, $key) {
		$this->obj = array('mode' => MCRYPT_MODE_CBC,
		'padding' => "",
		'KeySize' => MCRYPT_RIJNDAEL_128);
		$obj          = json_decode(base64_decode($json_ciphertext));
		$iv           = base64_decode($obj->{'iv'});         // IV
		$salt         = base64_decode($obj->{'salt'});
		$mode         = $obj->{'mode'};
		$padding      = $obj->{'padding'};
		$KeySize      = $obj->{'KeySize'};
		$KeySizeInt   = $this->parseKeyInt($KeySize);
		$ciphertext   = base64_decode($obj->{'ciphertext'});

		$key          = $this->PBKDF2($key, $salt, 1, $KeySizeInt); // key

		try  {
			// decrypt
			$plaintext  = mcrypt_decrypt($KeySize, $key, $ciphertext, $mode, $iv);
		}


		catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}


		return $plaintext;
	}


	/**
	 *  PBKDF2 Implementation (as described in RFC 2898);
	 *  For more information see: http://www.ietf.org/rfc/rfc2898.txt
	 *
	 *  @param string   $p   password
	 *  @param string   $s   salt
	 *  @param int      $c   iteration count (use 1000 or higher)
	 *  @param int      $kl  derived key length
	 *  @param string   $a   hash algorithm
	 *
	 *  @return string derived key
	 */
	function PBKDF2( $p, $s, $c, $kl, $a = 'sha256' ) {
		$hl = strlen( hash( $a, null, true ) );
		$kb = ceil( $kl / $hl );
		$dk = '';

		// Create key
		for ( $block = 1; $block <= $kb; $block++ ) {
			// Initial hash for this block
			$ib = $b = hash_hmac( $a, $s . pack( 'N', $block ), $p, true );

			// Perform block iterations
			for ( $i = 1; $i < $c; $i++ ) {
				// XOR each iterate
				$ib ^= ( $b = hash_hmac( $a, $b, $p, true ) );
			}

			// Append iterated block
			$dk .= $ib;
		}

		// Return derived key of correct length
		return substr( $dk, 0, $kl );
	}


	function generate_iv($KeySize, $mode) {
		$iv_size  = mcrypt_get_iv_size($KeySize, $mode);
		$iv       = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return $iv;
	}


	function parseKeyInt($KeySize) {
		$key="";
		if ($KeySize == MCRYPT_RIJNDAEL_128) {
			$key=16;
		}
		else if ($KeySize == MCRYPT_RIJNDAEL_192) {
				$key=24;
			}
		else if ($KeySize == MCRYPT_RIJNDAEL_256) {
				$key=32;
			}
		return $key;
	}


}


?>