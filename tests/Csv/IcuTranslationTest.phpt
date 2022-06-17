<?php

include_once(__DIR__ . '/../bootstrap.php');

use \Tester\Assert;
use \MvcCore\Ext\Translators\IcuTranslation;

/**
 * @see run.cmd ./Csv/*
 */
class IcuTranslationTest extends \Tester\TestCase {

	protected $localization = 'cs_CZ';

	protected $name;
	protected $surname;
	protected $intNum;
	protected $floatNum;
	protected $percNum;
	protected $manyNum;
	protected $date;
	protected $time;
	protected $numSpace;

	/** before each test method */
	public function setUp () {
		$this->name = 'Igor';
		$this->surname = 'Hnízdo';
		$this->intNum = 1234567890;
		$this->floatNum = 1234.56789;
		$this->percNum = 0.123456789;
		$this->manyNum = intval(round(PHP_INT_MAX * 0.001));
		$this->date = new \DateTime();
		$this->date->setDate(1986,8,6);
		$this->time = new \DateTime();
		$this->time->setTime(20,30,40);
		$this->numSpace = html_entity_decode('&#x00A0;'); // non braking space
	}

	/** after each test method */
	public function tearDown () {
	}

	public function testSequenceIndexes () {
		$translation = new IcuTranslation(
			$this->localization,
			"Jmenuji se {0} {1}!"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate([
				$this->name, 
				$this->surname
			]),
			"Jmenuji se {$this->name} {$this->surname}!"
		);
	}
	public function testAssocIndexes () {
		$translation = new IcuTranslation(
			$this->localization,
			"Jmenuji se {name} {surname}!"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate([
				'name'		=> $this->name, 
				'surname'	=> $this->surname
			]),
			"Jmenuji se {$this->name} {$this->surname}!"
		);
	}
	public function testMixedIndexes () {
		$translation = new IcuTranslation(
			$this->localization,
			"Jmenuji se {0} {surname}!"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate([
				$this->name, 
				'surname'	=> $this->surname
			]),
			"Jmenuji se {$this->name} {$this->surname}!"
		);
	}
	public function testIntNum () {
		$translation = new IcuTranslation(
			$this->localization,
			"{i, number, integer}"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate(['i' => $this->intNum]),
			number_format($this->intNum, 0, '.', $this->numSpace)
		);
	}
	public function testFloatNum () {
		$translation = new IcuTranslation(
			$this->localization,
			"{f, number}"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate(['f' => $this->floatNum]),
			number_format($this->floatNum, 3, ',', $this->numSpace)
		);
	}
	public function testFloatPercentage () {
		$translation = new IcuTranslation(
			$this->localization,
			"{p, number, percent}"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate(['p' => $this->percNum]),
			number_format($this->percNum * 100.0, 0, ',', $this->numSpace) . $this->numSpace . "%"
		);
	}
	public function testDate () {
		$translation = new IcuTranslation(
			$this->localization,
			"{d, date}"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate(['d' => $this->date]),
			"6. 8. 1986"
		);
	}
	public function testTime () {
		$translation = new IcuTranslation(
			$this->localization,
			"{t, time, medium}"
		);
		$translation->Parse();
		Assert::equal(
			$translation->Translate(['t' => $this->time]),
			"20:30:40"
		);
	}
	public function testPlural () {
		$translation = new IcuTranslation(
			$this->localization,
			"{items, plural, ".
			"	zero	{Nebyly nalezeny žádné položky.}".
			"	one		{Byla nalezena jediná položka.}".
			"	few		{Byly nalezeny # položky.}".
			"	other	{Bylo nalezeno # položek.}".
			"}"
		);
		$translation->Parse();
		$formattedMessages = [
			$translation->Translate(['items' => 0])			,
			$translation->Translate(['items' => 1])			,
			$translation->Translate(['items' => 2])			,
			$translation->Translate(['items' => 3])			,
			$translation->Translate(['items' => 4])			,
			$translation->Translate(['items' => 5])			,
			$translation->Translate(['items' => $this->manyNum])	,
		];
		$expectedMessages = [
			"Nebyly nalezeny žádné položky.",
			"Byla nalezena jediná položka.",
			"Byly nalezeny 2 položky.",
			"Byly nalezeny 3 položky.",
			"Byly nalezeny 4 položky.",
			"Bylo nalezeno 5 položek.",
			"Bylo nalezeno " . number_format($this->manyNum, 0, '.', $this->numSpace) . " položek."
		];
		Assert::equal(
			serialize($formattedMessages),
			serialize($expectedMessages)
		);
	}
	public function testSelect () {
		$translation = new IcuTranslation(
			$this->localization,
			"{gender, select, ".
			"	male	{Muž mě pozval na párty.}".
			"	female	{Žena mě pozvala na párty.}".
			"	other	{Tamto ono mě pozvalo na párty.}".
			"}"
		);
		$translation->Parse();
		$formattedMessages = [
			$translation->Translate(['gender' => 'male'])	,
			$translation->Translate(['gender' => 'female']),
			$translation->Translate(['gender' => 'other'])	,
		];
		$expectedMessages = [
			"Muž mě pozval na párty.",
			"Žena mě pozvala na párty.",
			"Tamto ono mě pozvalo na párty.",
		];
		Assert::equal(
			serialize($formattedMessages),
			serialize($expectedMessages)
		);
	}
	public function testSelectOrdinal () {
		$translation = new IcuTranslation(
			'en_US',
			"{place, ordinal}"
		);
		$translation->Parse();
		$formattedMessages = [
			$translation->Translate(['place' => 1])	,
			$translation->Translate(['place' => 2])	,
			$translation->Translate(['place' => 3])	,
			$translation->Translate(['place' => 4])	,
			$translation->Translate(['place' => 5])	,
			$translation->Translate(['place' => 10]),
		];
		$expectedMessages = [
			"1st",
			"2nd",
			"3rd",
			"4th",
			"5th",
			"10th",
		];
		Assert::equal(
			serialize($formattedMessages),
			serialize($expectedMessages)
		);
	}
	public function testCompinations () {
		$pattern = <<<'TEXT'
{gender_of_host, select,
	female {
		{num_guests, plural, offset:1 
			=0 {{host} Nepořádala žádnou párty.}
			=1 {{host} pozvala {guest} na svou párty.}
			=2 {{host} pozvala {guest} and jednoho dalšího člověka na svou párty.}
			other {{host} pozvala {guest} and # další lidi na svou párty.}
		}
	}
	male {
		{num_guests, plural, offset:1 
			=0 {{host} Nepořádal žádnou párty.}
			=1 {{host} pozval {guest} na svou párty.}
			=2 {{host} pozval {guest} and jednoho dalšího člověka na svou párty.}
			other {{host} pozval {guest} and # další lidi na svou párty.}
		}
	}
	other {
		{num_guests, plural, offset:1 
			=0 {{host} Nepořádalo žádnou párty.}
			=1 {{host} pozvalo {guest} na svou párty.}
			=2 {{host} pozvalo {guest} and jednoho dalšího člověka na svou párty.}
			other {{host} pozval {guest} and # další lidi na svou párty.}
		}
	}
}
TEXT;
		$pattern = preg_replace("#\s+#", " ", $pattern);
		$pattern = str_replace(["} } } }","} } }","} }"], ["}}}}","}}}","}}"], $pattern);
		$translation = new IcuTranslation(
			$this->localization,
			$pattern
		);
		$translation->Parse();
		$formattedMessages = [
			$translation->Translate([
				'host'				=> 'Jiří',
				'gender_of_host'	=> 'male',
				'num_guests'		=> 1,
				'guest'				=> 'Jerryho',
			]),
			$translation->Translate([
				'host'				=> 'Jiří',
				'gender_of_host'	=> 'male',
				'num_guests'		=> 2,
				'guest'				=> 'Jerryho',
			]),
			$translation->Translate([
				'host'				=> 'Jiří',
				'gender_of_host'	=> 'male',
				'num_guests'		=> 3,
				'guest'				=> 'Jerryho',
			]),

			$translation->Translate([
				'host'				=> 'Jana',
				'gender_of_host'	=> 'female',
				'num_guests'		=> 1,
				'guest'				=> 'Radku',
			]),
			$translation->Translate([
				'host'				=> 'Tom',
				'gender_of_host'	=> 'female',
				'num_guests'		=> 2,
				'guest'				=> 'Radku',
			]),
			$translation->Translate([
				'host'				=> 'Tom',
				'gender_of_host'	=> 'female',
				'num_guests'		=> 3,
				'guest'				=> 'Radku',
			]),

			$translation->Translate([
				'host'				=> 'Nokol',
				'gender_of_host'	=> 'female',
				'num_guests'		=> 1,
				'guest'				=> 'Sašu',
			]),
			$translation->Translate([
				'host'				=> 'Nokol',
				'gender_of_host'	=> 'female',
				'num_guests'		=> 2,
				'guest'				=> 'Sašu',
			]),
			$translation->Translate([
				'host'				=> 'Nokol',
				'gender_of_host'	=> 'female',
				'num_guests'		=> 3,
				'guest'				=> 'Sašu',
			]),
		];
		$expectedMessages = [
			" Jiří pozval Jerryho na svou párty.",
			" Jiří pozval Jerryho and jednoho dalšího člověka na svou párty.",
			" Jiří pozval Jerryho and 2 další lidi na svou párty.",
			" Jana pozvala Radku na svou párty.",
			" Tom pozvala Radku and jednoho dalšího člověka na svou párty.",
			" Tom pozvala Radku and 2 další lidi na svou párty.",
			" Nokol pozvala Sašu na svou párty.",
			" Nokol pozvala Sašu and jednoho dalšího člověka na svou párty.",
			" Nokol pozvala Sašu and 2 další lidi na svou párty.",
		];
		Assert::equal(
			serialize($formattedMessages),
			serialize($expectedMessages)
		);
	}
}

run(function () {
	(new IcuTranslationTest)->run();
});