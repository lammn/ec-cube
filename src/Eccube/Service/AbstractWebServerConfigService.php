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


abstract class AbstractWebServerConfigService
{
    abstract protected function getVersion();

    abstract protected function getRewriteTextConfig($version);

    abstract protected function parseConfig($source);

    abstract protected function appendConfig($config, $append);

    abstract protected function writeConfig($content, $destination);

    public function backupConfig($source, $destination)
    {
        if (!copy($source, $destination)) {
            return 0;
        }
        return 1;
    }

    public function rollbackConfig($source, $destination)
    {
        if (file_exists($source)) {
            unlink($destination);
            rename($source, $destination);
        }
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

