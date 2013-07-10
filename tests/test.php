#!/usr/bin/env php
<?php
/*
The test below demonstrates usage for the following search results ranking requirements:

fields  search_type  rate   description
-----------------------------------------------------------------------------------------------------------------
nav     MATCH_ANY    16     Results with a part of the search term in the navigation item (should be shown first)
h1,h2   MATCH_ALL    8      Results containing the whole search search term in h1 or h2 of its content
text    MATCH_ALL    4      Results containing the whole search search term in its text
h1,h2   MATCH_ANY    2      Results containing a part of the search term in its h1 or h2 of its content
text    MATCH_ANY    1      Results containing a part of the search term in its text
*/

require('../RelevanceQueryBuilder.php');
require('../RelevanceRule.php');

$b = new RelevanceQueryBuilder();

$b->addRelevanceRule(new RelevanceRule(
	array('nav'), RelevanceRule::MATCH_ANY, 16
));
$b->addRelevanceRule(new RelevanceRule(
	array('h1', 'h2'), RelevanceRule::MATCH_ALL, 8
));
$b->addRelevanceRule(new RelevanceRule(
	array('text'), RelevanceRule::MATCH_ALL, 4
));
$b->addRelevanceRule(new RelevanceRule(
	array('h1', 'h2'), RelevanceRule::MATCH_ANY, 2
));
$b->addRelevanceRule(new RelevanceRule(
	array('text'), RelevanceRule::MATCH_ANY, 1
));

$expression = $b->getRelevanceFieldDefinition('I want my beer!');

$sql = "
SELECT *, %expression% as relevance
FROM  `posts`
HAVING relevance > 0
ORDER BY relevance DESC";

$parsedSql = strtr($sql, array('%expression%' => $expression));

echo $parsedSql;