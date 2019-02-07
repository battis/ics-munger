<?php


namespace Battis\IcsMunger\Filtered\Tests\Comparisons;


class Like extends AbstractComparison
{

    protected function comparison(string $property): bool
    {
        return preg_match($this->value, $property) > 0;
    }
}
