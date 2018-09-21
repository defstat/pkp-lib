<?php
/**
 * @file controllers/list/announcements/PKPAnnouncementsListHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAnnouncementsListHandler
 * @ingroup controllers_list
 *
 * @brief Instantiates and manages a UI component to list announcements.
 */
import('lib.pkp.controllers.list.ListHandler');
import('lib.pkp.classes.db.DBResultRange');
import('classes.core.ServicesContainer');

class PKPAnnouncementsListHandler extends ListHandler {

	/**
	 * @copydoc ListHandler::getConfig()
	 */
	public function getConfig() {

		if ($this->_lazyLoad) {
			$config['lazyLoad'] = true;
		} else {
			$config['items'] = $this->getItems();
			$config['itemsMax'] = $this->getItemsMax();
		}

		$config['apiPath'] = $this->_apiPath;

		$config['count'] = $this->_count;
		$config['page'] = 1;

		$config['getParams'] = $this->_getParams;

		// Attach a CSRF token for post requests
		$config['csrfToken'] = Application::getRequest()->getSession()->getCSRFToken();

		$config['i18n'] = array(
			'id' => __('common.id'),
			'title' => __($this->_title),
			'add' => __('grid.action.addAnnouncement'),
			'search' => __('common.search'),
			'clearSearch' => __('common.clearSearch'),
			'itemCount' => __('manager.announcements.list.itemCount'),
			'itemsOfTotal' => __('manager.announcements.list.itemsOfTotal'),
			'loadMore' => __('grid.action.moreItems'),
			'loading' => __('common.loading'),
			'delete' => __('common.delete'),
			'viewMore' => __('list.viewMore'),
			'viewLess' => __('list.viewLess'),
		);

		return $config;
	}

	/**
	 * @copydoc ListHandler::getItems()
	 */
	public function getItems() {
		$request = Application::getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;

		$announcementService = ServicesContainer::instance()->get('announcement');
		$announcements = $announcementService->getAnnouncements($context->getId(), $this->_getItemsParams());
		$items = array();
		if (!empty($announcements)) {
			$propertyArgs = array(
				'request' => $request,
			);
			foreach ($announcements as $announcement) {
				$items[] = $announcementService->getSummaryProperties($announcement, $propertyArgs);
			}
		}

		return $items;
	}

	/**
	 * @copydoc ListHandler::getItemsMax()
	 */
	public function getItemsMax() {
		$request = Application::getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;

		return ServicesContainer::instance()
			->get('announcement')
			->getAnnouncementsMaxCount($context->getId(), $this->_getItemsParams());
	}

	/**
	 * @copydoc ListHandler::_getItemsParams()
	 */
	protected function _getItemsParams() {
		return array_merge(
			array(
				'count' => $this->_count,
				'offset' => 0,
			),
			$this->_getParams
		);
	}
}
