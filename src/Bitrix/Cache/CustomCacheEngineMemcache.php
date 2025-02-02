<?
use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use function Opis\Closure\{serialize as s, unserialize as u};

class CustomCacheEngineMemcache extends \Bitrix\Main\Data\CacheEngineMemcache
{
    function read(&$allVars, $baseDir, $initDir, $filename, $TTL)
    {
        if (parent::read($allVars, $baseDir, $initDir, $filename, $TTL))
        {
            if (!isset($allVars['CONTENT']) && !isset($allVars['VARS']))
            {
                $allVars = u($allVars);
            }
            return true;
        }

        return false;
    }

    function write($allVars, $baseDir, $initDir, $filename, $TTL)
    {
        $secondSendInCache = false;
        try {
            parent::write($allVars, $baseDir, $initDir, $filename, $TTL);
        } catch (\Exception $e) {
            $allVars = s($allVars);
            $secondSendInCache = true;
        }

        if ($secondSendInCache) {
            try {
                parent::write($allVars, $baseDir, $initDir, $filename, $TTL);
            } catch (Exception $e) {
                LoggerFactory::create('cacheSerializer')->error(
                    sprintf('can\'t serialize cache'), [
                        'serverVar' => $_SERVER,
                        'request' => $_REQUEST,
                        'baseDir' => $baseDir,
                        'initDir' => $initDir,
                        'filename' => $filename,
                    ]
                );
            }
        }
    }
}
