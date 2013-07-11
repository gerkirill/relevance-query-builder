# RelevanceQueryBuilder

Builds an SQL expression which adds a relevance value to the resulting rows.
Relevance is calculated against search string given. Relevance depends on the rules,
defined with addRelevanceRule(). In short - each rule may define some rate, which will be given
to the resulting record in a case keywords from the search string match some fields.
Query builder result usage example:

	SELECT *, <insert result here> as relevance
	FROM `posts`
	HAVING relevance > 0
	ORDER BY relevance DESC

The code below demonstrates usage for the following search results ranking requirements:

	fields  search_type  rate   description
	-----------------------------------------------------------------------------------------------------------------
	nav     MATCH_ANY    16     Results with a part of the search term in the navigation item (should be shown first)
	h1,h2   MATCH_ALL    8      Results containing the whole search search term in h1 or h2 of its content
	text    MATCH_ALL    4      Results containing the whole search search term in its text
	h1,h2   MATCH_ANY    2      Results containing a part of the search term in its h1 or h2 of its content
	text    MATCH_ANY    1      Results containing a part of the search term in its text

Here is the code:

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

NOTE: the examples use LIKE in generated SQL, but now query builder uses REGEXP instead by default.

The result would be :

	SELECT *, (`nav` LIKE '%want%' OR `nav` LIKE '%beer!%')*16
	+((`h1` LIKE '%want%' AND `h1` LIKE '%beer!%')
	OR (`h2` LIKE '%want%' AND `h2` LIKE '%beer!%'))*8
	+(`text` LIKE '%want%' AND `text` LIKE '%beer!%')*4
	+(`h1` LIKE '%want%' OR `h1` LIKE '%beer!%'
	OR `h2` LIKE '%want%' OR `h2` LIKE '%beer!%')*2
	+(`text` LIKE '%want%' OR `text` LIKE '%beer!%')*1 as relevance
	FROM  `posts`
	HAVING relevance > 0
	ORDER BY relevance DESC