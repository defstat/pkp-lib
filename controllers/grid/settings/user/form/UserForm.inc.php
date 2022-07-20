<?php

/**
 * @file controllers/grid/settings/user/form/UserForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserForm
 * @ingroup controllers_grid_settings_user_form
 *
 * @brief Base class for user forms.
 */

use APP\template\TemplateManager;

use PKP\form\Form;
use APP\facades\Repo;

class UserForm extends Form
{
    /** @var int Id of the user being edited */
    public $userId;

    /**
     * Constructor.
     *
     * @param int $userId optional
     */
    public function __construct($template, $userId = null)
    {
        parent::__construct($template);

        $this->userId = isset($userId) ? (int) $userId : null;

        if (!is_null($userId)) {
            $this->addCheck(new \PKP\form\validation\FormValidator($this, 'userGroupIds', 'required', 'manager.users.roleRequired'));
        }
    }

    /**
     * Initialize form data from current user profile.
     */
    public function initData()
    {   
        if (!is_null($this->userId)) {
            $userGroups = Repo::userGroup()->userUserGroups($this->userId);
            $userGroupIds = [];
            foreach($userGroups as $userGroup) {
                $userGroupIds[] = $userGroup->getId();
            }
            $this->setData('userGroupIds', $userGroupIds);
        }
        

        parent::initData();
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars(['userGroupIds']);
        parent::readInputData();
    }

    /**
     * @copydoc Form::display
     *
     * @param null|mixed $request
     * @param null|mixed $template
     */
    public function display($request = null, $template = null)
    {
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : \PKP\core\PKPApplication::CONTEXT_ID_NONE;
        $templateMgr = TemplateManager::getManager($request);

        $allUserGroups = [];

        $userGroups = Repo::userGroup()->getByContextId($contextId);
        foreach ($userGroups as $userGroup) {
            $allUserGroups[(int) $userGroup->getId()] = $userGroup->getLocalizedName();
        }

        $templateMgr->assign([
            'allUserGroups' => $allUserGroups,
            'assignedUserGroups' => array_map('intval', $this->getData('userGroupIds')),
        ]);

        return $this->fetch($request);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        if (isset($this->userId)) {
            Repo::userGroup()->deleteAssignmentsByContextId(Application::get()->getRequest()->getContext()->getId(), $this->userId);

            if ($this->getData('userGroupIds')) {
                $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
                foreach ($this->getData('userGroupIds') as $userGroupId) {
                    $userGroupDao->assignUserToGroup($this->userId, $userGroupId);
                }
            }
        }

        parent::execute(...$functionArgs);
    }
}
