<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Entity–attribute–value model (EAV) is a data model to describe entities where the number of attributes (properties, parameters) that can be used to describe them is potentially vast, 
 * but the number that will actually apply to a given entity is relatively modest. In mathematics, this model is known as a sparse matrix. 
 * EAV is also known as object–attribute–value model, vertical database model and open schema.
 * 
 * There are certain cases where an EAV schematic is an optimal approach to data modelling for a problem domain. 
 * However, in many cases where data can be modelled in statically relational terms an EAV based approach is an anti-pattern which can lead to longer development times, 
 * poor use of database resources and more complex queries when compared to a relationally-modelled data schema.
 * 
 * [ref-eav] http://en.wikipedia.org/wiki/Entity-attribute-value_model
 * 
 * @package Kohana/eav
 * @author Dennis Ruhe
 * @copyright (c) 2013-2013 Dennis Ruhe
 * @license see LICENSE.md
 */
class Kohana_EAV extends ORM {
	
	/**
	 * Stores the table name for the attributes table
	 * @var string    table name
	 */
	protected $_attributes_table_name = NULL;
	
	/**
	 * Stores the table name for the values table
	 * @var string    table name
	 */
	protected $_values_table_name = NULL;
	
	/**
	 * Stores the column information for the attributes table
	 * @var array     column names
	 */
	protected $_attributes_table_columns = array();
	
	/**
	 * Stores the column information for the values table
	 * @var array    column names
	 */
	protected $_values_table_columns = array();
	
	/**
	 * Stores the attribute values and information
	 * @var array    attributes
	 */
	protected $_eav_object = array();
	
	/**
	 * @var bool
	 */
	protected $_attributes_loaded = FALSE;
	
	/**
	 * An alias method for ORM::factory, you can use both methods
	 * 
	 * @param string  $model
	 * @param mixed   $id
	 * @return EAV
	 */
	public static function factory($model, $id = NULL)
	{
		return ORM::factory($model, $id);
	}
	
	/**
	 * Constructs a new model and leaves the rest to ORM
	 * 
	 * @param mixed $id
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);
		
		// See if the attributes table name is set
		if( ! $this->_attributes_table_name)
		{
			$this->_attributes_table_name = strtolower(str_replace('Model_', '', get_class($this)) .'_attributes');
		}
		
		// See if the values table name is set
		if( ! $this->_values_table_name)
		{
			$this->_values_table_name = strtolower(str_replace('Model_', '', get_class($this)) .'_attribute_values');
		}
		
		// See if the attribute table columns are filled in
		if(count($this->_attributes_table_columns) == 0)
		{
			$this->_attributes_table_columns = array(
					'id'      => 'id',
					'item_id' => Inflector::singular($this->_table_name) .'_id',
					'type'    => 'type',
					'name'    => 'name',
			);
		}
		
		// See if the values table columns are filled in
		if(count($this->_values_table_columns) == 0)
		{
			$this->_values_table_columns = array(
					'attribute_id'  => Inflector::singular($this->_attributes_table_name) .'_id',
					'value'         => 'value',
			);
		}
	}
	
	/**
	 * Gets existing attribute info from the tables
	 */
	protected function _get_attributes()
	{
		// Flag the attributes as loaded
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
			$this->_eav_object[$property->name] = array(
					'id'    => $property->id,
					'type'  => $property->type,
					'value' => $property->value,
			);
		}
	}
	
	/**
	 * Gets or sets a (new) attribute
	 * 
	 * @param unknown $column
	 * @param string $value
	 * @return Ambigous <mixed, array>
	 */
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
	
	/**
	 * Alias for and_where_attr
	 *
	 * @param string  $attribute
	 * @param string  $op
	 * @param mixed   $value
	 * @return Kohana_EAV
	 */
	public function where_attr($attribute, $op, $value)
	{
		return $this->and_where_attr($attribute, $op, $value);
	}
	
	/**
	 * Simulates an 'AND WHERE' clause
	 * 
	 * @param string  $attribute
	 * @param string  $op
	 * @param mixed   $value
	 * @return Kohana_EAV
	 */
	public function and_where_attr($attribute, $op, $value)
	{
		$this->join(array(
				DB::select(
						array('attr_t.'. Arr::get($this->_attributes_table_columns, 'item_id'), 'item_id'),
						array('val_t.'. Arr::get($this->_values_table_columns, 'value'), 'value')
				)->from(array($this->_values_table_name, 'val_t'))
					->join(array($this->_attributes_table_name, 'attr_t'))->on('attr_t.'. Arr::get($this->_attributes_table_columns, 'id'), '=', 'val_t.'. Arr::get($this->_values_table_columns, 'attribute_id'))
					->where('attr_t.'. Arr::get($this->_attributes_table_columns, 'name'), '=', $attribute)
					->having('value', $op, $value),
				md5($attribute),
		), 'INNER')->on(md5($attribute) .'.item_id', '=', Inflector::singular($this->_table_name) .'.'. $this->_primary_key);
							
		
		// Return self for chaineability
		return $this;
	}
}