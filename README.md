# Scientific Paper Encoding Software

After downloading, run: \
<code>$ composer update</code>

You will have to create <code>src/settings.php</code> before attempting to connect to an instance of ArangoDB

You may test your database connection with : \
<code>$ composer test-connection</code>

Initialize the database collections:    \
<code>$ php data/scripts/init_collections.php</code>

Run the server : \
<code>$ composer start</code>

Run all test cases : \
<code>$ composer test</code>

Run the ORM examples : \
<code>$ php data/scripts/examples/_______.php </code>