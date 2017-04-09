<?php
// Application middleware

$app->add(new \Slim\Middleware\JwtAuthentication([
	"path" => ["/"],
	"secure" => false,   // For development only
    "passthrough" => ["/users/login", "/users/register", "/hello", "/conflictscan"],
    "secret" => $app->getContainer()->get("settings")['JWT_secret'],
]));