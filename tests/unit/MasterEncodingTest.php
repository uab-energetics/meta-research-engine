<?php
require __DIR__ . '/../../lib/master_encoding/MasterEncoding.php';

class MasterEncodingTest extends \Tests\BaseTestCase {

    private $mockAssignment;
    private $masterEncoding;

    function setUp() {
        parent::setUp(); // TODO: Change the autogenerated stub
        $json = file_get_contents( __DIR__ . '/../data/mockAssignment.json' );
        $this->mockAssignment = json_decode( $json , true );
        $this->masterEncoding = [
            [
                "question" => "control",
                "responses" => [
                    [
                        "data" => [
                            "value" => "control"
                        ],
                        "users" => [
                            "control"
                        ]
                    ]
                ],
                "location" => 0
            ]
        ];
    }

    function testValid( ){
        self::assertEquals( 3, count( $this->mockAssignment['encoding']['constants'] ) );
        return $this->mockAssignment;
    }

    /**
     * @depends testValid
     */
    function testParseAssignment( $mockAssignment ){
        $output = MasterEncoding::parseAssignment( $mockAssignment );
        self::assertEquals( 3, count( $output ));
        return $output;
    }

    /**
     * @depends testParseAssignment
     */
    function testMatchRecord( $parsedOutput ){
        $dummyRecord = [
            "user" => "nobody",
            "question" => "nothing",
            "data" => [
                "value" => "nope"
            ],
            "location" => 99
        ];
        $goodRecord = [
            "user" => "control",
            "question" => "control",
            "data" => [
                "value" => "different values"
            ],
            "location" => 0
        ];

        $dummy = MasterEncoding::matchRecord( $dummyRecord, $this->masterEncoding );
        $good = MasterEncoding::matchRecord( $goodRecord, $this->masterEncoding );

        self::assertEquals( MasterEncoding::$NO_MATCH, $dummy );
        self::assertTrue( $good == $this->masterEncoding[0] );

        return $good;
    }

    /**
     * @depends testMatchRecord
     * @param $goodMasterRecord
     */
    function testMatchResponse( $goodMasterRecord ){
        $goodRecord = [
            "user" => "control",
            "question" => "control",
            "data" => [
                "value" => "control"
            ],
            "location" => 0
        ];
        $badUserResponse = [
            "user" => "control",
            "question" => "control",
            "data" => [
                "value" => "ontrol"
            ],
            "location" => 0
        ];

        $good = MasterEncoding::matchResponse( $goodRecord, $goodMasterRecord );
        $bad = MasterEncoding::matchResponse( $badUserResponse, $goodMasterRecord );

        self::assertEquals( MasterEncoding::$NO_MATCH, $bad );
        self::assertTrue( $good == $this->masterEncoding[0]['responses'][0] );
    }
    function testRecord(){
        $sampleRecord = [
            "user" => "im a record",
            "location" => "timbucktoo",
            "data" => [
                "value" => "some data"
            ],
            "question" => "some question"
        ];

        MasterEncoding::record( $sampleRecord, $this->masterEncoding );

        $result = false;
        foreach ( $this->masterEncoding as $masterRecord ){
            if( $masterRecord['question'] == $sampleRecord['question'] &&
                $masterRecord['location'] == $sampleRecord['location'] ) {
                $masterResponses = $masterRecord['responses'];
                foreach ( $masterResponses as $masterResponse ){
                    if( $masterResponse['data'] == $sampleRecord['data'] &&
                        in_array( $sampleRecord['user'], $masterResponse['users'] )){
                        $result = true;
                    }
                }
            }
        }
        self::assertTrue( $result );
    }
    function testRecordResponse(){
        $masterRecord = $this->masterEncoding[0];
        $sampleRecord = [
            "user" => "im a record",
            "location" => "timbucktoo",
            "data" => [
                "value" => "some data"
            ],
            "question" => "some question"
        ];
        MasterEncoding::recordResponse( $sampleRecord, $masterRecord );

        $result = false;
        foreach ( $masterRecord['responses'] as $masterResponse ){
            if( $masterResponse['data'] == $sampleRecord['data'] &&
                array_search( $sampleRecord['user'], $masterResponse['users']) !== false ){
                $result = true;
            }
        }

        self::assertTrue( $result );
    }
    function testRecordResponseUser(){
        $masterRecord = $this->masterEncoding[0];
        $masterResponse = $masterRecord['responses'][0];

        $sampleRecord = [
            "user" => "im a record",
            "location" => "timbucktoo",
            "data" => [
                "value" => "some data"
            ],
            "question" => "some question"
        ];

        MasterEncoding::recordResponseUser( $sampleRecord, $masterResponse );

        $result = false;
        foreach ( $masterResponse['users'] as $user ){
            if( $user == $sampleRecord['user']) $result = true;
        }

        self::assertTrue( $result );
    }
    function testMerge(){
        $masterEncoding = $this->masterEncoding;
        $assignment = $this->mockAssignment;

        $log = MasterEncoding::merge( $assignment, $masterEncoding );

        $file = fopen( __DIR__ . "/../data/masterEncodingOutput.json", "w");
        fwrite( $file, json_encode($masterEncoding, JSON_PRETTY_PRINT) );
        fclose( $file );
    }
    function testResponseChange(){
        $master = [];
        $assignment = json_decode( json_encode( $this->mockAssignment ), true );

        $log_first = MasterEncoding::merge( $assignment, $master );
        $assignment['encoding']['constants'][0]['data']['value'] = "D";
        $log_afterchange = MasterEncoding::merge( $assignment, $master );

        self::assertEquals( 3, count( $master ) );
    }
}