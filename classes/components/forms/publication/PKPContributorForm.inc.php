<?php
/**
 * @file classes/components/form/publication/PKPTitleAbstractForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPTitleAbstractForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's title and abstract
 */
namespace PKP\components\forms\publication;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldText;
use \PKP\components\forms\FieldRichTextarea;
use \PKP\components\forms\FieldOptions;

define('FORM_CONTRIBUTOR', 'contributor');

class PKPContributorForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_CONTRIBUTOR;

	/** @copydoc FormComponent::$method */
	public $method = 'POST';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context \Context The publication to change settings for
	 */
	public function __construct($action, $locales, $context) {
		$this->action = $action;
		$this->locales = $locales;

		$userGroupDao = \DAORegistry::getDAO('UserGroupDAO'); /** @var $userGroupDao \UserGroupDAO */
		$authorUserGroups = $userGroupDao->getByRoleId($context->getId(), ROLE_ID_AUTHOR)->toArray();

		$authorUserGroupsOptions = [];
		foreach ($authorUserGroups as $authorUserGroup) {
			$authorUserGroupsOptions[] = [
				'value' => (int) $authorUserGroup->getId(),
				'label' => $authorUserGroup->getLocalizedName(),
			];
		}

		$this->addField(new FieldText('givenName', [
				'label' => __('user.givenName'),
				'isMultilingual' => true,
				// 'value' => $author->getData('givenName'),
				'isRequired' => true
			]))
			->addField(new FieldText('familyName', [
				'label' => __('user.familyName'),
				'isMultilingual' => true,
				// 'value' => $author->getData('familyName'),
			]))
			->addField(new FieldText('email', [
				'label' => __('about.contact'),
				'isRequired' => true,
			]))
			->addField(new FieldOptions('userGroupId', [
				'label' => __('submission.submit.contributorRole'),
				'type' => 'radio',
				'options' => $authorUserGroupsOptions,
			]));;
	}
}
