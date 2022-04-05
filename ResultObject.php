<?php
/**
 * ResultObject class
 *
 * @package supportive class
 * @author Harshal Khairnar
 **/

class ResultObject{
	/**
	 * @method construct
	 * @param $array : array
	 **/
	public function __construct($array){
		$this->getArrayObject($array);
	}
	/**
	 * @method getArrayObject
	 * @param $array : array (array to conver to object)
	 * @return class object
	 **/
	private function getArrayObject($array){
		foreach ($array as $key => $value) {
			$this->{$key} = [];
			$this->{$key} = $array[$key];
		}
		return $this;
	}
}