<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;

trait InteractWithFiles
{
    protected $stored_files = [];

    protected $request;

    protected function request(FormRequest $request): self
    {
        $this->request = $request;

        return $this;
    }

    protected function store(string $key, ?string $path = null, ?string $disk = null): self
    {
        if (!$this->request->hasFile($key)) return $this;

        if (array_key_exists($key, $this->request->validated())) {
            $path ??= $this->getPath($key);

            $disk ??= config('filesystems.default');

            $this->stored_files[$key] = $this->request->{$key}->store($path, $disk);

            return $this;
        } else {
            // @codeCoverageIgnoreStart
            throw new \Exception("The {$key} not exists in form request");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function storeAs(string $key, string $name, ?string $path = null, ?string $disk = null): self
    {
        if (!$this->request->hasFile($key)) return $this;

        if (array_key_exists($key, $this->request->validated())) {
            $path ??= $this->getPath($key);

            $disk ??= config('filesystems.default');

            $this->stored_files[$key] = $this->request->{$key}->store($path, $name, $disk);

            return $this;
        } else {
            throw new \Exception("The {$key} not exists in form request");
        }
    }

    protected function validated(): array
    {
        $validated = $this->request->only(array_keys($this->request->validated()));

        return array_merge($validated, $this->stored_files);
    }

    private function getPath(string $key): string
    {
        return (Str::contains($key, [' ', '-', '_']))
            ? Str::plural(Str::lower(str_replace([' ', '_'], '-', $key)))
            : Str::plural(Str::kebab($key));
    }
}
