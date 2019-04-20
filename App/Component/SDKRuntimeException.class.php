<?php

namespace DevSxe\Application\Component\Wx;

/**
 * 异常捕获
 */
class SDKRuntimeException extends \Exception
{

    public function errorMessage()
    {
        //return $this->getMessage();
    }

}
