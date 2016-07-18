<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Eccube\Service;

use Eccube\Application;
use Symfony\Component\Yaml\Yaml;

class SystemService
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getDbversion()
    {

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addScalarResult('v', 'v');

        switch ($this->app['config']['database']['driver']) {
            case 'pdo_sqlite':
                $prefix = 'SQLite version ';
                $func = 'sqlite_version()';
                break;

            case 'pdo_mysql':
                $prefix = 'MySQL ';
                $func = 'version()';
                break;

            case 'pdo_pgsql':
            default:
                $prefix = '';
                $func = 'version()';
        }

        $version = $this->app['orm.em']
            ->createNativeQuery('select '.$func.' as v', $rsm)
            ->getSingleScalarResult();

        return $prefix.$version;
    }

    /**
     * Check use mod rewrite
     * @return bool
     */
    public function isUseModRewrite()
    {
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            $use = in_array('mod_rewrite', $modules);
        } else {
            $use =  getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
        }

        return $use;
    }

    /**
     * Check rollback when hidden html
     * @param $isHiddenHTML
     * @param $backupDir
     * @param $backupFilename
     * @return bool
     */
    public function isRollBack($isHiddenHTML, $backupDir, $backupFilename)
    {
        if (!$isHiddenHTML) {
            return false;
        }

        if (file_exists($backupDir . '/' . $backupFilename)) {
            return true;
        }

        return false;
    }

    /**
     * Check html
     * @param Application $app
     * @return bool
     */
    public function isHiddenHTML(Application $app)
    {
        if (strpos($app['config']['root'], $app['config']['public_path']) === false) {
            return true;
        }

        return false;
    }

    /**
     * Check permission in folder
     * @param $dir
     * @return bool
     */
    public function isWritable($dir)
    {
        if (is_dir($dir) && is_writeable($dir)) {
            return true;
        }

        return false;
    }

    /**
     * Change content of yml config file
     * @param $dir
     * @param $filename
     * @param string $extBackup
     * @return bool
     */
    public function changeContentYml($dir, $filename, $extBackup = '.bak')
    {
        $filePath = $dir . $filename;
        $fileContent = '';

        if (file_exists($filePath)) {
            $fileContent = Yaml::parse(file_get_contents($filePath));
        }

        if (empty($fileContent)) {
            return false;
        }

        // backup file
        copy($filePath, $filePath . $extBackup);

        // path have html - remove html
        $path = $fileContent['public_path'];
        foreach ($fileContent as $key => $item) {
            if (strpos($key, 'urlpath') !== false || strpos($key, 'tpl') !== false) {
                $fileContent[$key] = str_replace($path, '', $item);
            }
        }
        $fileContent['image_path'] = str_replace($path, '', $fileContent['image_path']);
        $tmpPath = trim($path, '/');
        $fileContent['root'] = str_replace($tmpPath, '', $fileContent['root']);
        $fileContent['root_urlpath'] = str_replace($tmpPath, '', $fileContent['root_urlpath']);

        // put to yml file
        $ymlContent = Yaml::dump($fileContent);
        return file_put_contents($filePath, $ymlContent);
    }

    /**
     * Detect web server used
     * @return bool|string
     */
    public function detectWebServer()
    {
        $webServer = $_SERVER["SERVER_SOFTWARE"];
        if (strpos($webServer, 'Microsoft-IIS') !== false) {
            return 'Microsoft-IIS';
        } elseif (strpos($webServer, 'Apache') !== false) {
            return 'Apache';
        } elseif (strpos(strtolower($webServer), 'nginx') !== false) {
            return 'Nginx';
        }

        return false;
    }
}
