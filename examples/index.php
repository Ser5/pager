<?
require_once __DIR__.'/../src/S5/Pager/Page.php';
require_once __DIR__.'/../src/S5/Pager/Pager.php';
require_once __DIR__.'/../src/S5/Pager/PagerResult.php';
require_once __DIR__.'/../src/S5/Pager/Sequence.php';

use \S5\Pager\Pager;

session_start();



foreach (['items_amount', 'items_per_page', 'template'] as $fieldName) {
	if (isset($_POST[$fieldName])) {
		$_SESSION[$fieldName] = $_POST[$fieldName];
	}
}

if (!isset($_SESSION['items_amount']) or !ctype_digit((string)$_SESSION['items_amount'])) {
	$_SESSION['items_amount'] = 1000;
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



try {
	$p = Pager::calc([
		'items_amount'   => $_SESSION['items_amount'],
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
	<a class="pager_page <?=$cssClass?>" href="<?=$url?>"><?=$text?></a>
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
		$cssClass = 'pager_page_mGap';
	} elseif ($page->getNumber() != $p->getPageNumber()) {
		$cssClass = '';
	} else {
		$cssClass = 'pager_page_mActive';
	}
	$showPage($url, $text, $cssClass);
}?>



<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>Pager</title>
	<link rel="stylesheet" href="/styles.css">
</head>
<body>

	<form class="params" method="post">
		<div class="params_item">
			<div class="params_itemLabel">Количество элементов:</div>
			<div><input class="params_itemInput" type="text" name="items_amount" value="<?=@htmlspecialchars($_SESSION['items_amount'])?>"></div>
		</div>
		<div class="params_item">
			<div class="params_itemLabel">Элементов на страницу:</div>
			<div><input class="params_itemInput" type="text" name="items_per_page" value="<?=@htmlspecialchars($_SESSION['items_per_page'])?>"></div>
		</div>
		<div class="params_item">
			<div class="params_itemLabel">Шаблон постраничности:</div>
			<div><input class="params_itemInput" style="font-family:'Courier New'" type="text" name="template" value="<?=@htmlspecialchars($_SESSION['template'])?>"></div>
		</div>
		<div class="params_item">
			<div class="params_itemLabel">&nbsp;</div>
			<div><input type="submit" value="Go!"></div>
		</div>
	</form>

	<div class="pagerBlock">
		<div class="pager">
			<?if (!empty($p->getPrev())) {?>
				<div class="pager_part">
					<?$showButton($p->getFirst(), 'First')?>
					<?$showButton($p->getRew(),   '<<')?>
					<?$showButton($p->getPrev(),  '<')?>
				</div>
			<?}?>
			<div class="pager_part">
				<?foreach ($p->getSequence() as $page) {
					$showNumber($page);
				}?>
			</div>
			<?if (!empty($p->getNext())) {?>
				<div class="pager_part">
					<?$showButton($p->getNext(), '>')?>
					<?$showButton($p->getFF(),   '>>')?>
					<?$showButton($p->getLast(), 'Last')?>
				</div>
			<?}?>
		</div>
	</div>

</body>
</html>
