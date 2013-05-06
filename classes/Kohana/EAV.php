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
	 * Stores the information about the eav_tables
	 * 
	 * @var unknown
	 */
	protected $_eav_table_info = array(
			'attributes' => array(),
			'values'     => array(),
	);
	
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
		if( ! Arr::get($this->_eav_table_info['attributes'], 'name'))
		{
			$this->_eav_table_info['attributes']['name'] = strtolower(str_replace('Model_', '', get_class($this)) .'_attributes');
		}
		
		// See if the values table name is set
		if( ! Arr::get($this->_eav_table_info['values'], 'name'))
		{
			$this->_eav_table_info['values']['name'] = strtolower(str_replace('Model_', '', get_class($this)) .'_attribute_values');
		}
		
		// See if the attribute table columns are filled in
		if(count($this->attributes_table_columns()) == 0)
		{
			Arr::set_path($this->_eav_table_info, 'attributes.columns', array(
					'id'      => 'id',
					'item_id' => Inflector::singular($this->table_name()) .'_id',
					'type'    => 'type',
					'name'    => 'name',
			));
		}
		
		// See if the values table columns are filled in
		if(count($this->values_table_columns()) == 0)
		{
			Arr::set_path($this->_eav_table_info, 'values.columns', array(
					'attribute_id'  => Inflector::singular($this->attributes_table_name()) .'_id',
					'value'         => 'value',
			));
		}
	}
	
	/**
	 * Get all information regarding the attributes table
	 * 
	 * @return Ambigous <mixed, array>
	 */
	public function attributes_table()
	{
		return Arr::get($this->_eav_table_info, 'attributes', array());
	}
	
	/**
	 * Get only the attributes table name
	 * 
	 * @return Ambigous <mixed, array>
	 */
	public function attributes_table_name()
	{
		return Arr::get($this->attributes_table(), 'name');
	}
	
	/**
	 * Get all columns, or one column name only
	 * 
	 * @param string $column
	 * @return Ambigous <mixed, array>
	 */
	public function attributes_table_columns($column = NULL)
	{
		$columns = Arr::get($this->attributes_table(), 'columns', array());
		
		if($column)
		{
			return Arr::get($columns, $column);
		}
		
		return $columns;
	}
	
	/**
	 * Get all information regarding the values table
	 * 
	 * @return Ambigous <mixed, array>
	 */
	public function values_table()
	{
		return Arr::get($this->_eav_table_info, 'values', array());
	}
	
	/**
	 * Get the name of the values table
	 * 
	 * @return Ambigous <mixed, array>
	 */
	public function values_table_name()
	{
		return Arr::get($this->_eav_table_info['values'], 'name');
	}
	
	/**
	 * Get all columns, or one column name only
	 * 
	 * @param string $column
	 * @return Ambigous <mixed, array>
	 */
	public function values_table_columns($column = NULL)
	{
		$columns = Arr::get($this->values_table(), 'columns', array());
		
		if($column)
		{
			return Arr::get($columns, $column);
		}
		
		return $columns;
	}
	
	/**
	 * Gets existing attribute info from the tables
	 */
	protected function _get_attributes()
	{
		// Flag the attributes as loaded
		$this->_attributes_loaded = TRUE;
		
		$result = DB::select(
				array('attr_table.'. $this->attributes_table_columns('id'), 'id'),
				array('attr_table.'. $this->attributes_table_columns('name'), 'name'),
				array('attr_table.'. $this->attributes_table_columns('type'), 'type'),
				array('val_table.'.  $this->values_table_columns('value'), 'value')
		)->from(array($this->values_table_name(), 'val_table'))
		 ->join(array($this->attributes_table_name(), 'attr_table'))->on('attr_table.'. $this->attributes_table_columns('id'), '=', 'val_table.'. $this->values_table_columns('attribute_id'))
		 ->where('attr_table.'. $this->attributes_table_columns('item_id'), '=', $this->id)
		 ->as_object()
		 ->execute();
		
		foreach($result as $property)
		{
			$attribute = new EAV_Attribute(array(
					'id'           => $property->id,
					'name'         => $property->name,
					'type'         => $property->type,
					'value'        => $property->value,
					'attribute_id' => $property->id,
			), $this);
			
			$this->_eav_object[$property->name] = $attribute;
		}
	}
	
	/**
	 * Gets or sets a (new) attribute
	 * 
	 * @param unknown $column
	 * @param string $value
	 * @return Ambigous <mixed, array>
	 */
	public function attr($column = NULL, $value = NULL)
	{
		// Check if the attributes are loaded
		if( ! $this->_attributes_loaded)
		{
			$this->_get_attributes();
		}
		
		// If no column is specified, return all attributes as objects
		if( ! $column)
		{
			return $this->_eav_object;
		}
		
		if($value)
		{
			// Get the attribute ID
			$id = Arr::get($this->_eav_object, $column);
			
			$this->_eav_object[$column]->values(array(
					'type'    => gettype($value),
					'value'   => $value,
			));
		}
		else
		{
			// Check if the attributes are loaded
			if( ! $this->_attributes_loaded)
			{
				$this->_get_attributes();
			}
		
			// Get the attribute value
			$attribute = Arr::get($this->_eav_object, $column);
			return ($attribute) ? $attribute->{$column} : NULL;
		}
	}
	
	/**
	 * Saves this object and all child attributes
	 * 
	 * @see Kohana_ORM::save()
	 * @param $validation
	 * @return self
	 */
	public function save(Validation $validation = NULL)
	{
		parent::save();
		
		foreach($this->_eav_object as $attribute)
		{
			$attribute->save();
		}
		
		return $this;
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