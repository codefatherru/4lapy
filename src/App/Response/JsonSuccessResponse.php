<?php

namespace FourPaws\App\Response;

use FourPaws\App\Exceptions\ApplicationCreateException;

/**
 * Class JsonSuccessResponse
 *
 * @package FourPaws\App\Model
 */
class JsonSuccessResponse extends JsonResponse
{
    /**
     * Создаётся JsonResponse с предустановленным JsonContent и success = true
     *
     * @inheritdoc
     */
    public static function create($message = null, $status = 200, $headers = [], array $options = []): JsonResponse
    {
        $content = static::buildContent($message, true, null, $options);

        return parent::create($content, $status, $headers);
    }

    /**
     * Создаётся JsonResponse с предустановленным JsonContent, data и success = true
     *
     * @param string $message
     * @param array  $data
     * @param int    $status
     *
     * @return JsonResponse
     * @throws ApplicationCreateException
     */
    public static function createWithData(
        string $message = '',
        array $data = [],
        int $status = 200,
        array $options = []
    ) : JsonResponse {
        $content = static::buildContent($message, true, $data, $options);

        return parent::create($content, $status);
    }
}
