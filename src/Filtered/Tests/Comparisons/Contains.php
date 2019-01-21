<?php


namespace Battis\IcsMunger\Filtered\Tests\Comparisons;


class Contains extends AbstractComparison
{

    protected function comparison(string $property): bool
    {
        return strstr($property, $this->value) !== false;
    }
}
