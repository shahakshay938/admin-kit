<?php

namespace Tutus\Adminkit\Console;

use Illuminate\Filesystem\Filesystem;

trait InstallsApiStack
{
    /**
     * Install the API Breeze stack.
     *
     * @return int|null
     */
    protected function installOnlyApiStack()
    {
        $files = new Filesystem;

        // Installing Packages
        if (!$this->requireComposerPackages(['knuckleswtf/scribe'], true)) {
            return false;
        }

        // .gitignore
        $files->append(base_path(".gitignore"), "coverage" . PHP_EOL . "scribe" . PHP_EOL . "!scribe/");

        // Tests...
        copy(__DIR__.'/../../stubs/only-api/tests/Feature/WebRouteTest.php', base_path('tests/Feature/WebRouteTest.php'));
        $files->ensureDirectoryExists(base_path('tests/Feature/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/tests/Feature/Auth', base_path('tests/Feature/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/tests/Feature/Common', base_path('tests/Feature/Common'));

        // Stub
        $files->ensureDirectoryExists(base_path('stubs'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/stubs', base_path('stubs'));

        // Routes...
        copy(__DIR__.'/../../stubs/only-api/routes/web.php', base_path('routes/web.php'));
        $files->delete(base_path('routes/api.php'));
        $files->ensureDirectoryExists(base_path('routes/api'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/routes/api', base_path('routes/api'));

        // Routes...
        copy(__DIR__.'/../../stubs/only-api/database/factories/UserFactory.php', database_path('factories/UserFactory.php'));
        copy(__DIR__.'/../../stubs/only-api/database/migrations/2014_10_12_000000_create_users_table.php', database_path('migrations/2014_10_12_000000_create_users_table.php'));

        // Configs...
        $this->replaceInFile(
            "'cipher' => 'AES-256-CBC',",
            "'cipher' => 'AES-256-CBC'," . PHP_EOL . PHP_EOL . "    /*" . PHP_EOL . "    |--------------------------------------------------------------------------" . PHP_EOL . "    | Checksum Key" . PHP_EOL . "    |--------------------------------------------------------------------------" . PHP_EOL . "    |" . PHP_EOL . "    | This key is used by the API service and should be set" . PHP_EOL . "    | to a random, 32 character string, otherwise these encrypted strings" . PHP_EOL . "    | will not be safe. Please do this before deploying an application!" . PHP_EOL . "    |" . PHP_EOL . "    */" . PHP_EOL . PHP_EOL . "    'checksum' => env('CHECKSUM_KEY')," . PHP_EOL . PHP_EOL . "    'timeout' => env('CHECKSUM_TIMEOUT', 600), // in seconds",
            config_path('app.php')
        );
        $this->replaceInFile(
            "App\Providers\RouteServiceProvider::class,",
            "App\Providers\RouteServiceProvider::class," . PHP_EOL . "        App\Providers\ResponseMacroServiceProvider::class,",
            config_path('app.php')
        );

        $this->replaceInFile(
            "'type' => 'static',",
            "'type' => 'laravel',",
            config_path('scribe.php')
        );

        $this->replaceInFile(
            "'middleware' => [],",
            "'middleware' => ['docs'],",
            config_path('scribe.php')
        );

        $this->replaceInFile(
            "*/" . PHP_EOL . "        'enabled' => true,",
            "*/" . PHP_EOL . "        'enabled' => false,",
            config_path('scribe.php')
        );

        $this->replaceInFile(
            "* Set this to true if any endpoints in your API use authentication." . PHP_EOL . "         */" . PHP_EOL . "        'enabled' => false,",
            "* Set this to true if any endpoints in your API use authentication." . PHP_EOL . "         */" . PHP_EOL . "        'enabled' => true,",
            config_path('scribe.php')
        );

        $this->replaceInFile(
            "'javascript',",
            "'php'," . PHP_EOL . "        'javascript'," . PHP_EOL . "        'python',",
            config_path('scribe.php')
        );

        $this->replaceInFile(
            "'openapi' => [" . PHP_EOL . "        'enabled' => true,",
            "'openapi' => [" . PHP_EOL . "        'enabled' => false,",
            config_path('scribe.php')
        );

        $this->replaceInFile(
            "'order' => [",
            "'order' => [" . PHP_EOL . "            'Common APIs'," . PHP_EOL . "            'Authentication',",
            config_path('scribe.php')
        );
        copy(__DIR__.'/../../stubs/only-api/config/utility.php', config_path('utility.php'));

        // Traits...
        $files->ensureDirectoryExists(app_path('Traits'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/app/Traits', app_path('Traits'));

        // Rules...
        $files->ensureDirectoryExists(app_path('Rules'));
        copy(__DIR__.'/../../stubs/only-api/app/Rules/ValidatePasswordWithEmail.php', app_path('Rules/ValidatePasswordWithEmail.php'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/app/Rules/Api', app_path('Rules/Api'));

        // Providers...
        copy(__DIR__.'/../../stubs/only-api/app/Providers/ResponseMacroServiceProvider.php', app_path('Providers/ResponseMacroServiceProvider.php'));
        copy(__DIR__.'/../../stubs/only-api/app/Providers/RouteServiceProvider.php', app_path('Providers/RouteServiceProvider.php'));

        // Model
        copy(__DIR__.'/../../stubs/only-api/app/Models/User.php', app_path('Models/User.php'));

        // Http Kernal
        $this->replaceInFile(
            "'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,",
            "'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,".PHP_EOL."        'docs'  =>  \App\Http\Middleware\ApiDocsMiddleware::class,",
            app_path('Http/kernel.php')
        );

        // Model Resource
        $files->ensureDirectoryExists(app_path('Http/Resources/Auth/V1'));
        copy(__DIR__.'/../../stubs/only-api/app/Http/Resources/Auth/V1/ProfileResource.php', app_path('Http/Resources/Auth/V1/ProfileResource.php'));

        // Requests...
        $files->ensureDirectoryExists(app_path('Http/Requests/Api/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/only-api/app/Http/Requests/Api/Auth', app_path('Http/Requests/Api/Auth'));

        // Middlewares...
        copy(__DIR__.'/../../stubs/only-api/app/Http/Middleware/AlwaysAcceptJson.php', app_path('Http/Middleware/AlwaysAcceptJson.php'));
        copy(__DIR__.'/../../stubs/only-api/app/Http/Middleware/ApiDocsMiddleware.php', app_path('Http/Middleware/ApiDocsMiddleware.php'));

        // Controllers...
        $files->ensureDirectoryExists(app_path('Http/Controllers/Api/Auth/V1'));
        $files->ensureDirectoryExists(app_path('Http/Controllers/Api/Common/V1'));
        copy(__DIR__.'/../../stubs/only-api/app/Http/Controllers/Api/Auth/V1/AuthenticationController.php', app_path('Http/Controllers/Api/Auth/V1/AuthenticationController.php'));
        copy(__DIR__.'/../../stubs/only-api/app/Http/Controllers/Api/Common/V1/GeneralController.php', app_path('Http/Controllers/Api/Common/V1/GeneralController.php'));

        // Publish laravel's default language files
        $this->callSilent('lang:publish');
        $this->replaceInFile(
            "'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',",
            "'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',".PHP_EOL."    'logout' => ':Entity logged out successfully.',",
            base_path('lang/en/auth.php')
        );
        $this->replaceInFile(
            "'uuid' => 'The :attribute field must be a valid UUID.',",
            "'uuid' => 'The :attribute field must be a valid UUID.',".PHP_EOL.PHP_EOL."    'checksum' => [".PHP_EOL."        'invalid' => 'Invalid checksum or failed to validate checksum.',".PHP_EOL."    ],",
            base_path('lang/en/validation.php')
        );

        // Commands...
        $files->ensureDirectoryExists(app_path('Console/Commands'));
        copy(__DIR__.'/../../stubs/only-api/app/Console/Commands/ChecksumKeyGenerateCommand.php', app_path('Console/Commands/ChecksumKeyGenerateCommand.php'));

        // .env.example
        $files->append(
            base_path('.env.example'),
            PHP_EOL."# Checksum Details --------------------------------------------------------------------------------".PHP_EOL."# To generate new key use `php artisan checksum:generate` command".PHP_EOL."CHECKSUM_KEY=".PHP_EOL."CHECKSUM_TIMEOUT=600".PHP_EOL.PHP_EOL."# Utility Parameters -------------------------------------------------------------------------------".PHP_EOL.'SANCTUM_TOKEN_NAME="${APP_NAME}"'.PHP_EOL.'API_AUTH_TOKEN_NAME="Authorization Token"'.PHP_EOL."API_DOCS_ALLOWED_IPS="
        );

        // Environment...
        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        $files->append(
            base_path('.env'),
            PHP_EOL."# Checksum Details --------------------------------------------------------------------------------".PHP_EOL."# To generate new key use `php artisan checksum:generate` command".PHP_EOL."CHECKSUM_KEY=".PHP_EOL."CHECKSUM_TIMEOUT=600".PHP_EOL.PHP_EOL."# Utility Parameters -------------------------------------------------------------------------------".PHP_EOL.'SANCTUM_TOKEN_NAME="${APP_NAME}"'.PHP_EOL.'API_AUTH_TOKEN_NAME="Authorization Token"'.PHP_EOL."API_DOCS_ALLOWED_IPS="
        );

        $this->components->info('API stack installed successfully.');

        $this->newLine();
    }

    /**
     * Remove any application scaffolding that isn't needed for APIs.
     *
     * @return void
     */
    protected function removeScaffoldingUnnecessaryForApis()
    {
        $files = new Filesystem;

        // Remove frontend related files...
        $files->delete(base_path('package.json'));
        $files->delete(base_path('vite.config.js'));

        // Remove Laravel "welcome" view...
        $files->delete(resource_path('views/welcome.blade.php'));
        $files->put(resource_path('views/.gitkeep'), PHP_EOL);

        // Remove CSS and JavaScript directories...
        $files->deleteDirectory(resource_path('css'));
        $files->deleteDirectory(resource_path('js'));
    }
}
