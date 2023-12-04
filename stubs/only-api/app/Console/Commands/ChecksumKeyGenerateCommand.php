<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class ChecksumKeyGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checksum:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the checksum key';

    /**
     * Execute the console command.
     *
     * @return void | string
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            $this->components->info($key);
            return;
        }

        // Next, we will replace the checksum key in the environment file so it is
        // automatically setup for this developer. This key gets generated using a
        // pseudo-random byte generator with length of 16 digit and is later bin2hex encoded for storage.
        if (!$this->setKeyInEnvironmentFile($key)) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $this->laravel['config']['app.checksum'] = $key;

        $this->components->info('Checksum key set successfully.');
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Set the checksum key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['app.checksum'];

        if (strlen($currentKey) !== 0 && (!$this->confirmToProceed())) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        if (!$this->writeNewEnvironmentFileWith($key)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return bool
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $replaced = preg_replace(
            $this->keyReplacementPattern(),
            'CHECKSUM_KEY=' . $key,
            $input = file_get_contents($this->laravel->environmentFilePath())
        );

        if ($replaced === $input || $replaced === null) {
            // @codeCoverageIgnoreStart
            $this->error('Unable to set checksum key. No CHECKSUM_KEY variable was found in the .env file.');

            return false;
            // @codeCoverageIgnoreEnd
        }

        file_put_contents($this->laravel->environmentFilePath(), $replaced);

        return true;
    }

    /**
     * Get a regex pattern that will match env CHECKSUM_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPattern()
    {
        $escaped = preg_quote('=' . $this->laravel['config']['app.checksum'], '/');

        return "/^CHECKSUM_KEY{$escaped}/m";
    }
}
