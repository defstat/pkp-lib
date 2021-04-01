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

define('FORM_CONTRIBUTOR', 'contributor');

class PKPContributorForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_CONTRIBUTOR;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $author Author The publication to change settings for
	 */
	public function __construct($action, $locales, $author) {
		$this->action = $action;
		$this->locales = $locales;

		$this->addField(new FieldText('givenName', [
				'label' => __('user.givenName'),
				'isMultilingual' => true,
				'value' => $author->getData('givenName'),
				'isRequired' => true
			]))
			->addField(new FieldText('familyName', [
				'label' => __('user.familyName'),
				'isMultilingual' => true,
				'value' => $author->getData('familyName'),
			]));
	}
}
