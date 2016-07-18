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
}
