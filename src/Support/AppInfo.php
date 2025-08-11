<?php

namespace Forge\Support;

final class AppInfo
{
	public function __construct(
		private readonly string $basePath,
		private readonly array $config = []  // optional app-level config
	) {}

	public function name(): string
	{
		return $this->config['name'] ?? $this->composer('name') ?? 'App';
	}

	public function version(): string
	{
		if (!empty($this->config['version'])) return (string)$this->config['version'];
		if ($v = $this->composer('version'))  return (string)$v;
		if ($hash = $this->gitShortHash())    return 'dev-'.$hash;
		return 'dev';
	}

	public function author(): ?string
	{
		if (!empty($this->config['author'])) return (string)$this->config['author'];
		$authors = $this->composer('authors') ?? [];
		return is_array($authors) && isset($authors[0]['name']) ? (string)$authors[0]['name'] : null;
	}

	public function homepage(): ?string
	{
		return $this->config['homepage'] ?? $this->composer('homepage') ?? null;
	}

	public function environment(): string
	{
		return $this->config['env'] ?? getenv('APP_ENV') ?: 'production';
	}

	public function phpVersion(): string
	{
		return PHP_VERSION;
	}

	public function basePath(): string
	{
		return $this->basePath;
	}

	public function toArray(): array
	{
		return [
			'name'        => $this->name(),
			'version'     => $this->version(),
			'author'      => $this->author(),
			'homepage'    => $this->homepage(),
			'environment' => $this->environment(),
			'php'         => $this->phpVersion(),
			'base_path'   => $this->basePath(),
		];
	}

	private function composer(?string $key = null)
	{
		$file = $this->basePath.'/composer.json';
		if (!is_file($file)) return null;
		$json = json_decode((string)file_get_contents($file), true) ?: [];
		return $key ? ($json[$key] ?? null) : $json;
	}

	private function gitShortHash(): ?string
	{
		if (!is_dir($this->basePath.'/.git')) return null;
		$hash = @exec('git -C '.escapeshellarg($this->basePath).' rev-parse --short HEAD');
		return $hash ? trim($hash) : null;
	}
}