# Using EAV models
## Loading the model
EAV comes with a factory function, [`EAV::factory`](../../guide-api/EAV#factory), which at this point is simply an alias to [`ORM::factory`](../../guide-api/ORM#factory)
Though I would recommend using `EAV::factory`, it might become its own entity in the future.

	$product = EAV::factory('Product');
	// Or
	$product = ORM::factory('Product');
	// Or simply
	$product = new Model_Product;

## Adding attributes to the model
EAV comes with a handy function to manipulate attributes, simply use [`EAV::attr()`](../../guide-api/EAV#attr)

	// Create a new instance of the model
	$model = EAV::factory('Product');
	
	// Add some attributes
	$model->attr('example_attribute', 'example_value');
	$model->attr('another_attribute', 'another_value');
	$model->attr('some_attribute', 'some_value');
	
	// This will override the 'example_attribute' value
	$model->attr('example_attribute', 'some_other_value');
	
	// This will save the model, and all attributes to the database
	$model->save();
	
EAV makes the distinction between a newly added attribute and an existing attribute. You don't have to worry about whether the attribute should be inserted or updated. 
So the same principle applies to already existing objects.
	
## Getting attributes from the model
In the previous example we created a new model and saved it, for the sake of simplicity I'm going to assume it got a primary key value around `1`.

	// Load a model with primary key value 1
	$model = EAV::factory('Product', 1);
	
At this point, the tables are automatically joined and the attributes are loaded. EAV does not need a seperate query for the attributes.

	// This will return an array of all the attributes
	$attributes = $model->attr();
	
	// Now load some individual attributes
	$example_attribute = $model->attr('example_attribute'); // Returns EAV_Attribute with the name 'example_attribute'
	$another_attribute = $model->attr('another_attribute'); // Returns EAV_Attribute with the name 'another_attribute'
	$some_attribute    = $model->attr('some_attribute');    // Returns EAV_Attribute with the name 'some_attribute'
	
So now that we've loaded some attributes we want to show them to our visitors.

	foreach($model->attr() as $attribute)
	{
		echo $attribute->name .' => '. $attribute->value .'<br>';
	}
	
Which would output something like

	example_attribute => some_other_value
	another_attribute => another_value
	some_attribute => some_value
	
## Searching for models with EAV
EAV comes with a build-in attribute filtering system which allows for the familiar syntax with a slight naming adjustment.

For example, to find all models with the attribute price greater than one hundred.

	$models = EAV::factory('Product')->where_attr('price', '>', 100)->find_all();
	
Using a different function for the attributes enables us to keep the typical search method in. Let's say we want to find dog food with a weight lower than 10.

	$models = EAV::factory('Product')
			->where('name', 'LIKE', '%dog food%') // All products have a name
			->and_where_attr('weight', '<', 10)   // Not all products have a 'weight'
			->find_all();
			
Be aware of the fact that searching in an EAV system requires a lot of power and lots of 'where_attr' clauses require a lot of joining subtables at this point.

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