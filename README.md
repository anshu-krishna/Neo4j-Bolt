# Krishna-Neo4j-Bolt

PHP driver for Bolt protocol for communication with Neo4j graph database over TCP

## Installation:
```
composer require anshu-krishna/neo4j-bolt
```

## Requirements:
- PHP >= 8.1
- Neo4j (with Bolt version 4.4, 4.3, 4.2 or 4.1)

**Note: All examples use the Neo4j 'Example Movie Database'**

## Example:
```php
require_once "vendor/autoload.php";

use Krishna\Neo4j\{AuthToken, BoltMaker};
use Krishna\Neo4j\Protocol\Reply\Success;

// Create Bolt
$bolt = (new BoltMaker(
	auth: AuthToken::basic('neo4j', 'neo4j')
))->makeBolt();

echo 'Running Bolt version:', $bolt::VERSION, '<br>';

// Run Query
$run = $bolt->query(<<<CYPHER
match (p:Person)-[a:ACTED_IN]->(m:Movie)
return
	p as person,
	a.roles as roles,
	{title: m.title, year: m.released} as movie
limit 2
CYPHER);

if(!$run instanceof Success) {
	echo 'Query Error: ', $run->message;
	exit(0);
}

echo <<<HTML
<table border="1">
	<tr>
		<th>Person</th> <th>Born</th> <th>Role(s)</th> <th>Movie</th> <th>Released</th>
	</tr>
HTML;
	// Pull results
	foreach($bolt->pull() as $i) {
		$roles = implode(', ', $i->roles);
		echo <<<"ROW"
<tr>
	<td>{$i->person->properties['name']}</td>
	<td>{$i->person->properties['born']}</td>
	<td>{$roles}</td>
	<td>{$i->movie['title']}</td>
	<td>{$i->movie['year']}</td>
</tr>
ROW;
	}

echo '</table>';
```
**For specifc BOLT version:**

Use the `useVersion()` in `BoltMaker` to set upto 4 versions in order of preference.
```php
$bolt = (new BoltMaker(auth: AuthToken::basic('neo4j', 'neo4j')))
	->useVersion(E_Version::V4_2, E_Version::V4_1)
	->makeBolt();
var_dump($bolt::VERSION);
```

**Debug Logging:**

Pass a `Logger` object in `BoltMaker` for protocol debug logs.
```php
$bolt = (new BoltMaker(
	auth: AuthToken::basic('neo4j', 'neo4j'),
	logger: new Logger(rowSize: 20)
))->makeBolt();
```
Output:
```
Write [Handshake]:
60 60 b0 17  00 00 04 04  00 00 03 04  00 00 02 04  00 00 01 04

Read [Handshake]:
00 00 04 04

Write [Hello]:
00 01 b1 00  01 01 a4 8a  75 73 65 72  5f 61 67 65  6e 74 89 4b
42 6f 6c 74  2f 31 2e 30  86 73 63 68  65 6d 65 85  62 61 73 69
63 89 70 72  69 6e 63 69  70 61 6c 85  6e 65 6f 34  6a 8b 63 72
65 64 65 6e  74 69 61 6c  73 85 6e 65  6f 34 6a 00  00

Read [Hello = Success]:
b1 70 a3 86  73 65 72 76  65 72 8b 4e  65 6f 34 6a  2f 34 2e 34
2e 35 8d 63  6f 6e 6e 65  63 74 69 6f  6e 5f 69 64  87 62 6f 6c
74 2d 31 38  85 68 69 6e  74 73 a0

Write [Goodbye]:
00 01 b0 00  01 02 00 00
```

To start logging after creating a `Bolt` protocol:
```php
$bolt = (new BoltMaker(
	auth: AuthToken::basic('neo4j', 'neo4j')
))->makeBolt();

// Start logging
$bolt->logger = new Logger;

// Execute quries here

// Stop logging
$bolt->logger = null;
```

## Protocol functions:
- Run a query
	```php
	query(
		string $query,
		array $parameters = [],
		bool $autoResetOnFaiure = true,
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null
	): I_Reply;
	```
	
- Pull results
	```php
	pull(int $count = -1, int $qid = -1);
	```

- Disconnect
	```php
	disconnect(): void;
	```
	
- Reset connection
	```php
	reset(): I_Reply;
	```

- Start a transaction
	```php
	beginTransaction(
		array $bookmarks = [],
		int $tx_timeout = -1,
		?array $tx_metadata = null,
		bool $readMode = false,
		?string $db = null
	): I_Reply;
	```

- Commit transaction
	```php
	commit(): I_Reply;
	```

- Rollback transaction
	```php
	rollback(): I_Reply;
	```

- Get last query metadata
	```php
	getQueryMeta(): ?I_Reply;
	```

- Check if last query was valid
	```php
	queryValid(): bool;
	```

- Check if last query has more results
	```php
	moreResults(): bool;
	```

- Discard results
	```php
	discard(int $count = -1, int $qid = -1): ?I_Reply;
	```

**Only in `Bolt` >= 4.3**

- Send route message
	```php
	route(array $routing, array $bookmarks, ?string $db = null): I_Reply;
	```