<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Serves the bundled WordPress plugin (wordpress-plugin/cegem360-marketinghub)
 * as an installable zip. It contains no secrets: the webhook URL is entered
 * in WordPress after installing.
 */
final class WordPressPluginController extends Controller
{
    private const string PLUGIN_SLUG = 'cegem360-marketinghub';

    public function __invoke(): StreamedResponse
    {
        $source = dirname(__DIR__, 3).'/wordpress-plugin/'.self::PLUGIN_SLUG;
        $path = (string) tempnam(sys_get_temp_dir(), 'ffb-wp');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        /** @var iterable<SplFileInfo> $files */
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->isFile()) {
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($source))), '/');
                $zip->addFile($file->getPathname(), self::PLUGIN_SLUG.'/'.$relative);
            }
        }

        $zip->close();

        return response()->streamDownload(function () use ($path): void {
            readfile($path);
            @unlink($path);
        }, self::PLUGIN_SLUG.'.zip', ['Content-Type' => 'application/zip']);
    }
}
