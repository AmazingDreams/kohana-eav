<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_EAV_Attribute {
	
	public $name = NULL;
	
	private $_object = array();
	
	public function __construct($name, $row)
	{
		$this->name = $name;
		
		$this->values($row);
	}
	
	public function values($array)
	{	
		foreach($array as $key => $value)
		{
			$this->{$key} = $value;
		}
		
		return $this;
	}
	
	public function __get($column)
	{
		return Arr::get($this->_object, $column);
	}
	
	public function __set($column, $value)
	{
		$this->_object[$column] = $value;
	}
}
