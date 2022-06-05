<?php

/**
 * FileSystem class
 *
 * @package default
 * @author Harshal Khairnar
 **/
class FileSystem{

	function __construct(){
		date_default_timezone_set("Asia/Kolkata");
	}

	function humanize($bytes, $decimals=2) {
		$unit = [" Bytes", " KB", " MB", " GB", " TB", " PB"];
		$power = floor((strlen($bytes) - 1) / 3);
		$size = round($bytes / pow(1024, $power), $decimals);
		return $size.@$unit[$power];
	}

	/**
	 * Get the directory size
	 * @param  string $directory
	 * @return integer
	 */
	function dirSize($directory) {
		$size = 0;
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
			$size+=$file->getSize();
		}
		return $size-4096;
	}

	function fileCount($dir){
		$files = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
		return iterator_count($files);
	}

	function scanDirs($dir=null){
		$list = ["dirs"=> [], "files" => []];
		if ($dir!==null && $dir!=="") {
			$scanDir = array_diff(scandir($dir), array('.', '..'));
			asort($scanDir);
			foreach ($scanDir as $file) {
				$path_file = $dir.$file;
				if (is_dir($path_file)) {
					array_push($list["dirs"], [
						"name" => $file,
						"path" => $path_file,
						"created" => filectime($path_file),
						"modified" => filemtime($path_file),
						"files" => $this->fileCount($path_file),
						"size" => $this->humanize($this->dirSize($path_file)),
					]);
				}else{
					$fileinfo = pathinfo($path_file);
					array_push($list["files"], [
						"path" => $path_file,
						"dir" => $fileinfo['dirname'],
						"file" => $fileinfo['basename'],
						"name" => $fileinfo['filename'],
						"created" => filectime($path_file),
						"modified" => filemtime($path_file),
						"mime" => mime_content_type($path_file),
						"ext" => $fileinfo['extension'],
						"size" => $this->humanize(filesize($path_file)),
					]);
				}
			}
		}
		return $list;
	}
} // END class FileSystem

?>