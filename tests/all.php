<?
require_once '../src/S5/Pager/Page.php';
require_once '../src/S5/Pager/Pager.php';
require_once '../src/S5/Pager/AmountPager.php';
require_once '../src/S5/Pager/PagerResult.php';
//require_once '../src/S5/Pager/Sequence.php';

use \S5\Pager\Pager;
?>

<?function getPage ($page, $string) {
	return $string.$page->getNumber().':'.$page->getUrl();
}?>
<?function getButton ($page, $string) {
	if (!empty($page)) {
		return getPage($page, $string);
	}
}?>
<?function getNumber ($p, $page) {
	if ($page->isGap()) {
		return '...';
	} elseif ($page->getNumber() == $p->getPageNumber()) {
		return '['.$page->getNumber().']';
	} else {
		return getPage($page, '');
	}
}?>
<?function getPagesString ($p, $isShowButtons = false) {
	$pageStringsList = [];
	if ($isShowButtons) {
		$pageStringsList[] = getButton($p->getFirst(), 'F');
		$pageStringsList[] = getButton($p->getRew(),   '<<');
		$pageStringsList[] = getButton($p->getPrev(),  '<');
	}
	foreach ($p->getSequence() as $page) {
		$pageStringsList[] = getNumber($p, $page);
	}
	if ($isShowButtons) {
		$pageStringsList[] = getButton($p->getNext(), '>');
		$pageStringsList[] = getButton($p->getFF(),   '>>');
		$pageStringsList[] = getButton($p->getLast(), 'L');
	}
	$pagesString = join(' ', $pageStringsList);
	$pagesString = preg_replace('/\s{2,}/', ' ', $pagesString);
	return $pagesString;
}?>

<?
function test ($expected, $got, $message = '') {
	echo ($expected == $got ? 'PASS' : 'FAIL'),'   ',$message,"\n";
	if ($expected != $got) {
		echo "$expected\n$got\n";
	}
}



$params = [
	'items_amount'   => 1000,
	'items_per_page' => 10,
	'template'       => '[3 2*2 3]',
	'page_number'    => 20,
	'linker'         => function ($number) { return $number; },
];

$p = Pager::calc($params);
test(
	'F1:1 <<15:15 <19:19 1:1 2:2 3:3 ... 18:18 19:19 [20] 21:21 22:22 ... 98:98 99:99 100:100 >21:21 >>25:25 L100:100',
	getPagesString($p, true),
	'Полная строка'
);

$p = Pager::calc($params);
test(
	'1:1 2:2 3:3 ... 18:18 19:19 [20] 21:21 22:22 ... 98:98 99:99 100:100',
	getPagesString($p),
	'Без кнопок'
);

$p = Pager::calc(['template' => '2*2'] + $params);
test(
	'18:18 19:19 [20] 21:21 22:22',
	getPagesString($p),
	'Только средний диапазон'
);

$p = Pager::calc(['template' => '[3 3]'] + $params);
test(
	'F1:1 1:1 2:2 3:3 ... 98:98 99:99 100:100 L100:100',
	getPagesString($p, true),
	'Без среднего диапазона, из кнопок видны только первая и последняя'
);

$p = Pager::calc(['items_amount' => 40, 'template' => '[3 3]'] + $params);
test(
	'1:1 2:2 3:3 [4]',
	getPagesString($p),
	'Начальный и конечный диапазоны пересекаются'
);

$p = Pager::calc(['page_number' => 1] + $params);
test(
	'[1] 2:2 3:3 4:4 5:5 ... 98:98 99:99 100:100',
	getPagesString($p),
	'Средний диапазон полностью покрывает начальный'
);

$p = Pager::calc(['page_number' => 4] + $params);
test(
	'1:1 2:2 3:3 [4] 5:5 6:6 ... 98:98 99:99 100:100',
	getPagesString($p),
	'Слияние диапазонов, видно часть начального и часть среднего'
);

$p = Pager::calc(['page_number' => 6] + $params);
test(
	'1:1 2:2 3:3 4:4 5:5 [6] 7:7 8:8 ... 98:98 99:99 100:100',
	getPagesString($p),
	'Слияние диапазонов, начальный примыкает к среднему'
);

$p = Pager::calc(['items_amount' => 110, 'page_number' => 6] + $params);
test(
	'1:1 2:2 3:3 4:4 5:5 [6] 7:7 8:8 9:9 10:10 11:11',
	getPagesString($p),
	'Страниц немного, все диапазоны примыкают друг к другу'
);

$p = Pager::calc(['items_amount' => 70, 'page_number' => 4] + $params);
test(
	'1:1 2:2 3:3 [4] 5:5 6:6 7:7',
	getPagesString($p),
	'Страниц недофига, от начального и конечного диапазонов видно только одну крайнюю страницу'
);

$p = Pager::calc(['items_amount' => 50, 'page_number' => 3] + $params);
test(
	'1:1 2:2 [3] 4:4 5:5',
	getPagesString($p),
	'Страниц меньше, видно только средний диапазон'
);

$p = Pager::calc(['items_amount' => 40, 'page_number' => 3] + $params);
test(
	'1:1 2:2 [3] 4:4',
	getPagesString($p),
	'Всего 4 страницы, от среднего диапазона видно 4 элемента вместо 5'
);

$p = Pager::calc(['items_amount' => 20, 'page_number' => 3] + $params);
test(
	'1:1 [2]',
	getPagesString($p),
	'Всего 2 страницы, от среднего диапазона видно 2 элемента, текущая страница становится 2-й'
);



$ctorParams = [
	'items_per_page' => $params['items_per_page'],
	'template'       => $params['template'],
	'linker'         => $params['linker'],
];

$expected = 'F1:1 <<15:15 <19:19 1:1 2:2 3:3 ... 18:18 19:19 [20] 21:21 22:22 ... 98:98 99:99 100:100 >21:21 >>25:25 L100:100';

$p1 = Pager::calc($params);
$p2 = (new Pager($ctorParams))->get(['items_amount' => $params['items_amount'], 'page_number' => $params['page_number']]);
$p3 = (new Pager($ctorParams))->forAmount($params['items_amount'])->get($params['page_number']);
echo
	(($expected == getPagesString($p1,true) and $expected == getPagesString($p2,true) and $expected == getPagesString($p3,true)) ? 'PASS' : 'FAIL'),
	"   все варианты получения постраничности выдают одинаковую строку\n"
;
