<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Translators;

/**
 * Responsibility - basic CSV translator:
 *  - Not translated keys are writen into store when request ends.
 *  - Translation value could contains basic integer or string 
 *    Replacements in curly brackets.
 *  - Translation value could contains i18n ICU translation format.
 *  - Configurable location for CSV files.
 */
class		Csv
extends		\MvcCore\Ext\Translators\AbstractTranslator
implements	\MvcCore\Ext\ITranslator {
	
	/**
	 * MvcCore Extension - Translator - CSV - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.0';

	/**
	 * Relative path to directory with CSV translations, 
	 * relative to application root directory. 
	 * Default value is `~/Var/Translations`.
	 * @var string
	 */
	protected $dataDir = '~/Var/Translations';

	/**
	 * Configure relative path to directory with CSV translations, 
	 * relative to application root directory. 
	 * Default value is `/Var/Translations`.
	 * @param  string $dataDir
	 * @return string
	 */
	public function SetDataDir ($dataDir) {
		return $this->dataDir = $dataDir;
	}

	/**
	 * Return relative path to directory with CSV translations, 
	 * relative to application root directory.
	 * @return string
	 */
	public function GetDataDir () {
		return $this->dataDir;
	}
	
	/**
	 * Complete CSV store full path.
	 * @return string
	 */
	protected function getCsvStoreFullPath () {
		$dataDir = $this->dataDir;
		if (mb_substr($dataDir, 0, 2) === '~/') {
			$app = \MvcCore\Application::GetInstance();
			$dataDir = $app->GetRequest()->GetAppRoot() . mb_substr($dataDir, 1);
		}
		return $dataDir . '/' . $this->localization . '.csv';
	}

	/**
	 * Load and parse CSV translation store from HDD.
	 * @param  int|string|NULL Translation store id, optional.
	 * @throws \Exception
	 * @return array
	 */
	public function LoadStore ($id = NULL) {
		$store = [];
		$csvFullPath = $this->getCsvStoreFullPath();
		if (!file_exists($csvFullPath)) {
			if (!self::$writeTranslations)
				trigger_error("No translations found in path: `{$csvFullPath}`.", E_USER_NOTICE);
			return [];
		} else {
			$rawCsv = file_get_contents($csvFullPath);
			$rawCsvRows = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawCsv));
			foreach ($rawCsvRows as $rowKey => $rawCsvRow) {
				if (!trim($rawCsvRow)) continue;
				$keyAndValue = str_getcsv($rawCsvRow, ";", '');
				if (count($keyAndValue) === 1) throw new \Exception(
					"Missing translation - line: `{$rowKey}`, localization: `{$this->localization}`."
				);
				list($key, $value) = $keyAndValue;
				if (isset($store[$key])) {
					$rowKey += 1;
					self::thrownAnException(
						"Translation key already defined. "
						."(path: '{$csvFullPath}', row: '{$rowKey}', key: '{$key}')"
					);
				}
				$value = str_replace('\\n', "\n", $value);
				if ($this->detectI18nIcuTranslation($value)) {
					$store[$key] = [TRUE, new \MvcCore\Ext\Translators\IcuTranslation(
						$this->localization, $value
					)];
				} else {
					$store[$key] = [FALSE, $value];
				}
			}
		}
		return $store;
	}

	/**
	 * Append not translated keys into CSV store, for current 
	 * localization, after request is done, when browser connection 
	 * is closed, in `register_shutdown_function()` handler.
	 * @return void
	 */
	protected function writeTranslations () {
		$app = \MvcCore\Application::GetInstance();
		$toolsClass = $app->GetToolClass();

		$csvFullPath = $this->getCsvStoreFullPath();
		$rawContent = '';
		if (file_exists($csvFullPath)) {
			$rawContent = str_replace(["\r\n", "\r"], "\n", trim(file_get_contents($csvFullPath)));
		}

		$newItems = [];
		foreach (array_keys($this->newTranslations) as $newTranslation)
			$newItems[] = $newTranslation . ';' . static::NOT_TRANSLATED_KEY_MARK . $newTranslation;
		$separator = mb_strlen($rawContent) > 0 ? "\n" : "";
		
		$toolsClass::AtomicWrite(
			$csvFullPath, $rawContent . $separator . implode("\n", $newItems)
		);
	}
}
