<?php
/*
 * @copyright Copyright (c) ADV/web-engineering co.
 */

namespace FourPaws\StoreBundle\Command;

use Adv\Bitrixtools\Tools\Log\LazyLoggerAwareTrait;
use Bitrix\Main\Application as BitrixApplication;
use DateTime;
use FourPaws\App\Application;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Entity\UserFieldEnumValue;
use FourPaws\Helpers\TaggedCacheHelper;
use FourPaws\SaleBundle\Service\NotificationService;
use FourPaws\StoreBundle\Entity\Store;
use FourPaws\StoreBundle\Service\DeliveryScheduleService;
use FourPaws\StoreBundle\Service\ScheduleResultService;
use FourPaws\StoreBundle\Service\StoreService;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeliveryScheduleCalculate extends Command implements LoggerAwareInterface
{
    use LazyLoggerAwareTrait;

    protected const DATE_FORMAT = 'Y-m-d';

    protected const OPT_DATE = 'date';

    protected const OPT_SENDER = 'sender';

    protected const OPT_RECEIVER = 'receiver';

    protected const OPT_TRANSITION_COUNT = 'transition-count';

    /** @var ScheduleResultService */
    protected $scheduleResultService;

    /** @var DeliveryScheduleService */
    protected $deliveryScheduleService;

    /** @var StoreService */
    protected $storeService;

    /**
     * DeliveryScheduleCalculate constructor.
     *
     * @param null|string $name
     *
     * @throws ApplicationCreateException
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->scheduleResultService = Application::getInstance()->getContainer()
            ->get(ScheduleResultService::class);
        $this->storeService = Application::getInstance()->getContainer()
            ->get('store.service');
        $this->deliveryScheduleService = Application::getInstance()->getContainer()
            ->get(DeliveryScheduleService::class);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('fourpaws:store:schedulescalculate')
            ->setDescription('Recalculate all delivery schedules')
            ->addOption(
                static::OPT_DATE,
                'd',
                InputOption::VALUE_OPTIONAL,
                'Start date (' . static::DATE_FORMAT . ')'
            )
            ->addOption(
                static::OPT_SENDER,
                's',
                InputOption::VALUE_OPTIONAL,
                'Sender store'
            )
            ->addOption(
                static::OPT_RECEIVER,
                'r',
                InputOption::VALUE_OPTIONAL,
                'Receiver store'
            )
            ->addOption(
                static::OPT_TRANSITION_COUNT,
                'tc',
                InputOption::VALUE_OPTIONAL,
                'Max transition count between stores'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $d = $input->getOption(static::OPT_DATE);
        $tc = $input->getOption(static::OPT_TRANSITION_COUNT);
        $s = $input->getOption(static::OPT_SENDER);
        $receiver = $input->getOption(static::OPT_RECEIVER);

        if ($d) {
            if (!$date = DateTime::createFromFormat(static::DATE_FORMAT, $d)) {
                throw new RuntimeException(sprintf('Date must be in %s format', static::DATE_FORMAT));
            } else {
                $dateActive = (clone $date)->modify('+1 day');
                $dateDelete = (clone $date)->modify('-1 day');
            }
        } else {
            $date = new DateTime();
            $dateActive = (clone $date)->modify('+1 day');
            $dateDelete = (clone $date)->modify('-1 day');
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($tc && (((int)$tc != $tc) || $tc < 0)) {
            throw new RuntimeException('Transition count must be a positive integer value');
        }

        $start_global = microtime(true);

        /** Расчёты не сгенерируются, если для первого отправителя не будет расписаний */
        if ($s) {
            $senders = [$this->storeService->getStoreByXmlId($s)];
        } else {
            $senders = $this->storeService->getStores(StoreService::TYPE_ALL_WITH_SUPPLIERS);
        }

        if ($receiver) {
            $receivers = [$this->storeService->getStoreByXmlId($receiver)];
        } else {
            $receivers = $senders;
        }
//        $senders = [$this->storeService->getStoreByXmlId('DC01')];
//        $senders1 = $this->storeService->getStores(StoreService::TYPE_ALL_WITH_SUPPLIERS);

        $regularities = $this->scheduleResultService->getRegularityEnumAll();
        /** @var UserFieldEnumValue $regularity */
        foreach ($regularities as $regularityId => $regularity) {
            $scheduleRegularity = $this->deliveryScheduleService->getRegular()->filter(function($item) use ($regularity){
                /**
                 * @var UserFieldEnumValue $item
                 * @var UserFieldEnumValue $regularity
                 */
                return $item->getXmlId() == $regularity->getXmlId();
            })->first();

            if(!$scheduleRegularity){
                $this->log()->error(
                    sprintf("Не найдена подходящая регулярность для расписаний: %s", $regularity->getValue())
                );
                continue;
            }
            $scheduleRegularityId = $scheduleRegularity->getId();

            /** @var Store $sender */
            foreach ($senders as $i => $sender) {
                $this->sqlHeartBeat();
                BitrixApplication::getConnection()->startTransaction();
                $start = microtime(true);
                $isSuccess = false;
                $totalCreated = 0;
                $totalDeleted = 0;

                try {
                    $this->sqlHeartBeat();
                    $totalDeleted += $this->scheduleResultService->deleteResultsForSender($sender, $dateDelete, $regularityId);

                    $this->sqlHeartBeat();
                    $results = $this->scheduleResultService->calculateForSender($sender, $dateActive, $scheduleRegularityId, $tc, $receivers);

                    $this->sqlHeartBeat();
                    [$created] = $this->scheduleResultService->updateResults($results, $dateDelete);

                    $this->sqlHeartBeat();
                    $totalCreated += $created;
                    $isSuccess = true;
                } catch (\Throwable $e) {
                    $this->log()->error(
                        sprintf('Failed to calculate schedule results: %s: %s', \get_class($e), $e->getMessage()),
                        ['sender' => $sender->getXmlId()]
                    );
                    $message = sprintf('Не удалось сгенерировать расписания для %s: %s', $sender->getXmlId(),  $e->getMessage());
                    $this->sendMessageToAdmin($message);
                }

                if ($isSuccess) {
                    BitrixApplication::getConnection()->commitTransaction();
                    $this->log()->info(
                        sprintf(
                            'Task finished for %s, time: %ss. %s of %s Created: %s, deleted: %s',
                            $sender->getXmlId(),
                            round(microtime(true) - $start, 2),
                            $i,
                            count($senders),
                            $totalCreated,
                            $totalDeleted
                        )
                    );
                } else {
                    BitrixApplication::getConnection()->rollbackTransaction();
                    $this->log()->info(
                        sprintf(
                            'Task failed for %s, time: %ss. %s of %s',
                            $sender->getXmlId(),
                            round(microtime(true) - $start, 2),
                            $i,
                            count($senders)
                        )
                    );
                }
            }
        }

        $this->scheduleResultService->clearOldResults();

        TaggedCacheHelper::clearManagedCache(['catalog:store:schedule:results']);

        $this->log()->info(
            sprintf(
                'Task finished, time: %smin.',
                round((microtime(true) - $start_global) / 60, 2)
            )
        );
    }

    private function sqlHeartBeat()
    {
        BitrixApplication::getConnection()->queryExecute("SELECT CURRENT_TIMESTAMP");
    }

    /**
     * Сообщение об ошибке при генерации
     * @param $message
     */
    private function sendMessageToAdmin($message)
    {
        /** @var NotificationService $notificationService */
        $notificationService = Application::getInstance()->getContainer()->get(NotificationService::class);
        $notificationService->sendErrorMessageToAdmin("Ошибка генерации расписания", $message);
    }
}
