<?php
class RelevanceRule
{
	const MATCH_ANY = 0;
	const MATCH_ALL = 1;

	private $fields = array();
	private $escapedFields = array();
	/** @var self::MATCH_ANY or self::MATCH_ALL */
	private $matchType;
	/** @var boolean if true - REGEXP '[[:<:]]word[[:>:]]' is used instead of LIKE '%word%' */
	private $useRegexp = true;
	private $rate;

	public function __construct(array $fields, $matchType, $rate)
	{
		$this->fields = $fields;
		$this->matchType = $matchType;
		$this->rate = $rate;
		foreach($fields as $field)
		{
			$this->escapedFields[] = $this->escaseField($field);
		}
	}

	public function useRegexp()
	{
		$this->useRegexp = true;
	}

	public function useLike()
	{
		$this->useRegexp = false;
	}

	public function getSql($sqlPreparedKeywords)
	{
		// get SQL for each field, depending on the $this->matchType
		$fieldsSql = array();
		foreach($this->escapedFields as $escapedField)
		{
			$fieldSql = $this->getFieldSql($escapedField, $sqlPreparedKeywords);
			/*
			if there are mutiple fields to match against, and match type is MATCH_ALL - each field SQL should be 
			surrounded with parentheses, as in this case we have both OR and AND expressions:
			(`h1` LIKE '%word1%' AND `h1` LIKE '%word2!%') OR (`h2` LIKE '%word1%' AND `h2` LIKE '%word2!%')
			In other cases - parantheses around field SQL are not required, the whole rule SQL will be wrapped
			with them anyway later on in this function, when multiplier is added. Example:
			`h1` LIKE '%word1%' AND `h1` LIKE '%word2!%' <- single field with MATCH_ALL
			`h1` LIKE '%word1%' OR `h1` LIKE '%word2!%' OR `h2` LIKE '%word1%' OR `h2` LIKE '%word2!%' <- multiple fields with MATCH_ANY
			*/
			if (self::MATCH_ALL == $this->matchType && count($this->escapedFields) > 1)
			{
				$fieldSql = '(' . $fieldSql . ')';
			}
			$fieldsSql[] = $fieldSql;
		}
		// implode SQLs with OR
		$sql = implode("\nOR ", $fieldsSql);

		// add rate as multiplier and return
		$sql = "($sql)*".$this->rate;
		return $sql;
	}
	// =================================
	private function getFieldSql($escapedField, $sqlPreparedKeywords)
	{
		switch($this->matchType)
		{
			case self::MATCH_ANY:
				$glue = ' OR ';
			break;
			case self::MATCH_ALL:
				$glue = ' AND ';
			break;
			default:
				throw new RuntimeException('Improper match type supplied.');
			break;
		}
		$likeParts = array();
		foreach($sqlPreparedKeywords as $sqlPreparedKeyword)
		{
			if ($this->useRegexp)
			{
				//TODO: regexp requires better escaping
				$likeParts[] = "$escapedField REGEXP '[[:<:]]{$sqlPreparedKeyword}[[:>:]]'";
			}
			else
			{
				$likeParts[] = "$escapedField LIKE '%$sqlPreparedKeyword%'";
			}
		}
		$fieldSql = implode($glue, $likeParts);
		return $fieldSql;
	}

	private function escaseField($field)
	{
		$fieldParts = explode('.', $field);
		foreach($fieldParts as &$fieldPart)
		{
			$fieldPart = mysql_escape_string($fieldPart);
			$fieldPart = "`$fieldPart`";
		}
		$fieldEscaped = implode('.', $fieldParts);
		return $fieldEscaped;
	}
}