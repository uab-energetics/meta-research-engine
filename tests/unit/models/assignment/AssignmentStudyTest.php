<?php
namespace Tests\Models;

use \Models\Edges\Assignment;
use Models\Vertices\Paper;
use Models\Vertices\Project;
use Models\Vertices\User;

use triagens\ArangoDb\Edge;

/**
 * Created by PhpStorm.
 * User: chris
 * Date: 4/30/17
 * Time: 12:00 AM
 */
class AssignmentTest extends \Tests\BaseTestCase
{
    /**
     * @var Project
     */
    private $study;
    /**
     * @var Paper
     */
    private $paper;
    /**
     * @var User
     */
    private $user;

    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->withMiddleware = false;
        // Need a study, paper, user, and assignment


        $this->study = Project::create([]);
        $this->paper = Paper::create([]);
        $this->user  = User::create([]);

        $this->study->addPaper( $this->paper );
    }

    /**
     * @return Assignment
     */
    public function testGetProject(){

        $newAssignment = Assignment::assign( $this->paper, $this->user );
        $project = $newAssignment->getProject();

        self::assertInstanceOf( Project::class, $project );
        return $newAssignment;
    }

    /**
     * @depends testGetProject
     */
    public function testGetPaperCoderData( $assignmentModel ){
        $response = $this->runApp("GET", "/loadAssignment?key=".$assignmentModel->key());

        self::assertEquals(200, $response->getStatusCode());
    }
}