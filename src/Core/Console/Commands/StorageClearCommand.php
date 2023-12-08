<?php

namespace Tutus\Adminkit\Core\Console\Commands;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use function Laravel\Prompts\select;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\ConfirmableTrait;
use function Laravel\Prompts\multiselect;

class StorageClearCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clear {--disk=* : Specify the disk(s) to delete files from}
                {--all : Removes all files from application}
                {--extra : Removes only files that not used by application}
                {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all files and folders from local and public storage disk.';

    /**
     * The files to be ignored.
     *
     * @var array
     */
    protected $ignore_files = ['.gitignore', 'public/.gitignore'];

    /**
     * The directories to be ignored.
     *
     * @var array
     */
    protected $ignore_directories = ['public'];

    /**
     * File extensions.
     *
     * @var array
     */
    protected $file_extension = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mov', 'qt', 'rtf', 'tar', 'zip', 'rar', 'mp4', 'avi', 'pdf', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return false;
        }

        $default_disks = array_keys(config('filesystems.disks'));

        $disks = empty($this->option('disk'))
            ? multiselect(
                label: 'Select disk(s)',
                options: $default_disks,
                required: 'You must select at least one disk',
            )
            : $this->option('disk');

        $delete_option = null;
        if ($this->option('all') && $this->option('extra')) {
            $this->warn('Either delete all files or extra files');
            return false;
        } else if ($this->option('all')) {
            $delete_option = 'all';
        } else if ($this->option('extra')) {
            $delete_option = 'unnecessary';
        } else {
            $delete_option = select(
                label: 'Clear storage',
                options: [
                    'all' => 'All files & folders',
                    'unnecessary' => 'Unnecessary files',
                ]
            );
        }

        if ($delete_option === "unnecessary") {
            $this->clearExtra($disks);
        } else {
            $this->clearAll($disks);
        }
    }

    private function clearAll(array $disks): void
    {
        foreach ($disks as $disk) {
            $files_cleared = $this->deleteFiles($disk, $this->ignore_files);

            $directories_cleared = $this->deleteDirectories($disk, $this->ignore_directories);

            if ($files_cleared && $directories_cleared) {
                $diskName = str()->ucfirst($disk);

                $this->components->info("\"{$diskName}\" storage disk cleared");
            } else {
                $this->components->error("Error clearing \"{$disk}\" storage disk");
            }
        }
    }

    private function deleteFiles(string $disk, array $ignore_files): bool
    {
        try {
            $disk_files = Storage::disk($disk)->files();
            $files = array_diff($disk_files, $ignore_files);
            Storage::disk($disk)->delete($files);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function deleteDirectories(string $disk, array $ignore_directories): bool
    {
        try {
            $disk_directories = Storage::disk($disk)->directories();

            $directories = ($disk === "local")
                ? array_diff($disk_directories, $ignore_directories)
                : $disk_directories;

            foreach ($directories as $directory) {
                Storage::disk($disk)->deleteDirectory($directory);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function clearExtra(array $disks): void
    {
        foreach ($disks as $disk) {
            $files_cleared = $this->deleteExtraFiles($disk, $this->ignore_files);

            if ($files_cleared) {
                $this->components->info("All unnecessary files deleted from \"{$disk}\" storage disk");
            } else {
                $this->components->error("Error clearing unnecessary files from \"{$disk}\" storage disk");
            }
        }
    }

    private function deleteExtraFiles(string $disk, array $ignore_files): bool
    {
        try {
            $project_files = $this->getProjectFiles();

            $disk_files = Storage::disk($disk)->allFiles();

            $storageFiles = array_diff($disk_files, $ignore_files);

            $diff = count($storageFiles) >= count($project_files)
                ? array_diff($project_files, $storageFiles)
                : array_diff($storageFiles, $project_files);

            if ($disk === "local") {
                $ignoreDirectory = 'public';

                $diff = array_filter($diff, function ($file) use ($ignoreDirectory) {
                    return strpos($file, $ignoreDirectory) === false;
                });
            }

            Storage::disk($disk)->delete($diff);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getProjectFiles()
    {
        $models = $this->getModels();
        $modelHasFiles = [];

        for ($i = 1; $i < count($models); $i++) {
            $fullNamespaceModel = "\App\Models\\" . $models[$i];

            $modelColumns = $fullNamespaceModel::get();

            if ($modelColumns->count()) {
                if ($modelColumns->count() < 5000) {
                    foreach ($modelColumns as $columns) {
                        $j = 0;
                        foreach ($columns->getAttributes() as $column) {
                            if ($j !== 0) {
                                if (!is_array($column) && !empty(pathinfo((string) $column, PATHINFO_EXTENSION))) {
                                    $extension = explode(".", $column);
                                    $extension = strtolower(Arr::last($extension));

                                    if (in_array($extension, $this->file_extension)) {
                                        array_push($modelHasFiles, $column);
                                    }
                                }
                            }
                            $j++;
                        }
                    }
                }
            }
        }

        return $modelHasFiles;
    }

    private function getModels()
    {
        $modelsArray = [];

        $results = scandir(app_path() . "/Models");

        foreach ($results as $filename) {
            if ($filename === '.' or $filename === '..') {
                continue;
            }
            array_push($modelsArray, substr($filename, 0, -4));
        }

        return $modelsArray;
    }
}
