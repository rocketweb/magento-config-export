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

namespace RocketWeb\ConfigExport\Provider;

use Magento\Framework\Exception\InvalidArgumentException;

class Fetch
{
    private array $configCache = [];

    private \Magento\Framework\App\Config $config;

    public function __construct(
        \Magento\Framework\App\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function values(string $path, array $scopes): array
    {
        $levels = $this->getPathLevels($path);

        if (!$this->configCache) {
            $this->configCache = $this->config->get('system');
        }

        $values = array_fill_keys($scopes, []);
        $defaultScopeValues = $this->getScopeValues($this->configCache['default'], $levels);

        foreach ($scopes as $scope) {
            if (!isset($this->configCache[$scope])) {
                throw new InvalidArgumentException(__('Invalid scope key: ' . $scope));
            }
            if ($scope === 'default') {
                $values[$scope] = $defaultScopeValues;
                continue;
            }

            foreach ($this->configCache[$scope] as $scopeKey => $scopeData) {
                if (is_numeric($scopeKey) || in_array($scopeKey, ['admin', 'default'])) {
                    continue;
                }

                $scopeValues = $this->getScopeValues($scopeData, $levels);
                $scopeValues = $this->cleanScopeValues($scopeValues, $defaultScopeValues);

                if (count($scopeValues) > 0) {
                    $values[$scope][$scopeKey] = $scopeValues;
                }
            }
        }

        foreach ($values as $scope => $scopeData) {
            if (count($scopeData) == 0) {
                unset($values[$scope]);
            }
        }

        return $values;
    }

    protected function cleanScopeValues(array $scopeValues, array $defaultValues): array
    {
        foreach ($scopeValues as $level1 => $l1scopeData) {
            foreach ($l1scopeData as $level2 => $l2scopeData) {
                foreach ($l2scopeData as $level3 => $value) {
                    $defaultValue = $defaultValues[$level1][$level2][$level3] ?? null;
                    if ($value === $defaultValue) {
                        unset($scopeValues[$level1][$level2][$level3]);
                    }
                }

                if (count($scopeValues[$level1][$level2]) === 0) {
                    unset($scopeValues[$level1][$level2]);
                }
            }

            if (count($scopeValues[$level1]) === 0) {
                unset($scopeValues[$level1]);
            }
        }

        return  $scopeValues;
    }

    protected function getScopeValues(array $scopeData, array $levels): array
    {
        [$level1, $level2, $level3] = $levels;

        if (!isset($scopeData[$level1])) {
            return [];
        }

        $scopeData = $scopeData[$level1];
        if ($level2 === '*' && $level3 === '*') {
            return [$level1 => $scopeData];
        }

        if ($level2 !== '*' && $level3 === '*') {
            return [$level1 => [$level2 => $scopeData[$level2]]];
        }

        if ($level2 === '*' && $level3 !== '*') {
            $values = [$level1 => []];
            foreach ($scopeData as $level2 => $l3scopeData) {
                foreach ($l3scopeData as $key => $value) {
                    if ($key === $level3) {
                        $values[$level1][$level2] = $values[$level1][$level2] ?? [];
                        $values[$level1][$level2][$level3] = $value;
                    }
                }
            }
            return $values;
        }

        if (isset($scopeData[$level2]) && isset($scopeData[$level2][$level3])) {
            return [$level1 => [$level2 => [$level3 => $scopeData[$level2][$level3]]]];
        }

        return [];
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getPathLevels(string $path): array
    {
        $levels = explode('/', $path);
        $levels = array_map(function ($level) {
            $level = trim($level);
            if ($level === '') {
                $level = '*';
            }
            return $level;
        }, $levels);

        if ($levels[0] === '*') {
            throw new InvalidArgumentException(__("Path can't start with asterisk!"));
        }

        return $levels;
    }
}
