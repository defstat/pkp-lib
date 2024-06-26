<?php

/**
 * @file tests/jobs/submissions/UpdateSubmissionSearchJobTest.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Tests for the submission search reindexing job.
 */

namespace PKP\tests\jobs\submissions;

use Mockery;
use PKP\jobs\submissions\UpdateSubmissionSearchJob;
use PKP\tests\PKPTestCase;

class UpdateSubmissionSearchJobTest extends PKPTestCase
{
    /**
     * Ensure that a serialized job can be unserialized and executed
     */
    public function testRunSerializedJob()
    {
        // Serializion from OJS 3.4.0
        $updateSubmissionSearchJob = unserialize('O:46:"PKP\jobs\submissions\UpdateSubmissionSearchJob":3:{s:15:" * submissionId";i:17;s:10:"connection";s:8:"database";s:5:"queue";s:5:"queue";}');

        // Ensure that the unserialized object is the right class
        $this->assertInstanceOf(UpdateSubmissionSearchJob::class, $updateSubmissionSearchJob);

        // Mock the Submission facade to return a fake submission when Repo::submission()->get($id) is called
        $mock = Mockery::mock(app(\APP\submission\Repository::class))
            ->makePartial()
            ->shouldReceive('get')
            ->with(17) // Submission ID from serialization string
            ->andReturn(new \APP\submission\Submission())
            ->getMock();

        app()->instance(\APP\submission\Repository::class, $mock);

        // Test that the job can be handled without causing an exception.
        $updateSubmissionSearchJob->handle();
    }
}
