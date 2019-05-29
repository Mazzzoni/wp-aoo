<?php
namespace Frast;

class AjaxLoader
{
	/** @var array */
	private $handlers = [];

	/**
	 * Loop through all the handlers to load them
	 */
	public function load(): void
	{
		$this->addAdminAjaxUrl();

		foreach ( $this->handlers as $handler ) { (new $handler())->load(); }
	}

	/**
	 * Register an handler to be called later
	 */
	public function register(string $handler): self
	{
		$this->handlers[] = $handler;
		return $this;
	}

	/**
	 * Make the admin ajax url globally available for our js scripts
	 */
	private function addAdminAjaxUrl(): void
	{
		add_action('wp_head', function () {
			$adminAjax = admin_url('admin-ajax.php');
			echo "<script>var WP_ADMIN_AJAX = '{$adminAjax}';</script>";
		});
	}
}