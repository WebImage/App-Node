<?php
use WebImage\Node\DataTypes\Type;
return [
	'types' => [
		[
			'qname' => 'WebImage.Node.Types.Root',
			'model' => ['name' => 'nodes', 'dataSaver' => 'CWI_CNODE_Root']
		],
		[
			'qname' => 'WebImage.Node.Types.Type',
			'model' => ['name' => 'node_types']
		],
		[
			'isSubClassable' => true,
//			'parent' => 'cwi:Root',
			'qname' => 'WebImage.Node.Types.Base',
			'parent' => 'WebImage.Node.Types.Root',
			'friendlyName' => 'Title',
			'model' => ['name' => 'node_base'],
			'properties' => [
				// CustomTitltePropertyClass would need to extends a lower level property
				['name' => 'title', 'friendlyName' => 'Title', 'searchable' => true, 'propertyClass' => 'CustomTitlePropertyClass',
					'type' => 'string', 'dataType' => 'string']
			]
		],
		[
			'isSubClassable' => true,
			'qname' => 'WebImage.Node.Types.Content',
			'parent' => 'WebImage.Node.Types.Base',
			'model' => ['name' => 'node_content'],
			'friendlyName' => 'Content',
			'enableLocales' => false,
			'enableProfiles' => false,
			'extensions' => [
				['name' => 'WebImage.Node.Types.OwnableExtension']
			],
			'properties' => [
				['name' => 'body', 'friendlyName' => 'Body', 'searchable' => true]
			]
		]
	],
	'extensions' => [
		['qname' => 'WebImage.Node.Types.OwnableExtension', 'model' => ['name' => 'node_publishable']],
		['qname' => 'WebImage.Node.Types.AuthorableExtension', 'associations' => [
			[
				'qname' => 'WebImage.Node.Types.AuthorExtension',
				'source' => [
					'mandatory' => false,
					'many' => true
				],
				'target' => [
					'class' => 'WebImage.Node.Types.Base',
					'mandatory' => true,
					'many' => true
				]
			]
		]]
	],
	'dataTypes' => [
		['type' => 'WebImage.Node.DataTypes.String', 'name' => 'Single Line', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.Node.DataTypes', 'name' => 'Multi Line', 'defaultInputElementClass' => 'WysiwygInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::TEXT]],
		['type' => 'WebImage.Node.DataTypes.Integer', 'name' => 'Integer', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'int', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.Node.DataTypes.Date', 'name' => 'Date', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::DATE]],
		['type' => 'WebImage.Node.DataTypes.DateTime', 'name' => 'Date/Time', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::DATETIME]],
		['type' => 'WebImage.Node.DataTypes.Boolean', 'name' => 'True/False', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'boolean', 'modelField' => ['type' => Type::BOOLEAN]],
//		['type' => 'WebImage.Node.DataTypes.QName', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.Node.DataTypes.NodeRef', 'name' => 'Reference', 'defaultInputElementClass' => 'TextInputElement', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.Node.DataTypes.ChildAssocRef', 'name' => 'Child Association', 'defaultInputElementClass' => 'TextInputElement', 'modelField' => ['type' => Type::INTEGER]], //CWI_REPO_SERVICE_ChildAssociationRef'],
		['type' => 'WebImage.Node.DataTypes.AssocRef', 'name' => 'Association Ref', 'defaultInputElementClass' => 'TextInputElement', 'phpClassName' => 'CWI_REPO_SERVICE_AssociationRef'],
//		['type' => 'WebImage.Node.DataTypes.Category', 'name' => 'Category', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string'],
		['type' => 'WebImage.Node.DataTypes.User', 'name' => 'User', 'defaultInputElementClass' => 'TextInputElement', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.Node.DataTypes.File', 'name' => 'File', 'defaultInputElementClass' => 'FileUploadElement', 'phpClassName' => 'CWI_CNODE_DATATYPE_File', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.Node.DataTypes.Embeddedmedia', 'name' => 'Embedded Media', 'defaultInputElementClass' => 'CWI_CNODE_FORMBUILDER_EmbeddedMediaElement', 'modelFields' => [
				['name' => 'embed', 'type' => Type::TEXT],
				['name' => 'value', 'type' => Type::STRING, 'options' => ['length' => '255']],
				['name' => 'provider', 'type' => Type::STRING, 'options' => ['length' => '255']],
				['name' => 'data', 'type' => Type::TEXT]
		]],
		['type' => 'WebImage.Node.DataTypes.Link', 'name' => 'Link', 'defaultInputElementClass' => 'LinkInputElement', 'modelFields' => [
			['name' => 'url', 'type' => Type::STRING, 'options' => ['length' => '255']],
			['name' => 'title', 'type' => Type::STRING, 'options' => ['length' => '255']]
		]],
		['type' => 'WebImage.Node.DataTypes.Address', 'name' => 'Address', 'defaultInputElementClass' => 'AddressInputElement', 'modelFields' => [
			['name' => 'street1', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
			['name' => 'street2', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
			['name' => 'city', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
			['name' => 'state', 'type' => Type::STRING, 'options' => ['length' => 3, 'notnull' => false]],
			['name' => 'country', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
			['name' => 'zip', 'type' => Type::STRING, 'options' => ['length' => 10, 'notnull' => false]],
		]]
	]
];