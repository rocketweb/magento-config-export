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

class DefaultRemoval
{
    private \Magento\Framework\App\Config\Initial\Reader $configReader;

    public function __construct(
        \Magento\Framework\App\Config\Initial\Reader $configReader
    ) {
        $this->configReader = $configReader;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $values): array
    {
        $data = $this->configReader->read();
        $data = $data['data'];

        foreach ($values as $scope => $scopeDataGroups) {
            if (!isset($data[$scope])) {
                continue;
            }

            if ($scope === 'default') {
                $values[$scope] = $this->clean($scopeDataGroups, $data[$scope]);
                continue;
            }

            foreach ($scopeDataGroups as $scopeKey => $scopeData) {
                if (!isset($data[$scope][$scopeKey])) {
                    continue;
                }
                $values[$scope][$scopeKey] = $this->clean($scopeData, $data[$scope][$scopeKey]);
            }
        }

        return $values;
    }

    protected function clean(array $scopeData, $configData): array
    {
        $pathsForCleanup = $this->cleanRecursion($scopeData, $configData);
        foreach ($pathsForCleanup as $levels) {
            [$level1, $level2, $level3] = $levels;
            unset($scopeData[$level1][$level2][$level3]);
        }

        foreach ($scopeData as $level1 => $l2scopeData) {
            foreach ($l2scopeData as $level2 => $l3scopeData) {
                if (count($l3scopeData) === 0) {
                    unset($scopeData[$level1][$level2]);
                    unset($l2scopeData[$level2]);
                }
            }
            if (count($l2scopeData) === 0) {
                unset($scopeData[$level1]);
            }
        }

        return $scopeData;
    }

    private function cleanRecursion(array $levelScopeData, array $configData, array $levels = []): array
    {
        $pathsForCleanup = [];
        foreach ($levelScopeData as $level => $value) {
            if (count($levels) < 2) {
                $levels[] = $level;
                $paths = $this->cleanRecursion($value, $configData, $levels);
                $pathsForCleanup = array_merge($pathsForCleanup, $paths);
                array_pop($levels);
                continue;
            }

            [$level1, $level2] = $levels;
            $configValue = $configData[$level1][$level2][$level] ?? '__not set __'; // unique string
            if ($configValue !== '__not set __' && $configValue == $value) {
                $path = $levels;
                $path[] = $level;
                $pathsForCleanup[] = $path;
            }
        }



        return $pathsForCleanup;
    }
}
