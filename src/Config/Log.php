<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\ReaderInterface;
use Magento\MagentoCloud\App\Logger\HandlerFactory;

/**
 * Log configuration.
 */
class Log
{
    public const SECTION_CONFIG = 'log';

    /**
     * Log levels.
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var array
     */
    private $config;

    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @param FileList $fileList
     * @param ReaderInterface $reader
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(FileList $fileList, ReaderInterface $reader, RepositoryFactory $repositoryFactory)
    {
        $this->fileList = $fileList;
        $this->reader = $reader;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * Returns array of handlers configs with keys as handler name.
     *
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->getConfig();
    }

    /**
     * @param string $handler
     * @return Repository
     * @throws \Exception
     */
    public function get(string $handler): Repository
    {
        if (!isset($this->getConfig()[$handler])) {
            throw new \Exception('Configuration for ' . $handler . ' is not found');
        }

        return $this->repositoryFactory->create(
            $this->getConfig()[$handler]
        );
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = array_replace_recursive(
                [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => $this->fileList->getCloudLog()],
                ],
                $this->reader->read()[static::SECTION_CONFIG] ?? []
            );
        }

        return $this->config;
    }
}
