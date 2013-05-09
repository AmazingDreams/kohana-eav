# Creating your model

To create a model for the table `products` in your database simply hang on to the naming conventions of kohana and create the file `application/classes/models/product.php`:

	class Model_Product extends EAV {
		...
	}
	
## Table naming

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