<?php

class cryptastic {

	/** Encryption Procedure
	 *
	 *	@param   mixed    msg      message/data
	 *	@param   string   k        encryption key
	 *	@param   boolean  base64   base64 encode result
	 *
	 *	@return  string   iv+ciphertext+mac or
	 *           boolean  false on error
	*/
	public function encrypt( $msg, $k, $base64 = false ) {

		# open cipher module (do not change cipher/mode)
		if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
			return false;

		$msg = serialize($msg);							# serialize
		$iv  = mcrypt_create_iv(32, MCRYPT_RAND);		# create iv

		if ( mcrypt_generic_init($td, $k, $iv) !== 0 )	# initialize buffers
			return false;

		$msg  = mcrypt_generic($td, $msg);				# encrypt
		$msg  = $iv . $msg;								# prepend iv
		$mac  = $this->pbkdf2($msg, $k, 1000, 32);		# create mac
		$msg .= $mac;									# append mac

		mcrypt_generic_deinit($td);						# clear buffers
		mcrypt_module_close($td);						# close cipher module

		if ( $base64 ) $msg = base64_encode($msg);		# base64 encode?

		return $msg;									# return iv+ciphertext+mac
	}

	/** Decryption Procedure
	 *
	 *	@param   string   msg      output from encrypt()
	 *	@param   string   k        encryption key
	 *	@param   boolean  base64   base64 decode msg
	 *
	 *	@return  string   original message/data or
	 *           boolean  false on error
	*/
	public function decrypt( $msg, $k, $base64 = false ) {

		if ( $base64 ) $msg = base64_decode($msg);			# base64 decode?

		# open cipher module (do not change cipher/mode)
		if ( ! $td = mcrypt_module_open('rijndael-256', '', 'ctr', '') )
			return false;

		$iv  = substr($msg, 0, 32);							# extract iv
		$mo  = strlen($msg) - 32;							# mac offset
		$em  = substr($msg, $mo);							# extract mac
		$msg = substr($msg, 32, strlen($msg)-64);			# extract ciphertext
		$mac = $this->pbkdf2($iv . $msg, $k, 1000, 32);		# create mac

		if ( $em !== $mac )									# authenticate mac
			return false;

		if ( mcrypt_generic_init($td, $k, $iv) !== 0 )		# initialize buffers
			return false;

		$msg = mdecrypt_generic($td, $msg);					# decrypt
		$msg = unserialize($msg);							# unserialize

		mcrypt_generic_deinit($td);							# clear buffers
		mcrypt_module_close($td);							# close cipher module

		return $msg;										# return original msg
	}

	/** PBKDF2 Implementation (as described in RFC 2898);
	 *
	 *	@param   string  p   password
	 *	@param   string  s   salt
	 *	@param   int     c   iteration count (use 1000 or higher)
	 *	@param   int     kl  derived key length
	 *	@param   string  a   hash algorithm
	 *
	 *	@return  string  derived key
	*/
	public function pbkdf2( $p, $s, $c, $kl, $a = 'sha256' ) {

		$hl = strlen(hash($a, null, true));	# Hash length
		$kb = ceil($kl / $hl);				# Key blocks to compute
		$dk = '';							# Derived key

		# Create key
		for ( $block = 1; $block <= $kb; $block ++ ) {

			# Initial hash for this block
			$ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);

			# Perform block iterations
			for ( $i = 1; $i < $c; $i ++ ) 

				# XOR each iterate
				$ib ^= ($b = hash_hmac($a, $b, $p, true));

			$dk .= $ib; # Append iterated block
		}

		# Return derived key of correct length
		return substr($dk, 0, $kl);
	}
}
class Encrypt{

	var $pass = 'the password';
	var $salt = 'the password salt';
	var $filename = 'filedata-1.txt';
	var $key;
	
	function __construct($filename = null,$pass = 'the password',$salt = 'the password salt') {
		$this->pass = $pass;
		$this->salt = $salt;
		$this->filename = $filename;
	}
	function encrypt($data,$encKeyFilename) {
		//start
		$cryptastic = new cryptastic;
		$this->key = $cryptastic->pbkdf2($pass, $salt, 1000, 32) or die("Failed to generate secret key.");
		//store the key
		$this->storeKey($encKeyFilename);
		//encrypt
		$encrypted	= $cryptastic->encrypt($data, $this->key) or die("Failed to complete encryption.");
		return $encrypted;
	}
	function encryptFile() {
		//validate filename
		if($this->filename == null OR file_exists($this->filename))dir('Invalid filename or File does not exsist!!');
		//start
		$cryptastic = new cryptastic;
		$this->key = $cryptastic->pbkdf2($pass, $salt, 1000, 32) or die("Failed to generate secret key.");
		//store the key
		$this->storeKey($this->filename);
		//encrypt
		$content		= file_get_contents($this->filename);
		$encrypted	= $cryptastic->encrypt($content, $this->key) or die("Failed to complete encryption.");
		$result		= file_put_contents($this->filename.'.enc', $encrypted);
		return $result;
	}
	function storeKey($encKeyFilename) {
		//store the key
		$handle = fopen($encKeyFilename.'.enc.key', 'w');
		fwrite($handle, $this->key) or die("Cannot write to the file");
		fclose($handle);
	}
	function retrieveKey() {
		$this->key = file_get_contents($this->filename.'.key');
		return $this->key;
	} 
	function decryptFile() {
		$cryptastic = new cryptastic;
		$content		= file_get_contents($this->filename.'.enc');
		$decrypted	= $cryptastic->decrypt($content, $this->retrieveKey()) or die("Failed to complete encryption.");
		$result		= file_put_contents($this->filename.'.decry', $decrypted);
		return $result;
	}
}
//$encrypt = new Encrypt('filedata-2.txt.gz');
//$encrypt->encryptFile();
//$encrypt->decryptFile();
	
?>
