<?php

namespace FourPaws\MobileApiBundle\Dto\Response;

use FourPaws\MobileApiBundle\Dto\Parts\CaptchaId;
use FourPaws\MobileApiBundle\Dto\Parts\FeedbackText;

class CaptchaVerifyResponse
{
    use FeedbackText, CaptchaId;
}
