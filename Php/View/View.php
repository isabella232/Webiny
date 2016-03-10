<?php
/**
 * Webiny Platform (http://www.webiny.com/)
 *
 * @copyright Copyright (c) 2009-2014 Webiny LTD. (http://www.webiny.com/)
 * @license   http://www.webiny.com/platform/license
 */

namespace Apps\Core\Php\View;

use Webiny\Component\Config\Config;

class View
{
    /**
     * Get access to system configuration
     *
     * @return Config
     */
    public static function wConfig()
    {
        return \Apps\Core\Php\DevTools\Config::getInstance();
    }
}