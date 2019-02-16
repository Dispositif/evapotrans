<?php
/**
 * Created by phil
 * 2019-02-15 15:29
 */

namespace Evapotrans\ValueObjects;

class Unity
{
    public $unity;

    /**
     * Unity constructor.
     *
     * @param $unity
     */
    public function __construct(string $unity)
    {
        $this->unity = $unity;
    }

    /**
     * @return mixed
     */
    public function getUnity(): string
    {
        return $this->unity;
    }

}
