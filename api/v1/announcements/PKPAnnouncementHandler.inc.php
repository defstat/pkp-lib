<?php
/**
 * @file api/v1/announcements/PKPAnnouncementHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAnnouncementHandler
 * @ingroup api_v1_announcements
 *
 * @brief Base class to handle API requests for announcements
 */

import('lib.pkp.classes.handler.APIHandler');
import('classes.core.ServicesContainer');

class PKPAnnouncementHandler extends APIHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->_handlerPath = 'announcements';
		$roles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'getAnnouncements'),
					'roles' => $roles
				),
			),
		);
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get a collection of announcements
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param $args array arguments
	 *
	 * @return Response
	 */
	public function getAnnouncements($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$announcementService = ServicesContainer::instance()->get('announcement');

		// Merge query params over default params
		$defaultParams = array(
			'count' => 20,
			'offset' => 0,
		);

		$requestParams = array_merge($defaultParams, $slimRequest->getQueryParams());

		$params = [];

		foreach ($requestParams as $param => $val) {
			switch ($param) {
				case 'type':
					if (is_string($val) && strpos($val, ',') > -1) {
						$val = explode(',', $val);
					} elseif (!is_array($val)) {
						$val = array($val);
					}
					$params[$param] = array_map('intval', $val);
					break;

				case 'searchPhrase':
					$params[$param] = trim($val);
					break;

				// Enforce a maximum count to prevent the API from crippling the
				// server
				case 'count':
					$params[$param] = min(100, (int) $val);
					break;

				case 'offset':
					$params[$param] = (int) $val;
					break;
			}
		}

		\HookRegistry::call('API::announcements::params', array(&$params, $slimRequest));

		$items = [];
		$announcements = $announcementService->getAnnouncements($context->getId(), $params);
		if (!empty($announcements)) {
			$propertyArgs = [
				'request' => $request,
				'slimRequest' => $slimRequest,
			];
			foreach ($announcements as $announcement) {
				$items[] = $announcementService->getSummaryProperties($announcement, $propertyArgs);
			}
		}

		$data = [
			'itemsMax' => $announcementService->getAnnouncementsMax($context->getId(), $params),
			'items' => $items,
		];

		return $response->withJson($data, 200);
	}
}
