<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Filtered\Tests\AbstractTest;
use Battis\IcsMunger\IcsMungerException;

abstract class AbstractOperator extends AbstractTest
{
    /**
     * @var AbstractTest[]
     */
    protected $tests;

    /**
     * AbstractOperator constructor.
     * @param AbstractTest[] $tests
     * @throws IcsMungerException
     */
    public function __construct(array $tests)
    {
        parent::__construct([]);
        foreach ($tests as $test) {
            if (!($test instanceof AbstractTest)) {
                throw new IcsMungerException('Expected Test[], but array included ' . gettype($test));
            }
        }
        $this->tests = $tests;
    }
}
