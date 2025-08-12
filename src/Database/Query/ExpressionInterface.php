<?php

namespace Forge\Database\Query;

use Forge\Database\Grammar;

interface ExpressionInterface
{
    /**
     * Get the value of the expression.
     *
     * @param  \Forge\Database\Grammar  $grammar
     * @return string|int|float
     */
    public function getValue(Grammar $grammar);
}
