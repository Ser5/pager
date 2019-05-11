<?
namespace S5\Pager;

class Pager {
	protected static $defaultItemsPerPage  = 10;
	protected static $defaultTemplate      = '4*5';
	protected static $defaultPageNumber    = 1;
	protected static $defaultLinker        = false;

	protected $params;

	/**
	 * Ctor.
	 *
	 * $params:
	 * - items_per_page
	 * - template
	 * - linker
	 *
	 * @param array $params
	 */
	function __construct ($params = []) {
		$params = static::checkItemsPerPage($params);
		$params = static::checkTemplate($params);
		$params = static::checkLinker($params);
		$this->params = $params;
	}



	/**
	 * Получение данных постраничности.
	 *
	 * $params
	 * - items_amount
	 * - page_number
	 *
	 * @param  array $params
	 * @return PagerResult
	 */
	public function get ($params) {
		return static::calc($params + $this->params);
	}



	/**
	 * Возвращает объект, содержащий данные о количестве элементов, далее может использоваться для получения постраничности с любой активной страницей.
	 *
	 * @param  int $itemsAmount
	 * @return AmountPager
	 */
	public function forAmount ($itemsAmount) {
		return new AmountPager($this->params + ['items_amount' => $itemsAmount]);
	}



	/*
	 * Расчёт данных постраничности с произвольными параметрами.
	 *
	 * $params:
	 * - items_amount
	 * - items_per_page
	 * - template
	 * - linker
	 * - page_number
	 *
	 * @return PagerResult
	 */
	public static function calc ($params) {
		$p = $params;

		//Проверка параметров
		$p = static::checkItemsAmount($p);
		$p = static::checkItemsPerPage($p);
		$p = static::checkTemplate($p);
		$p = static::checkLinker($p);
		$p = static::checkPageNumber($p);

		$originalPageNumber = $p['page_number'];
		$itemsAmount        = $p['items_amount'];
		$itemsPerPage       = $p['items_per_page'];
		$pageNumber         = $p['page_number'];
		$linker             = $p['linker'];

		$itemsFrom = $itemsTo = 0;
		$pagesAmount = $pagesWindowWidth = 0;
		$firstPage = $rewPage = $prevPage = null;
		$nextPage  = $ffPage  = $lastPage = null;

		if ($itemsAmount) {
			//Количество страниц
			$pagesAmount = (int)ceil($itemsAmount / $itemsPerPage);

			//Корректировка номера текущей страницы
			if (!ctype_digit((string)$pageNumber)) {
				$pageNumber = 1;
			} elseif ($pageNumber < 1) {
				$pageNumber = 1;
			} elseif ($pageNumber > $pagesAmount) {
				$pageNumber = $pagesAmount;
			}

			//С какой по какую запись будет происходить вывод
			$itemsFrom = ($itemsPerPage * ($pageNumber - 1));
			$itemsTo   = min($itemsAmount - 1, ($itemsPerPage * $pageNumber) - 1);

			//Сборка диапазонов страниц исходя из того, что указано в шаблоне
			$rangeStringsList = preg_split('/\s+/', $p['template']);
			$isMiddleRange    = false;
			$pagesWindowWidth = false;
			$rangesList       = [];
			$matches          = [];
			foreach ($rangeStringsList as $rangeString) {
				if (preg_match('/^\[(\d*)$/', $rangeString, $matches)) {
					if (count($matches) < 2) {
						$rangesList[] = [1, 1];
					} else {
						$rangesList[] = [1, $matches[1]];
					}
				} elseif (preg_match('/^(\d*)\]$/', $rangeString, $matches)) {
					if (count($matches) < 2) {
						$rangesList[] = [$pagesAmount, $pagesAmount];
					} else {
						$rangesList[] = [$pagesAmount-$matches[1]+1, $pagesAmount];
					}
				} elseif (preg_match('/^(\d*)\*(\d*)$/', $rangeString, $matches)) {
					$isMiddleRange    = true;
					$range            = [];
					$pagesWindowWidth = 1;
					if ($matches[1]) {
						$range[]           = $pageNumber-$matches[1];
						$pagesWindowWidth += $matches[1];
					} else {
						$range[] = $pageNumber;
					}
					if ($matches[2]) {
						$range[]           = $pageNumber+$matches[2];
						$pagesWindowWidth += $matches[2];
					} else {
						$range[] = $pageNumber;
					}
					//Сдвигаем диапазон, если он не лезет в окно
					if ($range[0] < 1) {
						$range[1] += (1 - $range[0]);
						$range[0] = 1;
					} elseif ($range[1] > $pagesAmount) {
						$range[0] -= ($range[1] - $pagesAmount);
						$range[1] = $pagesAmount;
					}
					$rangesList[] = $range;
				}
			}

			//Чиним вылезание за края
			foreach ($rangesList as &$range) {
				$range = [max(1,$range[0]), min($pagesAmount,$range[1])];
			}
			unset($range);

			//Слияние пересекающихся диапазонов страниц
			do {
				$isRestart    = false;
				$rangesAmount = count($rangesList);
				if ($rangesAmount > 1) {
					for ($a = 0; $a < $rangesAmount-1; $a++) {
						for ($b = $a+1; $b < $rangesAmount; $b++) {
							$range1 = &$rangesList[$a];
							$range2 = $rangesList[$b];
							if (
								($range2[0] >= $range1[0] and $range2[0] <= $range1[1] + 1) or
								($range1[0] >= $range2[0] and $range1[0] <= $range2[1] + 1)
							) {
								$range1[0] = min($range1[0], $range2[0]);
								$isRestart = true;
							}
							if (
								($range2[1] >= $range1[0] - 1 and $range2[1] <= $range1[1]) or
								($range1[1] >= $range2[0] - 1 and $range1[1] <= $range2[1])
							) {
								$range1[1] = max($range1[1], $range2[1]);
								$isRestart = true;
							}
							if ($isRestart) {
								array_splice($rangesList, $b, 1);
								break 2;
							}
						}
					}
				}
			} while ($isRestart);

			//Сборка массива с номерами страниц
			$sequence = [];
			if ($isMiddleRange) {}
			for ($rangeIx = 0; $rangeIx < $rangesAmount; $rangeIx++) {
				for ($n = $rangesList[$rangeIx][0]; $n <= $rangesList[$rangeIx][1]; $n++) {
					$sequence[] = new Page('number', $n, $linker);
				}
				if ($rangeIx < $rangesAmount - 1) {
					$sequence[] = new Page('gap');
				}
			}

			//Сборка кнопок
			if ($pageNumber > 1) {
				$firstPage = new Page('first', 1, $linker);
				if ($pagesWindowWidth) {
					$rewPage  = new Page('rew', max(1, $pageNumber - $pagesWindowWidth), $linker);
					$prevPage = new Page('prev', $pageNumber - 1, $linker);
				}
			}
			if ($pageNumber < $pagesAmount) {
				if ($pagesWindowWidth) {
					$nextPage = new Page('next', $pageNumber + 1, $linker);
					$ffPage   = new Page('ff',   min($pagesAmount, $pageNumber + $pagesWindowWidth), $linker);
				}
				$lastPage = new Page('last', $pagesAmount, $linker);
			}
		}

		$pagerResult = new PagerResult(
			$itemsAmount,
			$itemsPerPage,
			$originalPageNumber,
			$pageNumber,
			$linker,
			$itemsFrom, $itemsTo,
			$pagesAmount, $pagesWindowWidth,
			$firstPage, $rewPage, $prevPage, $sequence, $nextPage, $ffPage, $lastPage
		);

		//Готово
		return $pagerResult;
	}



