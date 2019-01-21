<?php


namespace Battis\IcsMunger\Filtered\Tests\Comparisons;


class Like extends AbstractComparison
{

    /**
     * @param string $property
     * @return bool
     */
    protected function comparison(string $property): bool
    {
        return preg_match($this->value, $property) > 0;
    }
}
