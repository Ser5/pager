<?
require_once __DIR__.'/../src/S5/Pager/Page.php';
require_once __DIR__.'/../src/S5/Pager/Pager.php';
require_once __DIR__.'/../src/S5/Pager/AmountPager.php';
require_once __DIR__.'/../src/S5/Pager/PagerResult.php';

use \S5\Pager\Pager;



class Data {
	private static $_c             = ['b','c','d','f','g','h','j','k','l','m','n','p','s','t','v'];
	private static $_v             = ['a','e','i','o','u'];
	private static $_syllablesList = false;
	private static $_syllablesMax  = false;

	private $_itemsAmount = false;
	//private $_itemsList   = [];

	public function __construct ($itemsAmount) {
		if (!static::$_syllablesList) {
			foreach (['b','c','d','f','g','h','j','k','l','m','n','p','s','t','v'] as $c) {
				foreach (['a','e','i','o','u'] as $v) {
					static::$_syllablesList[] = "$c$v";
				}
			}
			static::$_syllablesMax = count(static::$_c) * count(static::$_v) - 1;
		}
		mt_srand(1);
		$this->_itemsAmount = $itemsAmount;
	}

	public function getList ($from, $amount) {
		$to       = $from + $amount - 1;
		$itemsMax = $this->_itemsAmount - 1;
		if ($from < 0 or $from > $itemsMax or $amount < 1 or $to > $itemsMax) {
			throw new \InvalidArgumentException("[$this->_itemsAmount], [$from], [$amount]");
		}
		for ($id = 1; $id < $from; $id++) {
			$this->_getText();
		}
		$list = [];
		for ($id = $from; $id <= $to; $id++) {
			$list[] = ['id' => $id+1, 'text' => $this->_getText()];
		}
		return $list;
	}

	public function getAmount () {
		return $this->_itemsAmount;
	}

	private function _getText () {
		$text        = '';
		$wordsAmount = mt_rand(5, 10);
		for ($a = 0; $a < $wordsAmount; $a++) {
			$syllablesAmount = mt_rand(1, 5);
			for ($b = 0; $b < $syllablesAmount; $b++) {
				$text .= static::$_syllablesList[mt_rand(0, static::$_syllablesMax)];
			}
			$text .= ' ';
		}
		$text = substr($text, 0, -1) . '.';
		$text = ucfirst($text);
		return $text;
	}
}



session_start();



foreach (['items_amount', 'items_per_page', 'template'] as $fieldName) {
	if (isset($_POST[$fieldName])) {
		$_SESSION[$fieldName] = $_POST[$fieldName];
	}
}

if (!isset($_SESSION['items_amount']) or !ctype_digit((string)$_SESSION['items_amount'])) {
	$_SESSION['items_amount'] = 1000;
}
if ($_SESSION['items_amount'] > 5000) {
	$_SESSION['items_amount'] = 5000;
}
if (!isset($_SESSION['items_per_page']) or !ctype_digit((string)$_SESSION['items_per_page'])) {
	$_SESSION['items_per_page'] = 10;
}
if (!isset($_SESSION['template']) or !trim($_SESSION['template'])) {
	$_SESSION['template'] = '[3 4*5 3]';
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	header("Location: /");
	exit();
}



$data = new Data($_SESSION['items_amount']);



try {
	$p = Pager::calc([
		'items_amount'   => $data->getAmount(),
		'items_per_page' => $_SESSION['items_per_page'],
		'template'       => $_SESSION['template'],
		'page_number'    => (isset($_GET['page']) ? $_GET['page'] : 20),
		'linker'         => function ($number) { return "?page=$number"; }
	]);
} catch (\Exception $e) {
	$_SESSION = [];
	header("Location: /");
	exit();
}
?>

<?$showPage = function ($url, $text, $cssClass = '') {?>
	<a class="pager__page <?=$cssClass?>" href="<?=$url?>"><?=$text?></a>
<?}?>
<?$showButton = function ($page, $text) use ($showPage) {
	if (!empty($page)) {
		$showPage($page->getUrl(), $text);
	}
}?>
<?$showNumber = function ($page) use ($p, $showPage) {
	$url  = $page->getUrl();
	$text = $page->getNumber();
	if ($page->isGap()) {
		$url      = '';
		$text     = '&hellip;';
		$cssClass = 'pager__page_gap';
	} elseif ($page->getNumber() != $p->getPageNumber()) {
		$cssClass = '';
	} else {
		$cssClass = 'pager__page_active';
	}
	$showPage($url, $text, $cssClass);
}?>
<?$showPager = function () use ($p, $showButton, $showNumber) {?>
	<div>Элементы: <?=$p->getItemsFrom()+1?>-<?=$p->getItemsTo()+1?></div>
	<div class="pager">
		<?if (!empty($p->getPrev())) {?>
			<div class="pager__part">
				<?$showButton($p->getFirst(), 'First')?>
				<?$showButton($p->getRew(),   '<<')?>
				<?$showButton($p->getPrev(),  '<')?>
			</div>
		<?}?>
		<div class="pager__part">
			<?foreach ($p->getSequence() as $page) {
				$showNumber($page);
			}?>
		</div>
		<?if (!empty($p->getNext())) {?>
			<div class="pager__part">
				<?$showButton($p->getNext(), '>')?>
				<?$showButton($p->getFF(),   '>>')?>
				<?$showButton($p->getLast(), 'Last')?>
			</div>
		<?}?>
	</div>
<?}?>



<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>Pager</title>
	<link rel="stylesheet" href="/styles.css">
</head>
<body>

	<form class="params" method="post">
		<div class="params__item">
			<div class="params__item-label">Количество элементов:</div>
			<div><input class="params__item-input" type="text" name="items_amount" value="<?=@htmlspecialchars($_SESSION['items_amount'])?>"></div>
		</div>
		<div class="params__item">
			<div class="params__item-label">Элементов на страницу:</div>
			<div><input class="params__item-input" type="text" name="items_per_page" value="<?=@htmlspecialchars($_SESSION['items_per_page'])?>"></div>
		</div>
		<div class="params__item">
			<div class="params__item-label">Шаблон постраничности:</div>
			<div><input class="params__item-input" style="font-family:'Courier New'" type="text" name="template" value="<?=@htmlspecialchars($_SESSION['template'])?>"></div>
		</div>
		<div class="params__item">
			<div class="params__item-label">&nbsp;</div>
			<div><input type="submit" value="Go!"></div>
		</div>
	</form>

	<div class="pager-block">
		<?$showPager()?>
	</div>

	<div class="pager-block">
		<?foreach ($data->getList($p->getLimit()[0], $p->getLimit()[1]) as $itemData) {?>
			<div><?=$itemData['id']?>: <?=$itemData['text']?></div>
		<?}?>
	</div>

	<div class="pager-block">
		<?$showPager()?>
	</div>

</body>
</html>
