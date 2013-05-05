<?php defined('SYSPATH') OR die('No direct script access.');

/** 
 * @package Kohana/eav
 * @author Dennis Ruhe
 * @copyright (c) 2013-2013 Dennis Ruhe
 * @license see LICENSE.md
 */
class Kohana_EAV_Attribute {
	
	/**
	 * The name of the attribute 
	 * @var string
	 */	
	public $name = NULL;
	
	/**
	 * Holds all the other properties of this attribute
	 * @var array
	 */
	private $_object = array();
	
	/**
	 * Creates a new EAV_Attribute
	 * 
	 * @param string $name
	 * @param array  $row of properties
	 */
	public function __construct($name, $row)
	{
		$this->name = $name;
		
		$this->values($row);
	}
	
	/**
	 * Sets the values of this attribute
	 * 
	 * @param array $array of properties
	 * @return Kohana_EAV_Attribute
	 */
	public function values($array)
	{	
		foreach($array as $key => $value)
		{
			$this->{$key} = $value;
		}
		
		// Return self for chaining
		return $this;
	}
	
	/**
	 * Get a property
	 * 
	 * @param string $column
	 * @return Ambigous <mixed, array>
	 */
	public function __get($column)
	{
		return Arr::get($this->_object, $column);
	}
	
	/**
	 * Set a property
	 * 
	 * @param string $column
	 * @param unknown $value
	 */
	public function __set($column, $value)
	{
		$this->_object[$column] = $value;
	}
}
