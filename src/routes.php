<?php
// set up some aliases for less typing later
use ArangoDBClient\Collection as ArangoCollection;
use ArangoDBClient\CollectionHandler as ArangoCollectionHandler;
use ArangoDBClient\Connection as ArangoConnection;
use ArangoDBClient\ConnectionOptions as ArangoConnectionOptions;
use ArangoDBClient\DocumentHandler as ArangoDocumentHandler;
use ArangoDBClient\Document as ArangoDocument;
use ArangoDBClient\Exception as ArangoException;
use ArangoDBClient\Export as ArangoExport;
use ArangoDBClient\ConnectException as ArangoConnectException;
use ArangoDBClient\ClientException as ArangoClientException;
use ArangoDBClient\ServerException as ArangoServerException;
use ArangoDBClient\Statement as ArangoStatement;
use ArangoDBClient\UpdatePolicy as ArangoUpdatePolicy;

// Routes

// This is a control test route to make sure the server is running properly
$app->get('/hello/{name}', function ($request, $response, $args) {
    $response->getBody()->write($args['name']);
});

// This route is authenticated using middleware
$app->get('/secure', function ($request, $response, $args) {
    echo var_dump($this->DB->query("SELECT * FROM users"));
    return;
});

$app->get('/test/arangodb', function ($request, $response, $args) {
    $documentHandler = new ArangoDocumentHandler($this->arangodb_connection);
    $user = new ArangoDocument();
    $user->set("email", rand(0,100)."@gmail.com");
    $user->set("age", rand(0,90));
    $user->scopes = ["admin", "manager", "user"];
    $userID = $documentHandler->save('created_with_php', $user);
    $result = $documentHandler->has("created_with_php", $userID);
    echo var_dump($result);
    $response->getBody()->write($result);
    return;
});

/**
 * POST loginPost
 * Summary: Issues a Login Token
 * Notes:	Check!
 * Output-Formats: [application/json]
 */
$app->POST('/login', function ($request, $response, $args) {
    $formData = $request->getParams();
    $email = $formData['email'];
    $password = $formData['password'];
    // Query the credentials
    $account = $this->DB->queryFirstRow("SELECT * FROM users WHERE email=%s AND password=%s", $email, $password);
    // Check if the account exist
    if(count($account) == 0){
        $ResponseToken = [
            "status" => "INVALID"
        ];
        $response->write(json_encode($ResponseToken));
        return $response;
    }
    // If we made it here, the credentials are good, and we have the account object
    $userDetails = [
        "ID" => $account["ID"],
        "name" => $account["name"],
        "email" => $account["email"]
    ];
    // Building the JWT
    $tokenId    = base64_encode(mcrypt_create_iv(32));
    $issuedAt   = time();
    $expire     = $issuedAt + 60*30;
    $data = [
        'iat'  => $issuedAt,         // Issued at: time when the token was generated
        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
        'iss'  => "My App",       // Issuer
        'exp'  => $expire,           // Expire
        'data' => [                  // Data related to the signer user
            'userId'   => $account["ID"], // userid from the users table
            'userEmail' => $account["name"], // User name
        ]
    ];
    $token = $this->JWT->encode($data, $this->get("settings")['JWT_secret']);
    // Building the response object
    $ResponseToken = [
        "status" => "OK",
        "token" => $token,
        "user" => $userDetails
    ];
    $response->write(json_encode($ResponseToken, JSON_PRETTY_PRINT));
    return $response;
});


/**
 * POST registerPost
 * Summary: Registers a user
 * Notes:	Check!
 * Output-Formats: [application/json]
 */
$app->POST('/register', function ($request, $response, $args) {
    $formData = $request->getParams();
    // Check if the email is already registered
    $emailCount = $this->DB->query("SELECT * FROM users WHERE email=%s", $formData['email']);
    if(count($emailCount) > 0){
        $responseDetails = [
            "status" => "EXIST",
            "msg" => "Account with that email already exist"
        ];
        $response->write(json_encode($responseDetails, JSON_PRETTY_PRINT));
        return $response;
    }
    // Insert user into the DB
    $this->DB->insert("users", $formData);
    $responseDetails = [
        "status" => "OK",
        "msg" => "Account created. You may now login."
    ];
	
	if($formData['roleID'] === 1){ // If they are a student
		// enroll them in their class
		$this->DB->insert("enrollments", [
			"studentID" => $this->DB->insertId(),
			"classID" => $formData['classID']
		]);
	}
	
    $response->write(json_encode($responseDetails, JSON_PRETTY_PRINT));
    return $response;
});