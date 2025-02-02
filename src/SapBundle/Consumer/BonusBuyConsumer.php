<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\SapBundle\Consumer;

use FourPaws\SapBundle\Dto\In\Shares\BonusBuy;
use FourPaws\SapBundle\Service\Shares\SharesService;
use RuntimeException;

/**
 * Class BonusBuyConsumer
 *
 * @package FourPaws\SapBundle\Consumer
 */
class BonusBuyConsumer extends SapConsumerBase
{
    /**
     * @var SharesService
     */
    private $sharesService;

    /**
     * BonusBuyConsumer constructor.
     *
     * @param SharesService $sharesService
     */
    public function __construct(SharesService $sharesService)
    {
        $this->sharesService = $sharesService;
    }

    /**
     * Consume bonus buy promo actions
     *
     * @param $data
     *
     * @throws RuntimeException
     * @return bool
     */
    public function consume($data): bool
    {
        $success = false;
        if ($this->support($data)) {
            $this->log()->info('Импортируется акция из Bonus Buy');

            try {
                $success = true;

                $this->sharesService->import($data, $success);
            } catch (\Exception $e) {
                $success = false;

                $this->log()->error(\sprintf('Импортируется акции: %s', $e->getMessage()));
            }
        }

        return $success;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function support($data): bool
    {
        return \is_object($data) && $data instanceof BonusBuy;
    }
}
