<?php

/**
 * @file classes/components/form/context/PKPDateTimeForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPDateTimeForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for presenting date and time on the frontend
 */

namespace PKP\components\forms\context;

use PKP\components\forms\FieldRadioInput;
use PKP\components\forms\FormComponent;

class PKPDateTimeForm extends FormComponent
{
    public const FORM_DATE_TIME = 'dateTime';
    public $id = self::FORM_DATE_TIME;
    public $method = 'PUT';

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param array $locales Supported locales
     * @param \Context $context Journal or Press to change settings for
     */
    public function __construct($action, $locales, $context)
    {
        $this->action = $action;
        $this->locales = $locales;

        $localizedOptions = []; // template for localized options to be used for date and time format
        foreach ($this->locales as $key => $localeValue) {
            $localizedOptions[$localeValue['key']] = $key;
        }

        $this->addGroup([
            'id' => 'descriptions',
            'label' => __('manager.setup.dateTime.descriptionTitle'),
            'description' => __('manager.setup.dateTime.description'),
        ])
            //The default date format to use in the editorial and reader interfaces.
            ->addField(new FieldRadioInput('dateFormatLong', [
                'label' => __('manager.setup.dateTime.longDate'),
                'isMultilingual' => true,
                'options' => $this->_setDateOptions([
                    'F j, Y',
                    'F j Y',
                    'j F Y',
                    'Y F j',
                ]),
                'value' => $context->getDateTimeFormats('dateFormatLong'),
                'groupId' => 'descriptions',
            ]))
            // A brief date format that is used when there is less space for the full date.
            ->addField(new FieldRadioInput('dateFormatShort', [
                'label' => __('manager.setup.dateTime.shortDate'),
                'isMultilingual' => true,
                'options' => $this->_setDateOptions([
                    'Y-m-d',
                    'd-m-Y',
                    'm/d/Y',
                    'd.m.Y',
                ]),
                'value' => $context->getDateTimeFormats('dateFormatShort'),
                'groupId' => 'descriptions',

            ]))
            ->addField(new FieldRadioInput('timeFormat', [
                'label' => __('manager.setup.dateTime.time'),
                'isMultilingual' => true,
                'options' => $this->_setDateOptions([
                    'H:i',
                    'h:i A',
                    'g:ia',
                ]),
                'value' => $context->getDateTimeFormats('timeFormat'),
                'groupId' => 'descriptions',
            ]))
            ->addField(new FieldRadioInput('datetimeFormatLong', [
                'label' => __('manager.setup.dateTime.longDateTime'),
                'isMultilingual' => true,
                'options' => array_map(function ($value) use ($context, $localizedOptions) {
                    $locale = array_search($value, $localizedOptions);
                    $optionValue = $context->getLocalizedDateFormatLong($locale) . ' - ' . $context->getLocalizedTimeFormat($locale);
                    return [
                        [
                            'value' => $optionValue,
                            'label' => $optionValue,
                        ],
                        [
                            'isInput' => true,
                            'label' => __('manager.setup.dateTime.custom'),
                        ]
                    ];
                }, $localizedOptions),
                'value' => $context->getDateTimeFormats('datetimeFormatLong'),
                'groupId' => 'descriptions',
            ]))
            ->addField(new FieldRadioInput('datetimeFormatShort', [
                'label' => __('manager.setup.dateTime.shortDateTime'),
                'isMultilingual' => true,
                'options' => array_map(function ($value) use ($context, $localizedOptions) {
                    $locale = array_search($value, $localizedOptions);
                    $optionValue = $context->getLocalizedDateFormatShort($locale) . ' ' . $context->getLocalizedTimeFormat($locale);
                    return [
                        [
                            'value' => $optionValue,
                            'label' => $optionValue,
                        ],
                        [
                            'isInput' => true,
                            'label' => __('manager.setup.dateTime.custom'),
                        ]
                    ];
                }, $localizedOptions),
                'value' => $context->getDateTimeFormats('datetimeFormatShort'),
                'groupId' => 'descriptions',
            ]));
    }

    /**
     * Set localized options for date/time fields
     *
     * @param array $optionValues options to pass to the field
     *
     * @return array
     */
    private function _setDateOptions($optionValues)
    {
        $options = [];
        foreach ($this->locales as $localeValue) {
            $locale = $localeValue['key'];
            foreach ($optionValues as $optionValue) {
                $options[$locale][] = [
                    'value' => $optionValue,
                    'label' => $optionValue
                ];
            }

            $options[$locale][] = [
                'isInput' => true,
                'label' => __('manager.setup.dateTime.custom'),
            ];
        }
        return $options;
    }
}
