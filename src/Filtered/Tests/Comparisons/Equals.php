<?php


namespace Battis\IcsMunger\Filtered\Tests\Comparisons;


class Equals extends AbstractComparison
{
    protected function comparison(string $property): bool
    {
        return $property === $this->value;
    }
}
