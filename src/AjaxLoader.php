<?php declare(strict_types=1);
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
		
		foreach ( $this->handlers as $handler )
		{
			$handler = new $handler();
			$handler->load();
		
			foreach ( $handler->getActions() as $action )
			{
				$name = $action['name'];
				$callable = $action['callable'];
				
				// If it's an wp_ajax action, we add it
				if ( substr($name, 0, 7) === "wp_ajax" )
				{
					add_action($name, $callable);
				}
				// If it's something else, we check if the handler is authorized to load from the hook "wp"
				// That way it gives the user the capacity to use functions like is_page() or is_home()
				else
				{
					add_action('wp', function () use ($handler, $name, $callable) {
						if ( $handler->conditions() ) add_action($name, $callable);
					});
				}
			}
		}
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