	protected static function checkItemsPerPage ($p) {
		if (!isset($p['items_per_page'])) {
			$p['items_per_page'] = static::$defaultItemsPerPage;
		} else {
			if (!ctype_digit((string)$p['items_per_page'])) {
				throw new \InvalidArgumentException("items_per_page: expected int, got [$p[items_per_page]]");
			}
			if ($p['items_per_page'] < 1) {
				throw new \InvalidArgumentException("items_per_page: expected >=0, got [$p[items_amount]]");
			}
		}
		return $p;
	}

	protected static function checkItemsAmount ($p) {
		if (!isset($p['items_amount'])) {
			throw new \InvalidArgumentException("items_amount not set");
		}
		if (!ctype_digit((string)$p['items_amount'])) {
			throw new \InvalidArgumentException("items_amount: expected int, got [$p[items_amount]]");
		}
		if ($p['items_amount'] < 0) {
			$p['items_amount'] = 0;
		}
		return $p;
	}

	protected static function checkPageNumber ($p) {
		if (!$p['page_number']) {
			$p['page_number'] = static::$defaultPageNumber;
		} else {
			if (!is_callable($p['page_number'])) {
				$p['page_number'] = $p['page_number'];
			} else {
				$p['page_number'] = call_user_func($p['page_number']);
			}
		}
		return $p;
	}

	protected static function checkTemplate ($p) {
		if (!isset($p['template'])) {
			$p['template'] = static::$defaultTemplate;
		} else {
			$testTemplate = $p['template'];
			$testTemplate = preg_replace(
				['/\[\d*/', '/\d*\*\d*/', '/\d*\]/'],
				'',
				$testTemplate
			);
			if (strlen(str_replace(' ', '', $testTemplate)) > 0) {
				throw new \InvalidArgumentException("template contains unrecognized characters: [$p[template]]");
			}
		}
		return $p;
	}

	protected static function checkLinker ($p) {
		if (!isset($p['linker'])) {
			$p['linker'] = static::$defaultLinker;
		} else {
			if (!is_callable($p['linker'])) {
				throw new \InvalidArgumentException("linker: expected callable, got [$p[linker]]");
			}
		}
		return $p;
	}
}
