<?php

namespace Bogart;

class DateTime extends \DateTime
{
  
  const
    MINUTE = 60,
    HOUR = 3600,
    DAY = 86400,
    MONTH = 2592000,
    YEAR = 31536000;

	/**
	 * Return Date in ISO8601 format
	 *
	 * @return String
	 */
	public function __toString() {
		return $this->format('Y-m-d H:i');
	}

	/**
	 * Return difference between $this and $now
	 * 
	 * @requires PHP 5.3
	 * @param Datetime|String $now
	 * @return DateInterval
	 */
	public function diff($now = 'NOW') {
		if(!($now instanceOf DateTime)) {
			$now = new DateTime($now);
		}
		return parent::diff($now); // requires PHP 5.3
	}

	/**
	 * Return Age in Years
	 * 
	 * @param Datetime|String $now
	 * @return Integer
	 */
	public function getAge($now = 'NOW') {
		return $this->diff($now)->format('%y');
	}

	public function getTimestamp(){
		return $this->format("U");
	}

	/**
	 *    This function calculates the number of days between the first and the second date. Arguments must be subclasses of DateTime
	 **/
	static function differenceInDays (DateTime $firstDate, DateTime $secondDate){
		$firstDateTimeStamp = $firstDate->format("U");
		$secondDateTimeStamp = $secondDate->format("U");
		$rv = round ((($firstDateTimeStamp - $secondDateTimeStamp))/86400);
		return $rv;
	}

	/**
	 * This function returns an object of DateClass from $time in format $format. See date() for possible values for $format
	 **/
	static function createFromFormat ($format, $time){
		assert ($format!="");
		if($time==""){
			return new self();
		}

		$regexpArray['Y'] = "(?P<Y>19|20\d\d)";
		$regexpArray['m'] = "(?P<m>0[1-9]|1[012])";
		$regexpArray['d'] = "(?P<d>0[1-9]|[12][0-9]|3[01])";
		$regexpArray['-'] = "[-]";
		$regexpArray['.'] = "[\. /.]";
		$regexpArray[':'] = "[:]";
		$regexpArray['space'] = "[\s]";
		$regexpArray['H'] = "(?P<H>0[0-9]|1[0-9]|2[0-3])";
		$regexpArray['i'] = "(?P<i>[0-5][0-9])";
		$regexpArray['s'] = "(?P<s>[0-5][0-9])";

		$formatArray = str_split ($format);
		$regex = "";

		// create the regular expression
		foreach($formatArray as $character){
			if ($character==" ") $regex = $regex.$regexpArray['space'];
			elseif (array_key_exists($character, $regexpArray)) $regex = $regex.$regexpArray[$character];
		}
		$regex = "/".$regex."/";

		// get results for regualar expression
		preg_match ($regex, $time, $result);

		// create the init string for the new DateTime
		$initString = $result['Y']."-".$result['m']."-".$result['d'];

		// if no value for hours, minutes and seconds was found add 00:00:00
		if (isset($result['H'])) $initString = $initString." ".$result['H'].":".$result['i'].":".$result['s'];
		else {$initString = $initString." 00:00:00";}

		$newDate = new self ($initString);
		return $newDate;
	}
}
