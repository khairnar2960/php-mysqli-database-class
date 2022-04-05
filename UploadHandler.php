<?php
/**
 * UploadHandler class
 *
 * @package default
 * @author Harshal Khairnar
 * @license MIT
 * https://harshalkhairnar.com
 * 
 **/
class UploadHandler{
	/**
	 * @property $fileName
	 * @return name of file
	 * 
	 **/
	public $fileName;

	/**
	 * @property $tempPath
	 * @return tmp_path of file
	 * 
	 **/
	public $tempPath;

	/**
	 * @property $isError
	 * @return bool|array
	 * 
	 * (false if no error)
	 * (array of error if error) 
	 * 
	 **/
	public $isError;

	/**
	 * @property $fileSize
	 * @return size of file
	 * 
	 **/
	public $fileSize;

	/**
	 * @property $path
	 * @return absolute path to directory where file to be uploaded
	 * 
	 **/
	protected $path;

	/**
	 * @method getFile
	 * 
	 * this method get file from $_FILES array
	 * using input field name
	 * <input type="file" name="field_name">
	 *
	 * @return Class object itself
	 * @param $field_name : str (<input> field_name)
	 * @param $format : bool (format file size to readable [bytes,KB,MB..])
	 * 
	 **/
	public function getFile($field_name=null, $format=false){
		if ($field_name!==null) {
			$file = $_FILES[$field_name];
			$this->fileName = basename($file['name']);
			$this->tempPath = $file['tmp_name'];
			$this->isError = $this->getFileUploadError($file);
			$this->fileSize = $this->formatFileSize($file['size'], $format);
		}
		return $this;
	}

	/**
	 * @method setPath
	 *
	 * @return Class object itself
	 * @param $path : str (dir path to file upload)
	 * @param $auto_create : bool (creates dir. if not exist)
	 * if set false & dir. not exist.
	 * it throws an exception "File Upload Path Not Set"
	 *  
	 **/
	public function setPath($path=null, $auto_create=true){
		if ($path!==null) {

			// replace "\" to "/"
			$path = str_replace("\\", "/", __DIR__.$path);
			$this->path = $path;

			// check if "/" at the end of path. if not then adds one
			if (strripos($path, "/")!==(strlen($path)-1)) {
				$this->path .= "/";
			}
			if ($auto_create===true && !is_dir($this->path)) {
				if (!mkdir($this->path, 0777, true)) {
					throw new Exception("You dont have permission create");
				};
			}
			return $this;
		}else{
			throw new Exception("File Upload Path Not Set");
		}
	}

	/**
	 * @method upload
	 *
	 * @return boolean (true|false)
	 * @param $rename_to : str (new name to uploading file)
	 * @param $replace : bool (default "true") it replace file if already exists
	 * 
	 * if set false then  Did't upload, if file exist
	 * throws an exeption "file exist with <name>" if file exist with same name
	 *  
	 **/
	public function upload($rename_to=null, $replace=true){
		if ($this->path!==null) {
			if ($rename_to!==null) {
				$fileArray = explode(".", $this->fileName);
				if (count($fileArray)>1) {
					$file = $fileArray[0];
					$ext = end($fileArray);
					$this->fileName = "{$rename_to}.{$ext}";
				}else{
					$this->fileName = "{$rename_to}";
				}
			}
			if ($this->tempPath!==null || $this->tempPath!=='') {
				if ($replace===false && file_exists($this->path.$this->fileName)) {
					// remove exception & uncomment return statement.
					// if wants false if file exist
					throw new Exception("File Exist With Name {$this->fileName}");
					// return false;
				}
				if (move_uploaded_file($this->tempPath, $this->path.$this->fileName)) {
				    return true;
				} else {
				    $this->uploadAttack = true;
				    return false;
				}
			}
		}else{
			throw new Exception("File Upload Path Not Set");
		}
	}

	/**
	 * @method formatFileSize
	 * 
	 **/
	protected function formatFileSize($size=null, $format=false){
		if ($format===true) {
			if ($size!==null) {
				$decimals=2
				$unit = [" Bytes", " KB", " MB", " GB", " TB", " PB"];
				$power = floor((strlen($size) - 1) / 3);
				$sz = round($size / pow(1024, $power), $decimals);
				return $sz.@$unit[$power];
			}
		}else{
			return $size;
		}
	}

	/**
	 * @method getFileUploadError
	 * 
	 **/
	protected function getFileUploadError($file=null){
		if ($file!==null) {
			// error list
			$errors = [
						[
							"error" => "UPLOAD_ERR_OK",
							"message" => "There is no error, the file uploaded with success",
							"msg" => "UPLOAD OK"
						],
						[
							"error" => "UPLOAD_ERR_INI_SIZE",
							"message" => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
							"msg" => "SERVER MAX_FILE_SIZE EXCEEDS"
						],
						[
							"error" => "UPLOAD_ERR_FORM_SIZE",
							"message" => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
							"msg" => "FORM MAX_FILE_SIZE EXCEEDS"
						],
						[
							"error" => "UPLOAD_ERR_PARTIAL",
							"message" => "The uploaded file was only partially uploaded",
							"msg" => "INTERRUPTED"
						],
						[
							"error" => "UPLOAD_ERR_NO_FILE",
							"message" => "No file was uploaded",
							"msg" => "NO FILE SELECTED"
						],
						[
							"error" => "UPLOAD_ERR_NO_TMP_DIR",
							"message" => "Missing a temporary folder",
							"msg" => "TEMP FOLDER NOT FOUND"
						],
						[
							"error" => "UPLOAD_ERR_CANT_WRITE",
							"message" => "Failed to write file to disk",
							"msg" => "WRITE FAILED"
						],
						[
							"error" => "UPLOAD_ERR_EXTENSION",
							"message" => "A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help",
							"msg" => "EXTENSION INTERRUPT"
						]
					];
			if ($file['error']!==0) {
				return $errors[$file["error"]];
			}else{
				return false;
			}
		}
	}

} // END class UploadHandler() 
?>