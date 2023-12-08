<?php

namespace Tutus\Adminkit\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\select;
use function Laravel\Prompts\confirm;

class InstallCommand extends Command
{
    use InstallsApiStack;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adminkit:install {kit? : The starter kit that should be installed (only-api)}
                            {--confirm : Confirm Kit Installation Anyway}
                            {--composer=global : Absolute path to the Composer binary which should be used to install packages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the controllers and resources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $confirmInstall = $this->option('confirm') ?: confirm(
            label: "Make sure you're running this command on fresh laravel project, it will replace and modify your files.",
        );

        if (!$confirmInstall) {
            $this->components->warn('Nothing was installed.');
            return 1;
        }

        if($this->argument('kit') === 'api-only') {
            $stack = 'api-only';
        } else {
            $stack = select(
                label: 'Which stack would you like to install?',
                options: [
                    'api-only' => 'API only',
                    'admin-only' => 'Admin only',
                    'admin-api' => 'Admin + API',
                ]
            );
        }

        if ($stack === "api-only") {
            $this->installOnlyApiStack();
        }

        if ($stack === 'admin-only' || $stack === 'admin-api') {
            $this->components->alert('This kit is not available at the moment. Please check back in the future.');
            $this->newLine();
            return 1;
        }

        return 1;
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $path
     * @return void
     */
    protected function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Installs the given Composer Packages into the application.
     *
     * @param  array  $packages
     * @param  bool  $asDev
     * @return bool
     */
    protected function requireComposerPackages(array $packages, $asDev = false)
    {
        $composer = $this->option('composer');

        if ($composer !== 'global') {
            $command = ['php', $composer, 'require'];
        }

        $command = array_merge(
            $command ?? ['composer', 'require'],
            $packages,
            $asDev ? ['--dev'] : [],
        );

        return (new Process($command, base_path(), ['COMPOSER_MEMORY_LIMIT' => '-1']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            }) === 0;
    }
}
