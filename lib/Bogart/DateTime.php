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
}
