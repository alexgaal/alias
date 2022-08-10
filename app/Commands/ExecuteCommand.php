<?php

namespace App\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class ExecuteCommand extends Command
{
    protected $signature = 'execute {alias?} {-q|--quiet}';

    protected $description = 'Executes an alias.';

    protected array $aliases = [];

    public function handle()
    {
        $alias = $this->argument('alias');
        $quiet = $this->option('quiet') ?? false;

        try {
            $this->getProjectAliases();

            if ($alias && !in_array($alias, array_keys($this->aliases))) {
                $this->components->error("Alias $alias not found.");
            }

            if (!$alias) {
                $alias = $this->choice('Please select alias to execute', array_keys($this->aliases));
            }

            $this->components->info("Executing: $alias");

            if (is_array($this->aliases[$alias]) === false) {
                $this->aliases[$alias] = [$this->aliases[$alias]];
            }

            foreach ($this->aliases[$alias] as $task) {
                if ($quiet) {
                    @shell_exec($task);
                } else {
                    shell_exec($task);
                }
            }

            $this->newLine();
            $this->components->info('Finished.');
        } catch (\Exception $exception) {
            $this->components->error($exception->getMessage());
        }
    }

    protected function getProjectAliases(): array
    {
        $file = match (true) {
            File::exists($path = getcwd() . '/aliases.dev.json') => File::get($path),
            File::exists($path = getcwd() . '/aliases.json') => File::get($path),
            default => throw new FileNotFoundException('aliases.json or aliases.dev.json not found.')
        };

        return $this->aliases = json_decode($file, true);
    }
}
