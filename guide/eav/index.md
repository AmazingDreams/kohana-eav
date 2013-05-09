# EAV
Entity–attribute–value model (EAV) is a data model to describe entities where the number of attributes (properties, parameters) that can be used to describe them is potentially vast, 
but the number that will actually apply to a given entity is relatively modest. In mathematics, this model is known as a sparse matrix. 
EAV is also known as object–attribute–value model, vertical database model and open schema.

There are certain cases where an EAV schematic is an optimal approach to data modelling for a problem domain. 
However, in many cases where data can be modelled in statically relational terms an EAV based approach is an anti-pattern which can lead to longer development times, 
poor use of database resources and more complex queries when compared to a relationally-modelled data schema.

Source [Wikipedia](http://en.wikipedia.org/wiki/Entity-attribute-value_model)

## Getting Started
Before we can use EAV, we must enable the modules required.

	Kohana::modules(array(
		...
		'database' => MODPATH.'database',
		'eav' => MODPATH.'eav',
		'orm' => MODPATH.'orm',
		...
	));