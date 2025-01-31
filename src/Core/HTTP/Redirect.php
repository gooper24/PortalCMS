<?php

namespace PortalCMS\Core\HTTP;

use PortalCMS\Core\Config\Config;

/**
 * Class Redirect
 *
 * Simple abstraction for redirecting the user to a certain page
 */
class Redirect
{
    /**
     * To the defined page, uses a relative path (like "user/profile")
     *
     * Redirects to a RELATIVE path, like "user/profile"
     *
     * @param string $path
     */
    public static function to($path)
    {
        session_write_close();
        header('location: ' . Config::get('URL') . $path);
    }
}
