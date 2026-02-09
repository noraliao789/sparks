<?php

namespace App\Models\Traits;

/**
 * @author Ruby
 */
trait UnSerializeDate
{
    protected string $datetimeFormat = 'Y-m-d H:i:s';

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->datetimeFormat);
    }
}
