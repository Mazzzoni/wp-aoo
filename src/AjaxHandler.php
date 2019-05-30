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

	/**
	 * Get the name of the js file that will handle ajax requests
	 */
	abstract static public function getAssetSrc(): string;

	/**
	 * How the handler will handle requests
	 */
	abstract public function treatment();

	/**
	 * Load handler and enqueue it for Wordpress
	 */
	public function load(): void
	{
		if ( $this->conditions() === false ) return;

		$this
			->setHandler()
			->setAssetSrc(static::getAssetSrc())
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

	private function enqueueActions(): self
	{
		add_action("wp_ajax_{$this->handler}", [$this, 'treatment']);
		add_action("wp_ajax_nopriv_{$this->handler}", [$this, 'treatment']);

		return $this;
	}

	private function enqueueScript(): self
	{
		add_action('wp_enqueue_scripts', function () {
			wp_enqueue_script($this->handler, $this->assetSrc, $this->dependencies, $this->version, $this->inFooter);
		});

		return $this;
	}
}