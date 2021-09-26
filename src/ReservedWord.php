<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl;

use Exception;
use sspat\ReservedWords\ReservedWords;

abstract class ReservedWord
{
    public static function check(string $word): void
    {
        static $res = null;
        if (!$res instanceof ReservedWords) {
            $res = new ReservedWords();
        }
        if ($res->isReserved($word)) {
            throw new Exception($word . ' is a reserved word in PHP');
        }
    }
}
