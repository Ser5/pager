<?
namespace S5\Pager;

class PagerResult {
	protected $originalPageNumber;
	protected $pageNumber;

	protected $pagesAmount;
	protected $itemsAmount;
	protected $sequencePartsAmount;

	protected $pagesList;

	protected $first;
	protected $rew;
	protected $prev;
	protected $sequence;
	protected $sequenceChunksList;
	protected $next;
	protected $ff;
	protected $last;

	public function __construct (
		$itemsAmount,
		$itemsPerPage,
		$originalPageNumber,
		$pageNumber,
		$linker,
		$pagesAmount,
		$first, $rew, $prev, $sequence, $next, $ff, $last
	) {
		$this->originalPageNumber = $originalPageNumber;
		$this->pageNumber         = $pageNumber;

		$this->pagesAmount = $pagesAmount;
		$this->itemsAmount = $itemsAmount;
		//$this->sequencePartsAmount = ;

		$this->first    = $first;
		$this->rew      = $rew;
		$this->prev     = $prev;
		$this->sequence = $sequence;
		$this->next     = $next;
		$this->ff       = $ff;
		$this->last     = $last;

		//$this->sequenceChunksList = ;

		$this->pagesList = $sequence;
		array_unshift($this->pagesList, $first, $rew, $prev);
		array_push($this->pagesList, $next, $ff, $last);
	}



	/**
	 * @return mixed
	 */
	public function getOriginalPageNumber () {
		return $this->originalPageNumber;
	}

	/**
	 * @return int
	 */
	public function getPageNumber () {
		return $this->pageNumber;
	}

	/**
	 * @return bool
	 */
	public function isPageNumberFixed () {
		return ($this->originalPageNumber != $this->pageNumber);
	}

	/**
	 * @return int
	 */
	public function countPages () {
		return $this->pagesAmount;
	}

	/**
	 * @return int
	 */
	public function countItems () {
		return $this->itemsAmount;
	}

	/*public function countSequenceParts () {
		return $this->originalPageNumber;
	}*/

	/**
	 * @return \S5\Pager\Page[]
	 */
	public function getPagesList () {
		return $this->pagesList;
	}

	/**
	 * @return \S5\Pager\Page
	 */
	public function getFirst () {
		return $this->first;
	}

	/**
	 * @return \S5\Pager\Page
	 */
	public function getRew () {
		return $this->rew;
	}

	/**
	 * @return \S5\Pager\Page
	 */
	public function getPrev () {
		return $this->prev;
	}

	/**
	 * @return \S5\Pager\Page[]
	 */
	public function getSequence () {
		return $this->sequence;
	}

	/*public function getSequenceChunksList () {
		return $this->originalPageNumber;
	}*/

	/**
	 * @return \S5\Pager\Page
	 */
	public function getNext () {
		return $this->next;
	}

	/**
	 * @return \S5\Pager\Page
	 */
	public function getFF () {
		return $this->ff;
	}

	/**
	 * @return \S5\Pager\Page
	 */
	public function getLast () {
		return $this->last;
	}
}
