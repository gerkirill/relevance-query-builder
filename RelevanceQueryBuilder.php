<?php
/**
* Builds an SQL expression which adds a relevance value to the resulting rows.
* Relevance is calculated agains search string given. Relevance depends on the rules,
* defined with addRelevanceRule(). In short - each rule may define some rate, which will be given
* to the resulting record in a case keywords from the search string match some fields.
* Query builder result usage example:
* SELECT *, <insert result here> as relevance
* FROM `posts`
* HAVING relevance > 0
* ORDER BY relevance DESC
*
* Look into tests folder for more details on usage.
*/
class RelevanceQueryBuilder
{
	private $relevanceRules = array();
	private $minKeywordLength = 3;
	private $stopWords = array();

	public function addRelevanceRule(RelevanceRule $relevanceRule)
	{
		$this->relevanceRules[] = $relevanceRule;
	}

	public function setMinKeywordLength($length)
	{
		$this->minKeywordLength = $length;
	}

	public function setStopWords($stopWords)
	{
		$this->stopWords = $stopWords;
	}

	public function getRelevanceFieldDefinition($searchString)
	{
		// break search string into keywords
		$keywords = $this->breakIntoKeywords($searchString);
		// remove stop words
		$filteredKeywords = $this->removeStopWords($keywords);
		// prepare keywords for use in LIKE %...% query
		$sqlPreparedKeywords = $this->prepareKeywordsForLikeQuery($filteredKeywords);
		// build SQL for each relevance rule
		$relevanceSqlParts = array();
		foreach($this->relevanceRules as $relevanceRule)
		{
			$relevanceSqlParts[] = $relevanceRule->getSql($sqlPreparedKeywords);
		}
		// join these SQLs with "+" and return
		$relevanceFieldSql = implode("\n+", $relevanceSqlParts);
		return $relevanceFieldSql;
	}
	//======================================

	private function breakIntoKeywords($searchString)
	{
		$keywords = preg_split('%\s%', $searchString, -1, PREG_SPLIT_NO_EMPTY);
		$keywords = array_unique($keywords);
		return $keywords;
	}

	private function removeStopWords($keywords)
	{
		$filteredKeywords = array();
		foreach($keywords as $keyword)
		{
			if (in_array($keyword, $this->stopWords)) continue;
			if (strlen($keyword) < $this->minKeywordLength) continue;
			$filteredKeywords[] = $keyword;
		}
		return $filteredKeywords;
	}

	private function prepareKeywordsForLikeQuery($keywords)
	{
		foreach($keywords as &$keyword)
		{
			$keyword = mysql_escape_string($keyword);
			$keyword = strtr($keyword, array('%'=>'\%', '_'=>'\_'));
		}
		return $keywords;
	}

}