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
	 * Flags the object as modified or not
	 * @var bool modified
	 */
	private $_modified = FALSE;
	
	/**
	 * Creates a new EAV_Attribute
	 * 
	 * @param string $name
	 * @param array  $row of properties
	 */
	public function __construct($row, $master)
	{
		$this->_master = $master;
		$this->_object = $row;
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
		
		// Set master ID
		$this->_object['item_id'] = $this->_master->pk();
		
		// Object is modified and not new, should be updated
		if($this->_modified AND $this->id)
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
		else if( ! $this->id) // Object has no id therefore it is new
		{
			$this->_query_type = DATABASE::UPDATE;
			
			$master_columns = array(
					$this->_master->attributes_table_columns('item_id'),
					$this->_master->attributes_table_columns('type'),
					$this->_master->attributes_table_columns('name'),
			);
			$attribute_values = array(
					$this->item_id,
					$this->type,
					$this->name,
			);
			
			$result = DB::insert($this->_master->attributes_table_name(), $master_columns)
					->values($attribute_values)
					->execute();
			
			$this->id = $result[0];
			
			$values_values = array(
					'attribute_id' => $this->id,
					'value'        => $this->value,
			);
			
			DB::insert(
					$this->_master->values_table_name(), 
					$this->_master->values_table_columns()
				)->values($values_values)
					->execute();
		}
		else 
		{
			// Object is not modified and not new .. do nothing
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
		
		return array_values($this->{$table});
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
		
		// Flag the object as modified
		$this->_modified = TRUE;
	}
}
