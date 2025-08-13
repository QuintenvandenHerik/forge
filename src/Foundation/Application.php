<?php

declare(strict_types=1);

namespace Forge\Foundation;

use Closure;
use Forge\Contracts\Foundation\Application as ApplicationContract;
use Forge\Support\Facades\Facade;
use Forge\Container\Container;

class Application extends Container implements ApplicationContract {
	/**
	 * The base path for the Laravel installation.
	 *
	 * @var string
	 */
	protected string $basePath;
	
	public function __construct($basePath = null) {
		if ($basePath) {
			$this->setBasePath($basePath);
		}
		
		$this->bootstrap();
	}
	
	/**
	 * Bootstrap the given application.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		Facade::clearResolvedInstances();
		
		Facade::setFacadeApplication($this);
	}
	
	/**
	 * Set the base path for the application.
	 *
	 * @param string $basePath
	 *
	 * @return $this
	 */
	public function setBasePath(string $basePath): static {
		$this->basePath = rtrim($basePath, '\/');
		
//		$this->bindPathsInContainer();
		
		return $this;
	}
}