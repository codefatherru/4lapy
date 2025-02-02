<?php

namespace FourPaws\Console\Command;

use FourPaws\Migrator\Factory;
use FourPaws\Migrator\IblockNotFoundException;
use FourPaws\Migrator\Installer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException as DeInvalidArgumentException;

/**
 * Class MigrateData
 *
 * @package FourPaws\Console\Command
 *
 * Миграция данных со старого сайта из консоли
 */
class MigrateData extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    private const ARG_MIGRATE_LIST = 'migrate-list';
    private const ARG_LIMIT        = 'limit';

    /**
     * MigrateData constructor.
     *
     * @param string $name
     *
     * @throws LogicException
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->setLogger(new Logger('Migrator', [new StreamHandler(STDOUT, Logger::DEBUG)]));
    }

    /**
     * Configure command
     *
     * @throws InvalidArgumentException
     */
    protected function configure(): void
    {
        /**
         * @todo переделать подсказку для addArgument на Reflection
         */
        $this->setName('migrate')
             ->setDescription('Migrate data via rest')
             ->addArgument(self::ARG_LIMIT,
                           InputArgument::REQUIRED,
                           'Limit of entities, 100 by default')
             ->addArgument(self::ARG_MIGRATE_LIST,
                           InputArgument::IS_ARRAY,
                           sprintf('Migration type, one or more of this: %s', implode(', ', Factory::AVAILABLE_TYPES)))
             ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force migrate (disable time period check)');
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws DeInvalidArgumentException
     * @throws IblockNotFoundException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $migratorInstaller = new Installer($this->logger);
        
        if (!$migratorInstaller->isInstalled()) {
            $this->logger->info('Migrator tables is not installed. Installing...');
            $migratorInstaller->doInstall();
        }
        
        $this->logger->info('Migration start');
        
        $limit = $input->getArgument(self::ARG_LIMIT);
        
        if ($limit && !(int)$limit) {
            $input->setArgument(self::ARG_MIGRATE_LIST, [$limit]);
            $limit = 100;
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($input->getArgument(self::ARG_MIGRATE_LIST) as $type) {
            $client = (new Factory())->getClient($type, ['limit' => $limit]);
            $client->save();
        }
        
        $this->logResult();
    }
    
    /**
     * @param        $level
     * @param string $message
     * @param array  $context
     */
    protected function log($level, $message = '', array $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
    
    /**
     * Log final result of migration
     */
    protected function logResult()
    {
        $this->log(LogLevel::INFO, 'Data migration done');
    }
}
