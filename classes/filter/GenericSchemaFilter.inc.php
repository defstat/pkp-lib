<?php

/**
 * GenericSchemaFilter short summary.
 *
 * GenericSchemaFilter description.
 *
 * @version 1.0
 * @author defstat
 */
class GenericSchemaFilter extends NativeExportFilter {

	var $_entitySchema = "";

	var $SCHEMA_CONTEXT = "/dbscripts/xml/ojs_schema.xml";
	var $SCHEMA_GL = "/xml/schema/submissions.xml";
	var $ENTITYLINK_SCHEMA = "/classes/filter/GenericFilteringSchema.xml";

	/**
	 * @var XMLNode
	 */
	var $_treeGlobal;

	/**
	 * @var XMLNode
	 */
	var $_entityLinksGlobal;

	/**
	 * Constructor
	 */
	function __construct($deployment, $entitySchema) {
		// Read the XML schema
		$xmlParser = new XMLParser();
		$schemaGlobalFile = Core::getBaseDir() . '/' . PKP_LIB_PATH . $this->SCHEMA_GL;
		$entityLinkFile = Core::getBaseDir() . '/' . PKP_LIB_PATH . $this->ENTITYLINK_SCHEMA;
		$schemaContextFile = Core::getBaseDir() . $this->SCHEMA_CONTEXT;

		$treeGlobal = $xmlParser->parse($schemaGlobalFile);
		$treeContext = $xmlParser->parse($schemaContextFile);

		$treeGlobal->children = array_merge($treeGlobal->children, $treeContext->children);

		/**
		 * @var $child XMLNode
		 */
		foreach($treeGlobal->children as $child) {
			$child->setName($child->getAttribute('name'));
		}

		$this->_treeGlobal = $treeGlobal;

		$entityLinksTree = $xmlParser->parse($entityLinkFile);
		$this->_entityLinksGlobal = $entityLinksTree;

		$this->_entitySchema = $entitySchema;
		$this->_deployment = $deployment;
	}

	function exportEntity($doc, $entityName, $entityArray) {
		/**
		 * @var $entityRefNode XMLNode
		 */
		$entityRefNode = $this->_entityLinksGlobal->getChildByName($entityName);	/** @var $entityRefNode XMLNode */

		$schema = $entityRefNode->getAttribute('schema');
		$entityClass = $entityRefNode->getAttribute('class');
		$entitySettingsName = $entityRefNode->getAttribute('setting_schema');
		$entityPlural = $entityRefNode->getAttribute('plural');
		$entityIdColumnName = $entityRefNode->getAttribute('id');
		$entityClassName = $entityRefNode->getAttribute('single-class-name');
		$entityDAOClassName = $entityRefNode->getAttribute('classDAO');

		import($entityClass);


		/**
		 * @var $entityNode XMLNode
		 */
		$entityNode = $this->_treeGlobal->getChildByName($schema);

		/**
		 * @var $children array
		 */
		$children = $entityNode->getChildren();

		/**
		 * @var $child XMLNode
		 */
		$entityExportNode = $doc->createElementNS("test", $entityName);

		foreach ($entityArray as $entity) {

			// Add main DB Attributes
			foreach($children as $child) {
				$columnName = $child->getAttribute('name');
				$childName = $child->getName();
				if ($childName == 'field' && $columnName) {
					if ($entityIdColumnName == $columnName) {
						$entityExportNode->setAttribute("old_id", $entity->getId());
					} else {
						$getterFunctionName = 'get'.$this->reNameColumn($columnName);
						if (method_exists($entity, $getterFunctionName)){
							$entityExportNode->setAttribute($columnName, $entity->$getterFunctionName());
						} else {
							// ADD WARNING
						}
					}
				}
			}

			// Get EntityDAO class
			$entityDao = DAORegistry::getDAO($entityDAOClassName);

			// Add localised settings
			$localisedFields = $entityDao->getLocaleFieldNames();

			foreach ($localisedFields as $localisedField) {
				$getterFunctionName = 'get'.$this->reNameColumn($localisedField);
				if (method_exists($entity, $getterFunctionName)){
					$this->createLocalizedNodes($doc, $entityExportNode, $localisedField, $entity->$getterFunctionName(null));
				} else {
					// ADD WARNING
				}

			}

			// Add additional settings
			$additionalFields = $entityDao->getAdditionalFieldNames();
			foreach ($additionalFields as $additionalField) {
				$isTwoPartField = $this->checkIfColumnTwoParts($additionalField);

				if ($isTwoPartField) {
					$getterFunctionName = $isTwoPartField[0];
					if (method_exists($entity, $getterFunctionName)){
						$entityExportNode->appendChild($childnode = $doc->createElementNS($this->_deployment->getNamespace(), 'id', $entity->$getterFunctionName($isTwoPartField[1])));
					} else {
						// ADD WARNING
					}
				} else {
					$getterFunctionName = 'get'.$this->reNameColumn($additionalField);
					if (method_exists($entity, $getterFunctionName)){
						$entityExportNode->appendChild($childnode = $doc->createElementNS($this->_deployment->getNamespace(), $additionalField, $entity->$getterFunctionName()));
					} else {
						// ADD WARNING
					}
				}
			}
		}

		$this->exportChildren($doc, $entityExportNode, $entityRefNode->getChildren());

		$doc->appendChild($entityExportNode);
		$entityExportNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$entityExportNode->setAttribute('xsi:schemaLocation', "test");

		return $doc;

	}

	function exportChildren($doc, $node, $entityChildren) {

		foreach($entityChildren as $entityChild){	 /** @var $entityChild XMLNode */
			exportEntity($doc, $node, $entityChild->getName())
		}
		$childNode = $this->_entityLinksGlobal->getChildByName($entityName);

		return $node;
	}

	function reNameColumn($name, $char = '_') {
		$parts = explode($char, $name);

		$ret = "";
		foreach($parts as $part) {
			$ret .= ucfirst($part);
		}

		return $ret;
	}

	function checkIfColumnTwoParts($name) {
		$parts = explode('::', $name);

		if (count($parts) != 2) {
			return null;
		}

		$parts[0] = $this->reNameColumn($parts[0], '-');
		if ($parts[0] == "PubId") {
			$parts[0] = "getStored".$parts[0];
		}

		return $parts;
	}
}
