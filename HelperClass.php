<?php
/**
 * HelperClass
 */
class HelperClass{
	/* <------------------ { Helper Methods } ------------------> */

	/**
	 * @param $location : string (location to redirect)
	 * @param $replace : optional replace parameter indicates whether
	 * the header should replace a previous similar header,
	 * or add a second header of the same type.
	 * By default it will replace, but if you pass in false as the second argument
	 * you can force multiple headers of the same type.
	 **/
	public function headerLocation($location=null,$replace=true){
		return header("Location: {$location}", $replace);
	}

	public function addUserRole(){
		$this->stmt = "CREATE TABLE `user_role` (
						 `id` int NOT NULL AUTO_INCREMENT,
						 `role` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
						 PRIMARY KEY (`id`)
						) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
		return $this;
	}
}