<?php
use WebImage\Node\Types\Type;
return [
	'namespaces' => [
//		['uri' => 'http://www.cwimage.com/model/core', 'prefix' => 'cwi'],
//		['uri' => 'http://www.cwimage.com/model/extension', 'prefix' => 'ext'],
//		['uri' => 'http://www.cwimage.com/system', 'prefix' => 'sys']
//		['uri' => 'com.webimage.node.type.core', 'prefix' => 'cwi'],
//		['uri' => 'WebImage\Node\Types\Type', 'prefix' => 'nt'],
//		['uri' => 'com.webimage.node.type.extension', 'prefix' => 'ext'],
//		['uri' => 'com.webimage.node.system', 'prefix' => 'sys']
		/**
		 * NodeType nt:
		 * DataType dt:
		 */
	],
	'types' => [
		[
			'qname' => 'cwi:Root',
			'model' => ['name' => 'nodes', 'dataSaver' => 'CWI_CNODE_Root']
		],
		[
			'qname' => 'sys:Type',
			'model' => ['name' => 'node_types']
		],
		[
			'isSubClassable' => true,
			'qname' => 'cwi:Base',
			'parent' => 'cwi:Root',
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
			'qname' => 'cwi:Content',
			'parent' => 'cwi:Base',
			'model' => ['name' => 'node_content'],
			'friendlyName' => 'Content',
			'enableLocales' => false,
			'enableProfiles' => false,
			'extensions' => [
				['name' => 'ext:Ownable']
			],
			'properties' => [
				['name' => 'body', 'friendlyName' => 'Body', 'searchable' => true]
			]
		]
	],
	'extensions' => [
		['qname' => 'ext:Ownable', 'model' => ['name' => 'node_publishable']],
		['qname' => 'ext:Authorable', 'associations' => [
			[
				'qname' => 'cwi:Author',
				'source' => [
					'mandatory' => false,
					'many' => true
				],
				'target' => [
					'class' => 'sys:Base',
					'mandatory' => true,
					'many' => true
				]
			]
		]]
	],
	'dataTypes' => [
		['type' => 'WebImage.Node.DataType.String', 'name' => 'Single Line', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.Node.DataType', 'name' => 'Multi Line', 'defaultInputElementClass' => 'WysiwygInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::TEXT]],
		['type' => 'WebImage.Node.DataType.Integer', 'name' => 'Integer', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'int', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.Node.DataType.Date', 'name' => 'Date', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::DATE]],
		['type' => 'WebImage.Node.DataType.DateTime', 'name' => 'Date/Time', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::DATETIME]],
		['type' => 'WebImage.Node.DataType.Boolean', 'name' => 'True/False', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'boolean', 'modelField' => ['type' => Type::BOOLEAN]],
//		['type' => 'WebImage.Node.DataType.QName', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.Node.DataType.NodeRef', 'name' => 'Reference', 'defaultInputElementClass' => 'TextInputElement', 'modelField' => ['type' => Type::STRING, 'options' => ['length' => 255]]],
		['type' => 'WebImage.Node.DataType.ChildAssocRef', 'name' => 'Child Association', 'defaultInputElementClass' => 'TextInputElement', 'modelField' => ['type' => Type::INTEGER]], //CWI_REPO_SERVICE_ChildAssociationRef'],
		['type' => 'WebImage.Node.DataType.AssocRef', 'name' => 'Association Ref', 'defaultInputElementClass' => 'TextInputElement', 'phpClassName' => 'CWI_REPO_SERVICE_AssociationRef'],
//		['type' => 'WebImage.Node.DataType.Category', 'name' => 'Category', 'defaultInputElementClass' => 'TextInputElement', 'phpType' => 'string'],
		['type' => 'WebImage.Node.DataType.User', 'name' => 'User', 'defaultInputElementClass' => 'TextInputElement', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.Node.DataType.File', 'name' => 'File', 'defaultInputElementClass' => 'FileUploadElement', 'phpClassName' => 'CWI_CNODE_DATATYPE_File', 'modelField' => ['type' => Type::INTEGER]],
		['type' => 'WebImage.Node.DataType.Embeddedmedia', 'name' => 'Embedded Media', 'defaultInputElementClass' => 'CWI_CNODE_FORMBUILDER_EmbeddedMediaElement', 'modelFields' => [
				['name' => 'embed', 'type' => Type::TEXT],
				['name' => 'value', 'type' => Type::STRING, 'options' => ['length' => '255']],
				['name' => 'provider', 'type' => Type::STRING, 'options' => ['length' => '255']],
				['name' => 'data', 'type' => Type::TEXT]
		]],
		['type' => 'WebImage.Node.DataType.Link', 'name' => 'Link', 'defaultInputElementClass' => 'LinkInputElement', 'modelFields' => [
			['name' => 'url', 'type' => Type::STRING, 'options' => ['length' => '255']],
			['name' => 'title', 'type' => Type::STRING, 'options' => ['length' => '255']]
		]],
		['type' => 'WebImage.Node.DataType.Address', 'name' => 'Address', 'defaultInputElementClass' => 'AddressInputElement', 'modelFields' => [
			['name' => 'street1', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
			['name' => 'street2', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
			['name' => 'city', 'type' => Type::STRING, 'options' => ['length' => 200, 'notnull' => false]],
			['name' => 'state', 'type' => Type::STRING, 'options' => ['length' => 3, 'notnull' => false]],
			['name' => 'country', 'type' => Type::STRING, 'options' => ['length' => 255, 'notnull' => false]],
			['name' => 'zip', 'type' => Type::STRING, 'options' => ['length' => 10, 'notnull' => false]],
		]]
	]
];