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
	//var $SCHEMA_GL = "/xml/schema";

	/**
	 * @var XMLNode
	 */
	var $_treeGlobal;

	/**
	 * Constructor
	 */
	function __construct($deployment, $entitySchema) {
		// Read the XML schema
		$xmlParser = new XMLParser();
		$schemaGlobalFile = Core::getBaseDir() . '/' . PKP_LIB_PATH . $this->SCHEMA_GL;
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

		$this->_entitySchema = $entitySchema;

		$this->_deployment = $deployment;
	}

	function exportEntity($doc, $entityName, $entityArray) {
		$entityNameSingular = "submission";
		$entityIdColumnName = $entityNameSingular. "_id";
		$entitySettingsName = $entityNameSingular. "_settings";
		$entityClass = "classes.article.Article";
		$entityClassName = "Article";

		$entityDAOClassName = "ArticleDAO";

		import($entityClass);


		/**
		 * @var $entityNode XMLNode
		 */
		$entityNode = $this->_treeGlobal->getChildByName($entityName);

		/**
		 * @var $children array
		 */
		$children = $entityNode->getChildren();

		/**
		 * @var $child XMLNode
		 */
		$submissionNode = $doc->createElementNS("test", $entityNameSingular);

		foreach ($entityArray as $entity) {

			// Add main DB Attributes
			foreach($children as $child) {
				$columnName = $child->getAttribute('name');
				$childName = $child->getName();
				if ($childName == 'field' && $columnName) {
					if ($entityIdColumnName == $columnName) {
						$submissionNode->setAttribute("old_id", $entity->getId());
					} else {
						$getterFunctionName = 'get'.$this->reNameColumn($columnName);
						if (method_exists($entity, $getterFunctionName)){
							$submissionNode->setAttribute($columnName, $entity->$getterFunctionName());
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
					$this->createLocalizedNodes($doc, $submissionNode, $localisedField, $entity->$getterFunctionName(null));
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
						$submissionNode->appendChild($node = $doc->createElementNS($this->_deployment->getNamespace(), 'id', $entity->$getterFunctionName($isTwoPartField[1])));
					} else {
						// ADD WARNING
					}
				} else {
					$getterFunctionName = 'get'.$this->reNameColumn($additionalField);
					if (method_exists($entity, $getterFunctionName)){
						$submissionNode->appendChild($node = $doc->createElementNS($this->_deployment->getNamespace(), $additionalField, $entity->$getterFunctionName()));
					} else {
						// ADD WARNING
					}
				}
			}
		}


		$doc->appendChild($submissionNode);
		$submissionNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$submissionNode->setAttribute('xsi:schemaLocation', "test");

		return $doc;

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
