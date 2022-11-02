<?php declare(strict_types=1);
/**
 *  RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @copyright Copyright (c) 2022 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */

namespace RocketWeb\ConfigExport\Handler;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Reads the existing file content from var/config/config.xml and parses it into an XML structure. If file doesn't
 * exist, it creates an empty template.
 */
class Config
{
    private const CONFIG_PATH = '/config/config.xml';

    private \Magento\Framework\Filesystem $filesystem;

    public function __construct(
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    public function get(): \SimpleXMLElement
    {
        $varFolder = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);

        $content = '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . ' xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd"></config>';
        if ($varFolder->isExist(self::CONFIG_PATH)) {
            $content = $varFolder->readFile(self::CONFIG_PATH);
        }

        return new \SimpleXmlElement($content);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function set(\SimpleXMLElement $xml): void
    {
        $varFolder = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        $content = $xml->asXML();
        $content = $this->getCleanContent($content);

        $varFolder->writeFile(self::CONFIG_PATH, $content);
    }

    private function getCleanContent($content): string
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($content);
        $content = $dom->saveXML();
        $content = explode("\n", $content);
        $contentSize = count($content);
        for ($i = 0; $i < $contentSize; $i++) {
            $pos = strpos($content[$i], '<');
            if ($pos === false) {
                continue;
            }
            $spaces = substr($content[$i], 0, $pos);
            $content[$i] = str_replace($spaces, str_replace('  ', '    ', $spaces), $content[$i]);
        }

        return implode("\n", $content);
    }
}
