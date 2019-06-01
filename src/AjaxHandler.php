<?php declare(strict_types=1);
namespace Frast;

abstract class AjaxHandler
{
	/** @var string */
	protected $handler;

	/** @var string */
	protected $assetSrc = '';

	/** @var array */
	protected $dependencies = [];

	/** @var string|bool|null */
	protected $version = false;

	/** @var bool */
	protected $inFooter = true;

	/** @var string The value of the nonce */
	private $nonce = '';

	/** @var string The name of the nonce variable (which will be used in js files) */
	private $nonceName = '';

	/** @var array Actions that the handler makes */
	private $actions = [];

	/**
	 * Get the name of the js file that will handle ajax requests
	 */
	abstract public function getAssetSrc(): string;

	/**
	 * How the handler will handle requests
	 */
	abstract public function treatment();

	/**
	 * Load handler and enqueue it for Wordpress
	 */
	public function load(): void
	{
		$this
			->setHandler()
			->setAssetSrc($this->getAssetSrc())
			->createNonce()
			->registerNonce()
			->enqueueActions()
			->enqueueScript()
		;
	}

	/**
	 * Check if the handler can be registered, override this method to add your own rules
	 */
	public function conditions(): bool
	{
		return true;
	}

	public function getActions(): array
	{
		return $this->actions;
	}

	protected function getHandler(): string
	{
		return $this->handler;
	}

	/**
	 * Set the name of the script handler, based on the class name
	 */
	protected function setHandler(): self
	{
		$this->handler = (new \ReflectionClass($this))->getShortName();
		return $this;
	}

	protected function setAssetSrc(string $assetSrc): self
	{
		$this->assetSrc = $assetSrc;
		return $this;
	}

	protected function getDependencies(): array
	{
		return $this->dependencies;
	}

	protected function setDependencies(array $dependencies): self
	{
		$this->dependencies = $dependencies;
		return $this;
	}

	protected function getVersion()
	{
		return $this->version;
	}

	protected function setVersion($version): self
	{
		$this->version = $version;
		return $this;
	}

	protected function getInFooter(): bool
	{
		return $this->inFooter;
	}

	protected function setInFooter(bool $inFooter): self
	{
		$this->inFooter = $inFooter;
		return $this;
	}

	protected function addAction(string $name, callable $callable): void
	{
		$this->actions[] = [
			'name' => $name,
			'callable' => $callable,
		];
	}

	private function enqueueActions(): self
	{
		/** @var callable */
		$callTreatment = function () {
			check_ajax_referer($this->nonceName, 'nonce');
			$this->treatment();
		};

		$this->addAction("wp_ajax_{$this->handler}", $callTreatment);
		$this->addAction("wp_ajax_nopriv_{$this->handler}", $callTreatment);

		return $this;
	}

	private function enqueueScript(): self
	{
		if ( !empty($this->assetSrc) )
		{
			$this->addAction('wp_enqueue_scripts', function () {
				wp_enqueue_script($this->handler, $this->assetSrc, $this->dependencies, $this->version, $this->inFooter);
			});
		}
		else
		{
			if ( !method_exists($this, 'javascript') )
			{
				throw new \RuntimeException("If you don't specify a source for your javascript file, then you must define a public function javascript() method that return your javascript that'll be injected.");
			}

			$this->addAction('wp_footer', function () {
				echo $this->javascript();
			}, 50);
		}

		return $this;
	}

	private function createNonce(): self
	{
		$this->nonceName = $this->handler . 'Nonce';
		$this->nonce = wp_create_nonce($this->nonceName);
		return $this;
	}

	private function registerNonce(): self
	{
		$this->addAction('wp_head', function () {
			echo "<script>var {$this->nonceName} = '{$this->nonce}';</script>";
		});
		
		return $this;
	}
}