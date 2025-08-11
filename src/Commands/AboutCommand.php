<?php

namespace Forge\Commands;

use Forge\Support\AppInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\render;

#[AsCommand(name: 'about', description: 'Display information about the application')]
class AboutCommand extends Command
{
	public function __construct(private readonly AppInfo $info)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if ($input->getOption('json')) {
			$output->writeln(json_encode($this->info->toArray(), JSON_PRETTY_PRINT));
			return Command::SUCCESS;
		}

		$data = $this->info->toArray();

		render(<<<HTML
            <div class="space-y-1">
                <div class="px-2 py-1 bg-green-600 text-white">
                    <span class="font-bold">{$this->e($data['name'])}</span>
                </div>

                <div class="px-2">
                    <div>Version: <span class="text-green">{$this->e($data['version'])}</span></div>
                    <div>Environment: <span class="text-blue">{$this->e($data['environment'])}</span></div>
                    <div>PHP: <span class="text-yellow">{$this->e($data['php'])}</span></div>
                    <div>Base path: <span class="text-gray-500">{$this->e($data['base_path'])}</span></div>
                </div>

                <div class="px-2">
                    <div>Author: <span class="font-bold">{$this->e($data['author'] ?? '—')}</span></div>
                    <div>Homepage: <a href="{$this->e($data['homepage'] ?? '#')}" class="underline">{$this->e($data['homepage'] ?? '—')}</a></div>
                </div>
            </div>
        HTML);

		return Command::SUCCESS;
	}

	private function e(?string $v): string
	{
		return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
	}
}