<?php

namespace App\Core\Plugins;

use RuntimeException;

/**
 * Thrown when a plugin cannot be resolved or refuses an action. Controllers
 * translate this into a 404 so an unresolvable slug or a method that is not
 * on the manifest allowlist never leaks filesystem or class names.
 */
class PluginException extends RuntimeException
{
}
