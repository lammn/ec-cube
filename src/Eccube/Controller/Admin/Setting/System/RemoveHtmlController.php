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


namespace Eccube\Controller\Admin\Setting\System;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Symfony\Component\Yaml\Yaml;

/**
 * Class RemoveHtmlController remove html in url
 * @package Eccube\Controller\Admin\Setting\System
 */
class RemoveHtmlController extends AbstractController
{
    /**
     * @var string
     */
    private $subtitle;

    private $dir;

    private $backupExt = '.bak';

    private $accessFileName;

    private $pathName = 'path.yml';

    /**
     * RemoveHtmlController constructor.
     */
    public function __construct()
    {
        $this->subtitle = 'Remove HTML';
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app)
    {
        $system = $app['eccube.service.system'];

        $isHiddenHTML = $system->isHiddenHTML($app);
        $isRollBack = false;

        // Use mod rewrite
        $isRewrite = $system->isUseModRewrite();
        if (!$isRewrite) {
            $app->addError('admin.system.removehtml.rewrite.error', 'admin');
            return $app->render('Setting/System/remove_html.twig', array(
                'subtitle' => $this->subtitle,
                'is_hidden_html' => $isHiddenHTML,
                'is_rollback' => $isRollBack,
            ));
        }

        // Check permission in root folder
        $root = $app['config']['root_dir'];
        $isWrite = $system->isWritable($root);
        if (!$isWrite) {
            $app->addError('admin.system.removehtml.permission.error', 'admin');
            return $app->render('Setting/System/remove_html.twig', array(
                'subtitle' => $this->subtitle,
                'is_hidden_html' => $isHiddenHTML,
                'is_rollback' => $isRollBack,
            ));
        }

        // Backup check
        $this->accessFileName = '.htaccess';
        $this->dir = $root;
        $accessFile = $this->dir . '/' . $this->accessFileName;
        $isRollBack = $system->isRollBack($isHiddenHTML, $this->dir, $this->accessFileName . $this->backupExt);


        $apache = $app['eccube.service.apache'];
        $configDir = $app['config']['root_dir'] . '/app/config/eccube/';
        $pathFile = $configDir . $this->pathName;

        switch ($app['request']->get('mode')) {
            case 'remove':
                $apache->backupConfig($accessFile, $accessFile . $this->backupExt);
                $apache->changeConfig($accessFile, $accessFile);
                $this->changeContentYml($configDir, $this->pathName, $this->backupExt);
                break;

            case 'rollback':
                $apache->rollbackConfig($accessFile . $this->backupExt, $accessFile);
                $apache->rollbackConfig($pathFile . $this->backupExt, $pathFile);
                break;

            default:
                break;
        }

        return $app->render('Setting/System/remove_html.twig', array(
            'subtitle' => $this->subtitle,
            'is_hidden_html' => $isHiddenHTML,
            'is_rollback' => $isRollBack,
        ));
    }

    /**
     * Change content of yml config file
     * @param $dir
     * @param $filename
     * @param string $extBackup
     * @return bool
     */
    protected function changeContentYml($dir, $filename, $extBackup = '.bak')
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

        $path = $fileContent['public_path'];
        foreach ($fileContent as $key => $item) {
            if (strpos($key, 'urlpath') !== false || strpos($key, 'tpl') !== false) {
                $fileContent[$key] = str_replace($path, '', $item);
            }
        }
        $fileContent['image_path'] = str_replace($path, '', $fileContent['image_path']);

        $tmp = str_replace($path, '', $fileContent['root']);
        $fileContent['root'] = $tmp == '' ? '/' : $tmp ;
        $fileContent['root_urlpath'] = str_replace(trim($path, '/'), '', $fileContent['root_urlpath']);
        
        $ymlContent = Yaml::dump($fileContent);

        file_put_contents($filePath, $ymlContent);
        return true;
    }
}
