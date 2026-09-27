<?php

namespace App\Cms\Packages;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use ZipArchive;

/**
 * Downloads and unpacks a package (theme or language pack) into a temporary
 * folder, from:
 *  - a public Git repository (GitHub, GitLab or any host when `git` is installed),
 *  - a direct link to a .zip archive,
 *  - an uploaded .zip archive.
 *
 * Safety: HTTPS only, public hosts only (no access to the local network),
 * size and file-count limits, no symbolic links, no path traversal.
 */
class PackageFetcher
{
    /** Folders never copied from a package */
    private const IGNORED_DIRS = ['.git', '.github', '.gitlab', '.idea', '.vscode', 'node_modules', '__MACOSX'];

    /**
     * Downloads a Git repository or a .zip link. "#ref" selects a branch or tag.
     *
     * @return string Temporary folder containing the files
     */
    public function fromUrl(string $url): string
    {
        $url = trim($url);
        [$url, $ref] = array_pad(explode('#', $url, 2), 2, null);
        $ref = $ref !== null && preg_match('/^[A-Za-z0-9._\/-]{1,100}$/', $ref) ? $ref : null;

        $uri = $this->validateUrl($url);

        if (preg_match('/\.zip$/i', $uri->getPath())) {
            return $this->fromZip($this->download((string) $uri), true);
        }

        if ($archive = $this->archiveUrl($uri, $ref)) {
            return $this->fromZip($this->download($archive), true);
        }

        return $this->gitClone((string) $uri, $ref);
    }

    /**
     * Extracts a .zip archive into a temporary folder.
     *
     * @return string Temporary folder containing the files
     */
    public function fromZip(string $zipFile, bool $deleteZip = false): string
    {
        if (! class_exists(ZipArchive::class)) {
            return $this->fromZipWithPhar($zipFile, $deleteZip);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== true) {
            $deleteZip && @unlink($zipFile);
            throw new PackageException(__('This file is not a valid ZIP archive.'));
        }

        $dir = $this->tempDir();
        $maxBytes = config('mycms.packages.max_download_mb') * 1024 * 1024 * 3;
        $maxFiles = config('mycms.packages.max_files');
        $total = 0;
        $files = 0;

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = str_replace('\\', '/', (string) $stat['name']);

                // Symbolic links are refused (Unix mode stored in the external attributes)
                $zip->getExternalAttributesIndex($i, $opsys, $attr);
                if ($opsys === ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
                    throw new PackageException(__('The archive contains symbolic links, which are not allowed.'));
                }

                if (str_ends_with($name, '/')) {
                    continue;
                }
                if (! $this->isSafePath($name)) {
                    throw new PackageException(__('The archive contains an invalid file path.'));
                }
                if ($this->isIgnored($name)) {
                    continue;
                }
                if (++$files > $maxFiles || ($total += $stat['size']) > $maxBytes) {
                    throw new PackageException(__('The archive is too large.'));
                }

                $target = $dir.'/'.$name;
                File::ensureDirectoryExists(dirname($target));
                $in = $zip->getStream($stat['name']);
                $out = fopen($target, 'wb');
                if (! $in || ! $out) {
                    throw new PackageException(__('The archive could not be extracted.'));
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
                fclose($out);
            }
        } catch (PackageException $e) {
            File::deleteDirectory($dir);
            throw $e;
        } finally {
            $zip->close();
            $deleteZip && @unlink($zipFile);
        }

