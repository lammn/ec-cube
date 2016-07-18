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

use Eccube\Controller\AbstractWebServerConfigService;

class ApacheWebServerConfigService extends AbstractWebServerConfigService
{

    protected function getVersion()
    {
        return apache_get_version();
    }

    protected function getRewriteTextConfig($version)
    {
        $version2 = preg_match('/Apache\/2./i', $version, $matches, PREG_OFFSET_CAPTURE);
        if ($version2) {
            $rewrite = '<IfModule mod_rewrite.c>
                            RewriteEngine On
                            RewriteBase /
                            RewriteRule ^(.*)$ html/$1 [QSA,L]
                        </IfModule>';
        }
        return $rewrite;
    }

    protected function parseConfig($source)
    {
        return file_get_contents($source);
    }

    protected function appendConfig($config, $append)
    {
        return $config . $append;
    }

    protected function writeConfig($content, $destination)
    {
        $myfile = fopen($destination, "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
    }

    public function changeConfig($source, $destination)
    {
        $version = $this->getVersion();
        $append = $this->getRewriteTextConfig($version);
        $content = $this->parseConfig($source);
        $configContent = $this->appendConfig($content, $append);
        $this->writeConfig($configContent, $destination);
    }
}

