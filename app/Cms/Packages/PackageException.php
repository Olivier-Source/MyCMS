<?php

namespace App\Cms\Packages;

use RuntimeException;

/**
 * Error shown to the administrator when a theme or language pack cannot be
 * downloaded, read or installed. The message is already translated.
 */
class PackageException extends RuntimeException {}
