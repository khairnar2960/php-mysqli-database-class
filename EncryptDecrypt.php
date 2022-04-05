<?php
/**
 * EncryptDecrypt class
 *
 * @package default
 * @author Harshal Khairnar
 * for data encryption and decryption
 * @uses "AES-256-CBC" encryption method
 * @uses openssl_encrypt to encrypt data
 * @uses openssl_decrypt to decrypt data
 * ======================================================
 * see example for referance
 * ======================================================
 * include('EncryptDecrypt.php');
 * $en = new EncryptDecrypt();
 * $en->setKey("harshal");
 * $en->setIV("khairnar");
 * // $en->setDepth(5); //optional default is 1
 * echo $en->encrypt("hello world");
 * // Output => djhuZ1lXcnhXcFVqbVZKZUxoSDc0Zz09
 * echo "<br>";
 * echo $en->decrypt("djhuZ1lXcnhXcFVqbVZKZUxoSDc0Zz09");
 * // Output => hello world
 **/

class EncryptDecrypt{
	private $key;
	private $iv;
	private $method = "AES-256-CBC";
	private $depth = 1;
	private $str;

	/**
	 * @method setKey
	 * sets an secret key for encryption method
	 * @param $key : str (secret key)
	 * @return class object
	 **/
	public function setKey($key=null){
		if ($key!==null && $key!=="") {
			$this->key = hash('sha256', $key);
			return $this;
		}
	}

	/**
	 * @method setIV
	 * @param $iv : str (secret iv)
	 * sets an secret iv
	 * encrypt method AES-256-CBC expects 16 bytes
	 * so we need to cut off hash to 16 characters
	 * @return class object
	 **/
	public function setIV($iv=null){
		if ($iv!==null && $iv!=="") {
			$this->iv = substr(hash('sha256', $iv), 0, 16);
			return $this;
		}
	}

	/**
	 * @method setDepth
	 * @param $depth : int (recursion depth)
	 * default : 1 (int)
	 * maximum : 5 (int)
	 * @return class object
	 * it sets recursion depth for encryption & decryption
	 **/
	public function setDepth($depth=1){
		if ($depth!==null && $depth!=="") {
			if ($depth<=5) {
				$this->depth = $depth;
				return $this;
			}else{
				throw new \Exception("Maximum recursion deepth exceded {$depth}>5");
			}
		}
	}

	/**
	 * @method encrypt
	 * @param $str : str (data to encrypt)
	 * it encrypts data using given secret key, secret iv and encryption method
	 * @return encrypted data
	 **/
	public function encrypt($str=null){
		if ($str!==null && $str!=="") {
			$this->str = $str;
			$i = 1;
			while ($i<=$this->depth) {
				$this->str = base64_encode(openssl_encrypt($this->str, $this->method, $this->key, 0, $this->iv));
				$i++;
			}
			return $this->str;
		}
	}

	/**
	 * @method decrypt
	 * @param $str : str (encrypted data to decrypt)
	 * it decrypts data using given secret key, secret iv and encryption method
	 * @return encrypted data
	 **/
	public function decrypt($str=null){
		if ($str!==null && $str!=="") {
			$this->str = $str;
			$i = 1;
			while ($i<=$this->depth) {
				$this->str = openssl_decrypt(base64_decode($this->str), $this->method, $this->key, 0, $this->iv);
				$i++;
			}
			return $this->str;
		}
	}
}
?>