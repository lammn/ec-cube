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

    private $dir;

    private $backupFile;

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
        $this->backupFile = '.htaccess.old';
        $this->dir = $root;
        $isRollBack = $system->isRollBack($isHiddenHTML, $this->dir, $this->backupFile);

        switch ($app['request']->get('mode')) {
            case 'remove':
                echo 1;
                break;
            case 'rollback':
                echo 2;
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
}
