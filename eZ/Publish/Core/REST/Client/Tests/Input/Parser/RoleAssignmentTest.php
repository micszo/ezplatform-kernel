<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\REST\Client\Tests\Input\Parser;

use eZ\Publish\Core\REST\Client\Input\Parser;
use eZ\Publish\API\Repository\Values\User\RoleAssignment;

class RoleAssignmentTest extends BaseTest
{
    /**
     * Tests the RoleAssignment parser.
     *
     * @return \eZ\Publish\API\Repository\Values\User\RoleAssignment
     */
    public function testParse()
    {
        $roleAssignmentParser = $this->getParser();

        $inputArray = array(
            '_href' => '/user/users/14/roles/42',
            'Role' => array(
                '_href' => '/user/roles/42',
                '_media-type' => 'application/vnd.ez.api.Role+xml',
            ),
        );

        $result = $roleAssignmentParser->parse($inputArray, $this->getParsingDispatcherMock());

        $this->assertNotNull($result);

        return $result;
    }

    /**
     * Tests that the resulting role is in fact an instance of RoleAssignment class.
     *
     * @param \eZ\Publish\API\Repository\Values\User\RoleAssignment $result
     *
     * @depends testParse
     */
    public function testResultIsRoleAssignment($result)
    {
        $this->assertInstanceOf(RoleAssignment::class, $result);
    }

    /**
     * Gets the parser for role assignment.
     *
     * @return \eZ\Publish\Core\REST\Client\Input\Parser\RoleAssignment;
     */
    protected function getParser()
    {
        return new Parser\RoleAssignment();
    }
}
