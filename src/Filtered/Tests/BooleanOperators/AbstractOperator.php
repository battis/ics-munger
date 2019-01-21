<?php


namespace Battis\IcsMunger\Filtered\Tests\BooleanOperators;


use Battis\IcsMunger\Filtered\Tests\AbstractTest;
use Exception;

abstract class AbstractOperator extends AbstractTest
{
    /**
     * @var AbstractTest[]
     */
    protected $tests;

    /**
     * AbstractOperator constructor.
     * @param AbstractTest[] $tests
     * @throws Exception
     */
    public function __construct(array $tests)
    {
        parent::__construct([]);
        foreach ($tests as $test) {
            if (!($test instanceof AbstractTest)) {
                throw new Exception('Expected Test[], but array included ' . gettype($test));
            }
        }
        $this->tests = $tests;
    }
}
