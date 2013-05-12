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
 * @package Kohana EAV module
 * @author Dennis Ruhe
 * @copyright (c) 2013-2013 Dennis Ruhe
 * @license see LICENSE.md
 */
class EAV extends ORM {
	
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
		$this->_object_name = strtolower(str_replace('Model_', '', get_class($this)));
		
		// See if the attributes table name is set
		if( ! Arr::get($this->_eav_table_info['attributes'], 'name'))
		{
			$this->_eav_table_info['attributes']['name'] =  $this->_object_name .'_attributes';
		}
		
		// See if the values table name is set
		if( ! Arr::get($this->_eav_table_info['values'], 'name'))
		{
			$this->_eav_table_info['values']['name'] = $this->_object_name .'_attribute_values';
		}
		
		// See if the attribute table columns are filled in
		if(count($this->attributes_table_columns()) == 0)
		{
			Arr::set_path($this->_eav_table_info, 'attributes.columns', array(
					'id'      => 'id',
					'item_id' => $this->_object_name .'_id',
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

		parent::__construct($id);
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
	 * Gets or sets a (new) attribute
	 * 
	 * @param unknown $column
	 * @param string $value
	 * @return Ambigous <mixed, array>
	 */
	public function attr($column = NULL, $value = NULL)
	{
		// Check if the attributes are loaded and primary key is set
		if( ! $this->_attributes_loaded AND $this->pk())
		{
			$this->_load_attributes();
		}
		
		// If no column is specified, return all attributes as objects
		if( ! $column)
		{
			return $this->_eav_object;
		}
		
		if($value)
		{
			// Get the attribute ID
			$this->_eav_object[$column] = new EAV_Attribute(array(
					'item_id' => $this->pk(),
					'name'    => $column,
					'type'    => gettype($value),
					'value'   => $value,
			), $this);
		}
		else
		{
			// Get the attribute value
			$attribute = Arr::get($this->_eav_object, $column);
			return ($attribute) ? $attribute : NULL;
		}
	}
	
	/**
	 * Delete specified attribute or delete all
	 * 
	 * @param string $column  leave empty to delete all
	 * @return Kohana_EAV
	 */
	public function delete_attr($column = NULL)
	{
		$db = DB::delete($this->attributes_table_name());
		
		// No column specified, delete all
		if( ! $column)
		{
			$db->where($this->attributes_table_columns('item_id'), '=', $this->pk());
		}
		else 
		{
			$db->where($this->attributes_table_columns('id'), '=', ($this->attr($column)) ? $this->attr($column)->id : '0');
		}
		
		$db->execute();
		
		return $this;
	}
	
	/**
	 * Finds and loads a single database row into the object with attributes loaded
	 * 
	 * @chaineable
	 * @see Kohana_ORM::find()
	 * @return EAV object
	 */
	public function find()
	{
		$this->_prepare_eav_query();

		return parent::find();
	}
	
	/**
	 * Finds multiple database rows and returns an iterator of the rows found
	 * 
	 * @chaineable
	 * @see Kohana_ORM::find_all()
	 * @return array EAV objects
	 */
	public function find_all()
	{
		$this->_prepare_eav_query();
		
		return parent::find_all();
	}
	
	/**
	 * Loads the attributes from the model
	 */
	protected function _load_attributes()
	{
		$this->_attributes_loaded = TRUE;
		
		$attributes = Arr::get($this->_object, 'meta_data');
		
		// Check if field is set
		if( ! $attributes OR strlen($attributes) == 0)
			return $this;
		
		$attributes = explode(';', Arr::get($this->_object, 'meta_data'));
		unset($this->_object['meta_data']); // Clean up the mess
		
		foreach($attributes as $attribute)
		{
			$attribute = explode(',', $attribute);
			
			$attribute = new EAV_Attribute(array(
					'id'           => Arr::get($attribute, 0),
					'name'         => Arr::get($attribute, 1),
					'type'         => Arr::get($attribute, 2),
					'value'        => Arr::get($attribute, 3),
					'attribute_id' => Arr::get($attribute, 0),
			), $this);
			
			$this->_eav_object[$attribute->name] = $attribute;
		}
	}
	
	/**
	 * Joins the three EAV tables together
	 * 
	 * @return Kohana_EAV
	 */
	private function _prepare_eav_query()
	{
		$this->join(array($this->attributes_table_name(), 'attr_table'), 'LEFT')->on('attr_table.'. $this->attributes_table_columns('item_id'), '=', $this->_object_name .'.'. $this->_primary_key)
			->join(array($this->values_table_name(), 'val_table'), 'LEFT')->on('val_table.'. $this->values_table_columns('attribute_id'), '=', 'attr_table.'. $this->attributes_table_columns('id'));
		
		// Add group by to prevent returning one row of NULL values
		$this->group_by($this->_object_name .'.id');
		
		return $this;
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
						array('attr_t.'. $this->attributes_table_columns('item_id'), 'item_id'),
						array('val_t.'. $this->values_table_columns('value'), 'value')
				)->from(array($this->values_table_name(), 'val_t'))
					->join(array($this->attributes_table_name(), 'attr_t'))->on('attr_t.'. $this->attributes_table_columns('id'), '=', 'val_t.'. $this->values_table_columns('attribute_id'))
					->where('attr_t.'. $this->attributes_table_columns('name'), '=', $attribute)
					->having('value', $op, $value),
				md5($attribute),
		), 'INNER')->on(md5($attribute) .'.item_id', '=', $this->_object_name .'.'. $this->_primary_key);
							
		
		// Return self for chaineability
		return $this;
	}
	
	/**
	 * Appends meta_data to the SELECT clause
	 * 
	 * (non-PHPdoc)
	 * @see Kohana_ORM::_build_select()
	 */
	protected function _build_select()
	{
		$columns = parent::_build_select();
		
		$separator = ",";
		$expresion = DB::expr('GROUP_CONCAT(
				`attr_table`.`'. $this->attributes_table_columns('id') .'`, "'. $separator .'", 
				`attr_table`.`'. $this->attributes_table_columns('name') .'`, "'. $separator .'",
				`attr_table`.`'. $this->attributes_table_columns('type') .'`, "'. $separator .'",
				`val_table`.`'. $this->values_table_columns('value') .'` SEPARATOR ";")'
		);
		
		$columns[] = array($expresion, 'meta_data');
		
		return $columns;
	}
}