<?
namespace S5\Pager;

class AmountPager {
	protected $params;

	public function __construct ($params) {
		$this->params = $params;
	}



	/*
	 * @param int|callback $pageNumber
	 * @return \S5\Pager\PagerResult
	 */
	public function get ($pageNumber) {
		return Pager::calc(['page_number' => $pageNumber] + $this->params);
	}
}
