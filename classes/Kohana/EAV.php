<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_EAV extends ORM {
	
	protected $_attributes_table_name = NULL;
	
	protected $_values_table_name = NULL;
	
	protected $_attributes_table_columns = array();
	
	protected $_values_table_columns = array();
	
	protected $_eav_object = array();
	
	protected $_attributes_loaded = FALSE;
	
	public static function factory($model, $id = NULL)
	{
		// Set class name
		$model = 'Model_'.$model;

		return new $model($id);
	}
	
	public function __construct($id)
	{
		parent::__construct($id);
		
		if( ! $this->_attributes_table_name)
		{
			$this->_attributes_table_name = strtolower(str_replace('Model_', '', get_class($this)) .'_attributes');
		}
		
		if( ! $this->_values_table_name)
		{
			$this->_values_table_name = strtolower(str_replace('Model_', '', get_class($this)) .'_attribute_values');
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
		$this->_attributes_loaded = TRUE;
		
		$result = DB::select(
				array($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'id'), 'id'),
				array($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'name'), 'name'),
				array($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'type'), 'type'),
				array($this->_values_table_name .'.'. Arr::get($this->_values_table_columns, 'value'), 'value')
		)->from($this->_values_table_name)
		 ->join($this->_attributes_table_name)->on($this->_attributes_table_name .'.'. Arr::get($this->_attributes_table_columns, 'id'), '=', $this->_values_table_name .'.'. Arr::get($this->_values_table_columns, 'attribute_id'))
		 ->where(Arr::get($this->_attributes_table_columns, 'item_id'), '=', $this->id)
		 ->as_object()
		 ->execute();
		
		foreach($result as $property)
		{
			settype($property->value, $property->type);
			$this->attr($property->name, $property->value);
		}
	}
	
	public function attr($column, $value = NULL)
	{
		// Check if the attributes are loaded
		if( ! $this->_attributes_loaded)
		{
			$this->_get_attributes();
		}
		
		if($value)
		{
			// Get the attribute ID
			$id = Arr::get($this->_eav_object, $column);
			
			$this->_eav_object[$column] = array(
					'id'    => $id,
					'type'  => gettype($value),
					'value' => $value,
			);
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