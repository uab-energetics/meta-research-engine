<?php

/* Assignment
 * Master Encoding
 *
 * assignment record    : question, location, data, user
 * master record        : question, location, responses
 * master response      : data, users
 * master response users: array
 * */

class MasterEncoding {
    static function merge( $assignment, &$masterEncoding ){
        $log = [];
        $log['headers']['Assignment'] = $assignment;
        $log['headers']['Report'] = $masterEncoding;
        $parsedAssignment = MasterEncoding::parseAssignment( $assignment );
        foreach ( $parsedAssignment as $i => $userRecord ){
            $masterRecord = &MasterEncoding::matchRecord( $userRecord, $masterEncoding );
            if( $masterRecord == self::$NO_MATCH ){
                MasterEncoding::record( $userRecord, $masterEncoding );
                $log[]["Added New Record"] = $userRecord;
                continue;
            }
            $masterResponse = &MasterEncoding::matchResponse( $userRecord, $masterRecord );
            if( $masterResponse == self::$NO_MATCH ){
                MasterEncoding::recordResponse( $userRecord, $masterRecord );
                $log[]['Recorded New Response'][] = [
                    "userRecord" => $userRecord,
                    "master" => $masterRecord
                ];
                continue;
            }
            MasterEncoding::recordResponseUser( $userRecord, $masterResponse, $log );
        }
        return $log;
    }

    static function parseAssignment( $assignment ){
        $output = [];
        foreach ( $assignment['encoding']['constants'] as $constant ){
            $output[] = [
                "user" => $assignment['_key'],
                "question" => $constant['question'],
                "location" => 0,
                "data" => $constant['data']
            ];
        }
        return $output;
    }
    static function &matchRecord( $userRecord, &$masterEncoding ){
        $question = $userRecord['question'];
        $location = $userRecord['location'];

        foreach ( $masterEncoding as &$masterRecord ){
            $masterQ = $masterRecord['question'];
            $masterL = $masterRecord['location'];

            if( $question == $masterQ && $location == $masterL ) return $masterRecord;
        }
        return self::$NO_MATCH;
    }
    static function &matchResponse( $userRecord, &$masterRecord ){
        $userData = $userRecord['data'];
        foreach ( $masterRecord['responses'] as &$masterResponse ){
            $masterData = $masterResponse['data'];
            if( $userData == $masterData ){
                return $masterResponse;
            }
        }
        return self::$NO_MATCH;
    }

    static function record( $userRecord, &$masterEncoding ){
        $masterEncoding[] = [
            "question" => $userRecord['question'],
            "location" => $userRecord['location'],
            "responses" => [
                [
                    "data" => $userRecord['data'],
                    "users" => [ $userRecord['user'] ]
                ]
            ]
        ];
    }
    static function recordResponse( $userRecord, &$masterRecord ){
        $userKey = $userRecord['user'];
        self::cleanup( $userKey, $masterRecord );
        $responseData = [
            "data" => $userRecord['data'],
            "users" => [ $userRecord['user'] ]
        ];
        $masterRecord['responses'][] = $responseData;
    }
    static function recordResponseUser( $userRecord, &$masterResponse, &$log = null ){
        $userKey = $userRecord['user'];
        if( !in_array($userKey, $masterResponse['users']) ){
            $masterResponse['users'][] = $userRecord['user'];
            if( $log !== null ){
                $log[]["Added User to Existing Response"] = [
                    "User Record" => $userRecord,
                    "Master Response" => $masterResponse
                ];
            }
        }
    }

    static function cleanup( $userKey, &$masterRecord, &$log = null ){
        for( $i = 0; $i < count($masterRecord['responses']); $i++){
            $response = &$masterRecord['responses'][$i];
            if( in_array($userKey, $response['users'])){
                $index = array_search( $userKey, $response['users']);
                array_splice($response['users'], $index, 1);

                if( $log !== null ){
                    $log[]["Changed User Answer"] = [
                        "User" => $userKey,
                        "Old Response" => $response
                    ];
                }
            }
            if( count( $response['users'] ) == 0 ){
                array_splice( $masterRecord['responses'], $i, 1);

                if( $log !== null ) {
                    $log[]["Deleted Empty Response"] = [
                        "Old Response" => $response
                    ];
                }
            }
        }
    }

    static $NO_MATCH = "";
}