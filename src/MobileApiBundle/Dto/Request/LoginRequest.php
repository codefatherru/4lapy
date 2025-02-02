<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\MobileApiBundle\Dto\Request;

use FourPaws\MobileApiBundle\Dto\Parts\Captcha;
use FourPaws\MobileApiBundle\Dto\Parts\Login;
use FourPaws\MobileApiBundle\Dto\Request\Types\PostRequest;
use FourPaws\MobileApiBundle\Dto\Request\Types\SimpleUnserializeRequest;

/**
 * Class UserLoginRequest
 *
 * @package FourPaws\MobileApiBundle\Dto\Request
 */
class LoginRequest implements SimpleUnserializeRequest, PostRequest
{
    use
        Captcha,
        Login;
}
