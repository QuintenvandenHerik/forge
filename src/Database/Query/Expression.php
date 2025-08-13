<?php

namespace Forge\Database\Query;

use Forge\Contracts\Database\Query\Expression as ExpressionContract;
use Forge\Database\Grammar;

/**
 * @template TValue of string|int|float
 */
class Expression implements ExpressionContract
{
    /**
     * Create a new raw query expression.
     *
     * @param  TValue  $value
     */
    public function __construct(
        protected $value
    ) {
    }

    /**
     * Get the value of the expression.
     *
     * @param  \Forge\Database\Grammar  $grammar
     * @return TValue
     */
    public function getValue(Grammar $grammar)
    {
        return $this->value;
    }
}
