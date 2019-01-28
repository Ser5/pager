<?
namespace S5\Pager;

class Page {
	protected $type;
	protected $number;
	protected $url;

	public function __construct ($type, $number = false, $linker = false) {
		//var_dump($linker);
		$this->type = $type;
		if ($number !== false) {
			$this->number = $number;
			if ($linker !== false) {
				$this->url = $linker($number);
				//echo '<pre>'; var_dump($this->url); echo '</pre>'; exit();
			}
		}
	}

	public function getNumber () { return $this->number; }

	public function getUrl () { return $this->url; }

	public function isButton () {
		static $buttonCodesHash = ['first', 'rew', 'prev', 'next', 'ff', 'last'];
		return isset($buttonCodesHash[$this->type]);
	}

	public function isSequence () { return $this->type == 'number'; }

	public function isFirst () { return $this->type == 'first'; }

	public function isRew () { return $this->type == 'rew'; }

	public function isPrev () { return $this->type == 'prev'; }

	public function isNext () { return $this->type == 'next'; }

	public function isFF () { return $this->type == 'ff'; }

	public function isLast () { return $this->type == 'last'; }

	public function isGap () { return $this->type == 'gap'; }

	public function isNumber () { return $this->type == 'number'; }
}