        return $dir;
    }

    /**
     * Same as fromZip(), for servers without the "zip" extension: the archive
     * is read with PharData (built into PHP), with the same checks.
     */
    private function fromZipWithPhar(string $zipFile, bool $deleteZip): string
    {
        if (! class_exists(\PharData::class)) {
            throw new PackageException(__('The PHP "zip" extension is required to install packages.'));
        }

        // PharData needs the .zip extension in the file name
        $copy = storage_path('app/tmp/packages/'.Str::lower(Str::random(16)).'.zip');
        File::ensureDirectoryExists(dirname($copy));
        File::copy($zipFile, $copy);
        $deleteZip && @unlink($zipFile);

        $dir = $this->tempDir();
        $maxBytes = config('mycms.packages.max_download_mb') * 1024 * 1024 * 3;
        $maxFiles = config('mycms.packages.max_files');
        $total = 0;
        $files = 0;

        try {
            try {
                $archive = new \PharData($copy, \FilesystemIterator::SKIP_DOTS);
            } catch (\Throwable) {
                throw new PackageException(__('This file is not a valid ZIP archive.'));
            }
            $prefix = 'phar://'.str_replace('\\', '/', $copy).'/';

            foreach (new \RecursiveIteratorIterator($archive) as $entry) {
                $name = substr(str_replace('\\', '/', $entry->getPathname()), strlen($prefix));
                if (! $this->isSafePath($name)) {
                    throw new PackageException(__('The archive contains an invalid file path.'));
                }
                if ($this->isIgnored($name)) {
                    continue;
                }
                if (++$files > $maxFiles || ($total += $entry->getSize()) > $maxBytes) {
                    throw new PackageException(__('The archive is too large.'));
                }
                File::ensureDirectoryExists(dirname($dir.'/'.$name));
                if (! @copy($entry->getPathname(), $dir.'/'.$name)) {
                    throw new PackageException(__('The archive could not be extracted.'));
                }
            }
        } catch (PackageException $e) {
            File::deleteDirectory($dir);
            throw $e;
        } finally {
            unset($archive);
            @unlink($copy);
        }

        return $dir;
    }

    /**
     * Folder holding the manifest: the root of the package, or its single
     * top-level folder (GitHub archives contain "repo-main/").
     */
    public function locateRoot(string $dir, string $manifest): string
    {
        if (is_file($dir.'/'.$manifest)) {
            return $dir;
        }
        $children = array_values(array_filter(glob($dir.'/*') ?: [], 'is_dir'));
        if (count($children) === 1 && is_file($children[0].'/'.$manifest)) {
            return $children[0];
        }

        throw new PackageException(__('No :file file was found at the root of the package.', ['file' => $manifest]));
    }

    /**
     * Copies the allowed files of a package (by extension) to a new folder.
     *
     * @param  string[]  $extensions  Allowed extensions (e.g. "blade.php", "css")
     */
    public function copyAllowed(string $source, string $target, array $extensions): void
    {
        File::ensureDirectoryExists($target);
        $source = rtrim(str_replace('\\', '/', $source), '/');

        foreach (File::allFiles($source, true) as $file) {
            if ($file->isLink()) {
                throw new PackageException(__('The package contains symbolic links, which are not allowed.'));
            }
            $relative = ltrim(str_replace('\\', '/', substr(str_replace('\\', '/', $file->getPathname()), strlen($source))), '/');
            if ($this->isIgnored($relative) || ! $this->hasAllowedExtension($relative, $extensions)) {
                continue;
            }
            File::ensureDirectoryExists(dirname($target.'/'.$relative));
            File::copy($file->getPathname(), $target.'/'.$relative);
        }
    }

    public function tempDir(): string
    {
        $dir = storage_path('app/tmp/packages/'.Str::lower(Str::random(16)));
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function cleanup(?string ...$dirs): void
    {
        foreach (array_filter($dirs) as $dir) {
            File::deleteDirectory($dir);
        }
    }

    /* ------------------------------------------------------------------ */

    private function hasAllowedExtension(string $path, array $extensions): bool
    {
        $name = strtolower(basename($path));
        if (in_array($name, ['license', 'licence', 'readme', 'changelog'], true)) {
            return true;
        }
        foreach ($extensions as $ext) {
            if (str_ends_with($name, '.'.$ext)) {
                // "x.php" is only accepted as a Blade template ("x.blade.php")
                return $ext !== 'php';
            }
        }

        return false;
    }

    private function isSafePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) || str_contains($path, "\0")) {
            return false;
        }

        return ! in_array('..', explode('/', $path), true);
    }

    private function isIgnored(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if (in_array($segment, self::IGNORED_DIRS, true) || $segment === '.DS_Store') {
                return true;
            }
        }

        return false;
    }

    /** Only https:// addresses pointing to a public server are accepted. */
    private function validateUrl(string $url): UriInterface
    {
        try {
            $uri = new Uri($url);
        } catch (\InvalidArgumentException) {
            throw new PackageException(__('This address is not valid.'));
        }

        if ($uri->getScheme() !== 'https' || $uri->getHost() === '' || $uri->getUserInfo() !== '') {
            throw new PackageException(__('Only https:// addresses are accepted.'));
        }
        $this->assertPublicHost($uri->getHost());

        return $uri;
    }

    private function assertPublicHost(string $host): void
    {
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $ips) {
            throw new PackageException(__('The server :host could not be found.', ['host' => $host]));
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new PackageException(__('Addresses on a local network are not allowed.'));
            }
        }
    }

    /** ZIP archive address for well-known Git hosts (no `git` needed). */
    private function archiveUrl(UriInterface $uri, ?string $ref): ?string
    {
        $path = trim(preg_replace('/\.git$/', '', $uri->getPath()), '/');

        if ($uri->getHost() === 'github.com' && preg_match('#^([\w.-]+)/([\w.-]+)(?:/tree/(.+))?$#', $path, $m)) {
            $ref ??= $m[3] ?? 'HEAD';

            return "https://github.com/{$m[1]}/{$m[2]}/archive/".rawurlencode($ref).'.zip';
        }

        if ($uri->getHost() === 'gitlab.com' && preg_match('#^([\w.-]+(?:/[\w.-]+)+?)(?:/-/tree/(.+))?$#', $path, $m)) {
            $ref ??= $m[2] ?? null;

            return 'https://gitlab.com/api/v4/projects/'.rawurlencode($m[1]).'/repository/archive.zip'.($ref ? '?sha='.rawurlencode($ref) : '');
        }

        return null;
    }

    private function download(string $url): string
    {
        $file = storage_path('app/tmp/packages/'.Str::lower(Str::random(16)).'.zip');
        File::ensureDirectoryExists(dirname($file));
        $max = config('mycms.packages.max_download_mb') * 1024 * 1024;

        try {
            $response = Http::timeout(config('mycms.packages.timeout'))
                ->withHeaders(['User-Agent' => 'MyCMS/'.config('mycms.version')])
                ->withOptions([
                    'sink' => $file,
                    'allow_redirects' => [
                        'max' => 5,
                        'protocols' => ['https'],
                        'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $uri) {
                            $this->assertPublicHost($uri->getHost());
                        },
                    ],
                    'progress' => function ($total, $downloaded) use ($max) {
                        if ($total > $max || $downloaded > $max) {
                            throw new PackageException(__('The file is too large (:max MB maximum).', ['max' => $max / 1048576]));
                        }
                    },
                ])
                ->get($url);
        } catch (PackageException $e) {
            @unlink($file);
            throw $e;
        } catch (\Throwable $e) {
            @unlink($file);
            $previous = $e->getPrevious();
            throw $previous instanceof PackageException ? $previous : new PackageException(__('The download failed: :error', ['error' => Str::limit($e->getMessage(), 150)]));
        }

        if (! $response->successful()) {
            @unlink($file);
            throw new PackageException(__('The download failed (error :status). Check that the repository is public.', ['status' => $response->status()]));
        }

        return $file;
    }

    private function gitClone(string $url, ?string $ref): string
    {
        if (! function_exists('proc_open') || ! Process::run(['git', '--version'])->successful()) {
            throw new PackageException(__('Git is not available on this server: download the ZIP archive of the repository and upload it instead.'));
        }

        $dir = $this->tempDir();
        $command = ['git', '-c', 'protocol.allow=never', '-c', 'protocol.https.allow=always', '-c', 'core.symlinks=false',
            'clone', '--depth', '1', '--single-branch', '--no-tags'];
        if ($ref) {
            array_push($command, '--branch', $ref);
        }
        array_push($command, '--', $url, $dir.'/repo');

        $result = Process::timeout(config('mycms.packages.timeout'))
            ->env(['GIT_TERMINAL_PROMPT' => '0', 'GIT_ASKPASS' => 'echo'])
            ->run($command);

        if (! $result->successful()) {
            File::deleteDirectory($dir);
            throw new PackageException(__('The repository could not be downloaded. Check the address and that the repository is public.'));
        }
        File::deleteDirectory($dir.'/repo/.git');

        return $dir;
    }
}
