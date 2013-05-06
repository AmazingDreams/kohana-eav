<?php defined('SYSPATH') OR die('No direct script access.');

/** 
 * @package Kohana/eav
 * @author Dennis Ruhe
 * @copyright (c) 2013-2013 Dennis Ruhe
 * @license see LICENSE.md
 */
class Kohana_EAV_Attribute {
	
	const ATTRIBUTES = '_attribute_part';
	const VALUES     = '_values_part';
	
	/**
	 * Holds all the other properties of this attribute
	 * @var array
	 */
	private $_object = array();
	
	/**
	 * Holds the master object
	 * @var EAV
	 */
	private $_master = NULL;
	
	/**
	 * Holds the values related to the attribute part
	 * @var unknown
	 */
	private $_attribute_part = array();
	
	/**
	 * Holds the values related to the value part
	 * @var unknown
	 */
	private $_values_part = array();
	
	/**
	 * Creates a new EAV_Attribute
	 * 
	 * @param string $name
	 * @param array  $row of properties
	 */
	public function __construct($row, $master)
	{
		$this->_master = $master;
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
	 * Reorders all properties of this object into the corresponding value/attribute arrays
	 */
	private function _reorder_object()
	{
		foreach($this->_object as $key => $value)
		{
			if(in_array($key, $this->_master->attributes_table_columns()))
			{
				$this->_attribute_part[$key] = $value;
			}
				
			if(in_array($key, $this->_master->values_table_columns()))
			{
				$this->_values_part[$key] = $value;
			}
		}
	}
	
	/**
	 * Saves this attribute
	 * 
	 * @return self
	 */
	public function save()
	{
		// Make sure everything is in the right place
		$this->_reorder_object();
		
		if($this->id)
		{
			$array = array();

			foreach($this->_attribute_part as $key => $value)
			{
				if($key != $this->_master->attributes_table_columns('id'))
				{
					$array[$key] = $value;
				}
			}
			
			DB::update($this->_master->attributes_table_name())
					->set($array)
					->where($this->_master->attributes_table_columns('id'), '=', $this->id)
					->execute();

			DB::update($this->_master->values_table_name())
					->set($this->_values_part)
					->where($this->_master->values_table_columns('attribute_id'), '=', $this->id)
					->execute();
		}
		else
		{
			$this->_query_type = DATABASE::UPDATE;
			
			$this->id = DB::insert($this->_master->attributes_table_name(), $this->_master->attribute_table_columns())
					->values($this->get_values(self::ATTRIBUTES))
					->execute();
			
			DB::insert(
					$this->_master->values_table_name(), 
					Arr::merge(array($this->_master->values_table_columns('attribute_id')), $this->_master->values_table_columns())
				)->values(Arr::merge(array($this->id), $this->get_values(self::VALUES)))
					->execute();
		}
		
		return $this;
	}
	
	/**
	 * Get the values of this objects properties only
	 * 
	 * @param unknown $table
	 * @throws Kohana_Exception
	 * @return multitype:NULL
	 */
	public function get_values($table)
	{
		if($table != self::ATTRIBUTES AND $table != self::VALUES)
			throw new Kohana_Exception('No valid value specified');
		
		return array_values($this->{table});
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
