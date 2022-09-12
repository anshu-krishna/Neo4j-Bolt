<?php
require_once "vendor/autoload.php";

use Krishna\Neo4j\{AuthToken, BoltMaker, Logger};
use Krishna\Neo4j\Protocol\Reply\Success;

$showLogs = array_key_exists('log', $_GET);
if(!$showLogs) { echo '<h3>Note: Add <u>?log</u> to the GET request to see the socket logs.</h3>'; }

// Create Bolt
$bolt = (new BoltMaker(
	auth: AuthToken::basic('neo4j', 'open'),
	logger: $showLogs ? new Logger(): null
))->makeBolt();

echo '<strong>Running Bolt version:', $bolt::VERSION, '</strong><br>';

$query = <<<CYPHER
match (p:Person)-[a:ACTED_IN]->(m:Movie)
return
	p as person,
	a.roles as roles,
	{title: m.title, year: m.released} as movie
limit 2
CYPHER;
$bolt->logger?->log(str_replace("\r", '', $query), 'Cypher Query');

// Run Query
$run = $bolt->query($query);

if(!$run instanceof Success) {
	echo 'Query Error: ', $run->message;
	exit(0);
}

$table = [<<<HTML
<table border="1">
<thead><tr><th>Person</th> <th>Born</th> <th>Role(s)</th> <th>Movie</th> <th>Released</th></tr></thead>
<tbody>
HTML];
	// Pull results
	foreach($bolt->pull() as $i) {
		$roles = implode(', ', $i->roles);
		$table[] = <<<"ROW"
<tr>
<td>{$i->person->properties['name']}</td>
<td>{$i->person->properties['born']}</td>
<td>{$roles}</td>
<td>{$i->movie['title']}</td>
<td>{$i->movie['year']}</td>
</tr>
ROW;
	}

$table[] = '</tbody></table>';

echo implode('', $table);