<?php
// pass ?file=file_name to download file
if (isset($_GET['file'])) {
	$path = realpath(__DIR__."/../views/".$_GET['file']);
	if (file_exists($path)) {
		$file = file_get_contents($path);
		header('Content-Type: ' . mime_content_type($path));
		header('Content-Length: ' . filesize($path));
		// force file to download
		header( 'Content-Disposition: attachment; filename="'.pathinfo($path)['basename'].'"' );
		echo $file;
	}else{
		exit();
	}
}