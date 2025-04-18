<?php

/**
 * @file classes/security/authorization/internal/SubmissionIncompletePolicy.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionIncompletePolicy
 *
 * @brief   Policy that ensures that the request contains a submission that has 
 *          completed the submission process
 * 
 */

namespace PKP\security\authorization\internal;

use APP\facades\Repo;
use PKP\security\authorization\DataObjectRequiredPolicy;
use PKP\core\PKPRequest;
use PKP\security\authorization\AuthorizationPolicy;

class SubmissionIncompletePolicy extends DataObjectRequiredPolicy
{
    /**
     * Constructor
     * 
     * @param PKPRequest 	$request 					The PKP core request object
     * @param array 		$args 						Request parameters
     * @param string 		$submissionParameterName 	The request parameter we expect the submission id in.
     * @param string|null   $message                    The localized message to show on policy failure
     * @param mixed|null 	$operation 					Optional list of operations for which this check takes effect. If specified, 
     * 													operations outside this set will not be checked against this policy
     */
    function __construct(
        PKPRequest $request,
        array &$args,
        string $submissionParameterName = 'submissionId',
        ?string $message = null,
        mixed $operations = null
    )
    {
        parent::__construct($request, $args, $submissionParameterName, $message, $operations);
    }


    //
    // Implement template methods from AuthorizationPolicy
    //
    /**
     * @see DataObjectRequiredPolicy::dataObjectEffect()
     */
    public function dataObjectEffect()
    {
        $submissionExists =  Repo::submission()
            ->getCollector()
            ->filterByContextIds([$this->_request->getContext()->getId()])
            ->filterByIncomplete(true)
            ->filterBySubmissionIds([$this->getDataObjectId()])
            ->getQueryBuilder()
            ->exists();

        return $submissionExists
            ? AuthorizationPolicy::AUTHORIZATION_PERMIT
            : AuthorizationPolicy::AUTHORIZATION_DENY;
    }
}
