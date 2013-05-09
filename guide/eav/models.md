# Creating your model

To create a model for the table `products` in your database simply hang on to the naming conventions of kohana and create the file `application/classes/models/product.php`:

	class Model_Product extends EAV {
		...
	}

Leaving the model empty should in most cases be enough for both ORM and EAV to work. EAV would in this case guess the following:

	$_eav_table_info = array(
			'attributes' => array(
					'name'    => 'product_attributes',
					'columns' => array(
							'id'      => 'id',
							'item_id' => 'product_id',
							'type'    => 'type',
							'name'    => 'name',
					),
			),
			'values' => array(
					'name'    => 'product_attribute_values',
					'columns' => array(
							'attribute_id' => 'product_attribute_id',
							'value'        => 'value',
					),
			),
	);
	
## Adding attributes to the model

EAV comes with a handy function to add attributes, simply use [`EAV::attr()`](../../guide-api/EAV#attr)

	$model = EAV::factory('Product');
	
	$model->attr('example_attribute', 'example_value');
	$model->attr('another_attribute', 'another_value');
	$model->attr('some_attribute', 'some_value');
	
	// This will override the 'example_attribute' value
	$model->attr('example_attribute', 'some_other_value');
	
	// This will save the model, and all attributes to the database
	$model->save();

## A real world example

The form below should provide you with a form on which you can add 100 attributes to a single object

	<?php echo Form::open(); ?>
	
	<?php echo Form::input('name'); ?>
	<?php echo Form::textarea('description'); ?>
	
	<?php /* A hundred attributes! */ ?>
	<?php for($i = 0; $i < 100; $i++); ?>
		<?php echo Form::input('attr_name[]'); ?>
		<?php echo Form::input('attr_value[]'); ?>
	<?php endfor; ?>
	
	<?php echo Form::submit(); ?>
	<?php echo Form::close(); ?>
	
The PHP code:

	$product = EAV::factory('Product');
	
	$product->values($this->request->post(), array(
			'name',
			'description',
	));
			
	$names = $this->request->post('attr_name');
	$values = $this->request->post('attr_value');
	$attributes = array();
	
	// Put them in an array in the form of $attr_name => $attr_value
	for($i = 0; $i < count($names); $i++)
	{
		$attributes[$names[$i]] = $values[$i];
	}
	
	foreach($attributes as $key => $value)
	{
		$product->attr($key, $value);
	}
	
	try 
	{
		$product->save();
	}
	catch(ORM_Validation_Exception $e)
	{
		$errors = $e->errors('models');
	}

Note that it would be wise to set max_execution_time really high if you don't have a limit on the amount of attributes