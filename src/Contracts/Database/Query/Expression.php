<?php

namespace Forge\Contracts\Database\Query;

use Forge\Database\Grammar;

interface Expression
{
    /**
     * Get the value of the expression.
     *
     * @param  \Forge\Database\Grammar  $grammar
     * @return string|int|float
     */
    public function getValue(Grammar $grammar);
}
