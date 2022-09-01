<?php
require_once "vendor/autoload.php";

use Krishna\Neo4j\{AuthToken, BoltMaker};
use Krishna\Neo4j\Protocol\Reply\Success;

// Create Bolt
$bolt = (new BoltMaker(auth: AuthToken::basic('neo4j', 'open')))->makeBolt();

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