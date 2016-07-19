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

    /**
     * @var null|string
     */
    private $dir = null;

    /**
     * @var string
     */
    private $backupExt = '.bak';

    /**
     * @var null|string
     */
    private $accessFileName = null;

    /**
     * name of file config path
     * @var string
     */
    private $pathName = 'path.yml';

    /**
     * @var \Eccube\Service\SystemService
     */
    private $system;

    private $webServer;

    /**
     * RemoveHtmlController constructor.
     */
    public function __construct()
    {
        // title of remove html page
        $this->subtitle = 'Remove HTML';
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app)
    {
        $this->beforeAction($app);

        $isHiddenHTML = $this->system->isHiddenHTML($app);
        $isRollBack = false;

        // Use mod rewrite
        $isRewrite = $this->system->isUseModRewrite();
        if (!$isRewrite) {
            $app->addError('admin.system.removehtml.rewrite.error', 'admin');
            return $app->render('Setting/System/remove_html.twig', array(
                'subtitle' => $this->subtitle,
                'is_hidden_html' => $isHiddenHTML,
                'is_rollback' => $isRollBack,
            ));
        }

        // Check permission in root folder
        $isWrite = $this->system->isWritable($this->dir);
        if (!$isWrite) {
            $app->addError('admin.system.removehtml.permission.error', 'admin');
            return $app->render('Setting/System/remove_html.twig', array(
                'subtitle' => $this->subtitle,
                'is_hidden_html' => $isHiddenHTML,
                'is_rollback' => $isRollBack,
            ));
        }

        // Backup check
        $isRollBack = $this->system->isRollBack($isHiddenHTML, $this->dir, $this->accessFileName . $this->backupExt);

        // Path to config path.yml file
        $configDir = $app['config']['root_dir'] . '/app/config/eccube/';
        $pathFile = $configDir . $this->pathName;

        // Access file of web server
        $accessFile = $this->dir . '/' . $this->accessFileName;
        switch ($app['request']->get('mode')) {
            case 'remove':
                // Backup access file
                $this->webServer->backupConfig($accessFile, $accessFile . $this->backupExt);
                $this->webServer->changeConfig($accessFile, $accessFile);

                // change yml
                $this->system->changeContentYml($configDir, $this->pathName, $this->backupExt);
                $app->addSuccess('admin.system.removehtml.remove.success', 'admin');
                return $app->redirect($app->url('admin_setting_system_removehtml'));
                break;

            case 'rollback':
                // rollback access file
                $this->webServer->rollbackConfig($accessFile . $this->backupExt, $accessFile);
                // yml file
                $this->webServer->rollbackConfig($pathFile . $this->backupExt, $pathFile);
                $app->addSuccess('admin.system.removehtml.rollback.success', 'admin');
                return $app->redirect($app->url('admin_setting_system_removehtml'));
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
     * Before action
     * @param Application $app
     */
    private function beforeAction(Application $app)
    {
        // root folder where save config file
        $this->dir = $app['config']['root_dir'];

        // system service provider
        $this->system = $app['eccube.service.system'];

        // detect web server used
        $webServer = $this->system->detectWebServer();
        switch ($webServer) {
            case 'Apache':
                $this->webServer = $app['eccube.service.apache'];
                $this->accessFileName = '.htaccess';
                break;

            case 'Microsoft-IIS':
                $this->accessFileName = 'web.config';
                break;

            case 'Nginx':
                break;

            default:
                break;
        }
    }

}
