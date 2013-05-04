<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_EAV extends ORM {
	
	protected $_attributes_table_name = NULL;
	
	protected $_values_table_name = NULL;
	
	protected $_attributes_table_columns = array();
	
	protected $_values_table_columns = array();
	
	protected $_eav_object = array();
	
	protected $_attributes_loaded = FALSE;
	
	public function __construct($id)
	{
		parent::__construct($id);
		
		if( ! $this->_attributes_table_name)
		{
			$this->_attributes_table_name = str_replace('Model_', '', get_class($this)) .'_attributes';
		}
		
		if( ! $this->_values_table_name)
		{
			$this->_values_table_name = str_replace('Model_', '', get_class($this)) .'_attribute_values';
		}
		
		if(count($this->_attributes_table_columns) == 0)
		{
			$this->_attributes_table_columns = array(
					'id'      => 'id',
					'item_id' => Inflector::singular($this->_table_name) .'_id',
					'type'    => 'type',
					'name'    => 'name',
			);
		}
		
		if(count($this->_values_table_columns) == 0)
		{
			$this->_values_table_columns = array(
					'attribute_id'  => Inflector::singular($this->_attributes_table_name) .'_id',
					'value'         => 'value',
			);
		}
	}
	
	protected function _get_attributes()
	{
		$result = DB::select(
				array($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'id'), 'id'),
				array($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'name'), 'name'),
				array($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'type'), 'type'),
				array($this->_values_table_name .'.'. Arr::get($this->_values_table_columns, 'value'), 'value')
		)->from($this->_values_table_name)
		 ->join($this->_attributes_table_name)->on($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'id'), '=', $this->_values_table_name .'.'. Arr::get($this->_values_table_columns, 'attribute_id'))
		 ->where(Arr::get($this->_attributes_table_columns, 'item_id'), '=', $this->id)
		 ->execute()
		 ->as_object();
		
		foreach($result as $property)
		{
			settype($property->value, $property->type);
			$this->{$property->name} = $property->value;
		}
		
		$this->_attributes_loaded = TRUE;
	}
	
	public function __set($column, $value)
	{
		if(array_key_exists($column, $this->_table_columns))
		{
			parent::__set($column, $value);
		}
		else 
		{
			// Check if the attributes are loaded
			if( ! $this->_attributes_loaded)
			{
				$this->_get_attributes();
			}
			
			// Get the attribute ID
			$id = Arr::get($this->_eav_object, $column);
			
			$this->_eav_object[$column] = array(
					'id'    => $id,
					'type'  => typeof($value),
					'value' => $value,
			);
		}
	}
	
	public function __get($column)
	{
		if(array_key_exists($column, $this->_table_columns))
		{
			parent::__get($column, $value);
		}
		else
		{
			// Check if the attributes are loaded
			if( ! $this->_attributes_loaded)
			{
				$this->_get_attributes();
			}
				
			// Get the attribute value
			return Arr::get(Arr::get($this->_eav_object, $column), 'value');
		}
	}
}