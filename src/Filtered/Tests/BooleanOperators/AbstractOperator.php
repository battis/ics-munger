<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Filtered\FilterException;
use Battis\IcsMunger\Filtered\Tests\AbstractTest;


abstract class AbstractOperator extends AbstractTest
{
    /**
     * @var AbstractTest[]
     */
    protected $tests;

    /**
     * AbstractOperator constructor.
     * @param AbstractTest[] $tests
     * @throws OperatorException
     * @throws FilterException
     */
    public function __construct(array $tests)
    {
        parent::__construct([]);
        foreach ($tests as $test) {
            if (!($test instanceof AbstractTest)) {
                throw new OperatorException('Expected Test[], but array included ' . gettype($test));
            }
        }
        $this->tests = $tests;
    }
}
