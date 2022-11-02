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

namespace RocketWeb\ConfigExport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportConfigsCommand extends Command
{
    private const ARG_SCOPES = 'scopes';
    private const ARG_PATHS = 'paths';
    private const ALLOWED_SCOPES = ['default', 'websites', 'stores'];

    private \RocketWeb\ConfigExport\Provider\Fetch $fetch;
    private \RocketWeb\ConfigExport\Handler\Config $configHandler;

    public function __construct(
        \RocketWeb\ConfigExport\Handler\Config $configHandler,
        \RocketWeb\ConfigExport\Provider\Fetch $fetch,
        string $name = null
    ) {
        parent::__construct($name);
        $this->fetch = $fetch;
        $this->configHandler = $configHandler;
    }

    protected function configure(): void
    {
        $this->setName('config:data:export');
        $this->setDescription('Export specific configuration into config.xml to make it VCS transferable'
            . ' without locking them by using app/etc/config.php or app/etc/env.php');

        $this->addArgument(
            self::ARG_SCOPES,
            InputArgument::REQUIRED,
            'Scopes for which you want to export values for. CSV values are allowed. '
            . 'Options: all|' . implode('|', self::ALLOWED_SCOPES)
        );
        $this->addArgument(
            self::ARG_PATHS,
            InputArgument::REQUIRED,
            'Path(s) that you want to export. Wildcard support as asterisk for second and third section'
            . ' of the path (*). Example: trans_email/*/email'
        );

        parent::configure();
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $input->getArgument(self::ARG_PATHS);
        $paths = array_filter(array_map('trim', explode(',', $paths)));

        if (count($paths) == 0) {
            $output->writeln('No valid paths provided, existing');
            return;
        }

        $scopes = trim($input->getArgument(self::ARG_SCOPES));
        if ($scopes === 'all') {
            $scopes = implode(',', self::ALLOWED_SCOPES);
        }
        $scopes = array_filter(array_map('trim', explode(',', $scopes)));
        $scopes = array_map('strtolower', $scopes);
        foreach ($scopes as $scope) {
            if (!in_array($scope, self::ALLOWED_SCOPES)) {
                $output->writeln('Scope "' . $scope . '" is not valid. Accepted values: all|'
                    . implode('|', self::ALLOWED_SCOPES));
                return;
            }
        }
        $values = [];
        foreach ($paths as $path) {
            $values = array_merge_recursive($values, $this->fetch->values($path, $scopes));
        }

        $xml = $this->configHandler->get();
        $this->updateXml($xml, $values);
        $this->configHandler->set($xml);
    }

    protected function updateXml(\SimpleXMLElement $xml, array $values)
    {
        foreach ($values as $key => $subValue) {
            if (is_array($subValue)) {
                $childXml = $this->findXmlChild($xml, $key);
                $this->updateXml($childXml, $subValue);
            } else {
                $this->findXmlChild($xml, $key);
                $xml->{$key} = $subValue;
            }
        }
    }

    private function findXmlChild($xml, $key): \SimpleXMLElement
    {
        $children = $xml->children();
        $childXml = null;
        foreach ($children as $child) {
            if ($child->getName() == $key) {
                $childXml = $child;
                break;
            }
        }
        if (!$childXml) {
            $childXml = $xml->addChild($key, '');
        }

        return $childXml;
    }
}
