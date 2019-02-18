<?php

namespace Evapotrans\ValueObjects;

class Unit
{
    public $name;

    /**
     * Unit constructor.
     *
     * @param $name
     */
    public function __construct(string $name)
    {
        $this->name = mb_strtolower($name);
    }

    /**
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function equal(Unit $unit): bool
    {
        return $unit->getName() === $this->getName();
    }

}
