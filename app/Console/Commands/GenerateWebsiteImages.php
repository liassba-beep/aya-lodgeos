<?php

namespace App\Console\Commands;

use App\Models\PropertyPhoto;
use App\Models\RoomType;
use App\Models\TenantAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class GenerateWebsiteImages extends Command
{
    protected $signature = 'website:generate-images {--force : Regenerate existing files} {--quality=78 : WebP quality, from 1 to 100}';

    protected $description = 'Generate WebP and AVIF variants for public tenant website images.';

    public function handle(): int
    {
        $webp = (new ExecutableFinder())->find('cwebp');
        $avif = (new ExecutableFinder())->find('avifenc');
        $quality = max(1, min(100, (int) $this->option('quality')));
        $force = (bool) $this->option('force');

        if (! $webp && ! $avif) {
            $this->warn('No image encoders found. Install cwebp and avifenc.');

            return self::FAILURE;
        }

        $sources = $this->sources();
        $converted = 0;

        foreach ($sources as $source) {
            if ($webp && $this->convertWebp($webp, $source, $quality, $force)) {
                $converted++;
            }

            if ($avif && $this->convertAvif($avif, $source, $force)) {
                $converted++;
            }
        }

        $this->info("Generated {$converted} responsive image variant(s).");

        return self::SUCCESS;
    }

    private function sources(): array
    {
        $paths = [];

        foreach (glob(public_path('images/*.{jpg,jpeg,png}'), GLOB_BRACE) ?: [] as $path) {
            $paths[] = $path;
        }

        try {
            $paths = array_merge($paths, TenantAccount::query()
                ->get(['og_image', 'favicon_path'])
                ->flatMap(fn (TenantAccount $tenant): array => [$tenant->og_image, $tenant->favicon_path])
                ->all());

            $paths = array_merge($paths, PropertyPhoto::withoutGlobalScopes()->pluck('path')->all());
            $paths = array_merge($paths, RoomType::withoutGlobalScopes()->pluck('photo')->all());
        } catch (Throwable $exception) {
            $this->warn('Database image lookup skipped: '.$exception->getMessage());
        }

        return collect($paths)
            ->filter()
            ->map(fn (string $path): ?string => $this->physicalPath($path))
            ->filter(fn (?string $path): bool => $path && is_file($path) && $this->isSupportedSource($path))
            ->unique()
            ->values()
            ->all();
    }

    private function physicalPath(string $path): ?string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            return public_path(ltrim($path, '/'));
        }

        if (str_starts_with($path, '/')) {
            return public_path(ltrim($path, '/'));
        }

        return Storage::disk('public')->path($path);
    }

    private function isSupportedSource(string $path): bool
    {
        return (bool) preg_match('/\.(jpe?g|png)$/i', $path);
    }

    private function convertWebp(string $binary, string $source, int $quality, bool $force): bool
    {
        $target = $this->targetPath($source, 'webp');

        if (! $force && is_file($target)) {
            return false;
        }

        $process = new Process([$binary, '-quiet', '-q', (string) $quality, $source, '-o', $target]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->warn("WebP failed for {$source}: ".trim($process->getErrorOutput()));

            return false;
        }

        return true;
    }

    private function convertAvif(string $binary, string $source, bool $force): bool
    {
        $target = $this->targetPath($source, 'avif');

        if (! $force && is_file($target)) {
            return false;
        }

        $process = new Process([$binary, '--min', '24', '--max', '38', $source, $target]);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->warn("AVIF failed for {$source}: ".trim($process->getErrorOutput()));

            return false;
        }

        return true;
    }

    private function targetPath(string $source, string $extension): string
    {
        return preg_replace('/\.(jpe?g|png)$/i', '.'.$extension, $source) ?: $source.'.'.$extension;
    }
}
