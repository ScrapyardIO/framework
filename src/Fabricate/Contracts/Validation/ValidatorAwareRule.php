<?php

namespace Fabricate\Contracts\Validation;

interface ValidatorAwareRule
{
    /**
     * Set the current validator.
     *
     * @param  \Fabricate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(\Fabricate\Validation\Validator $validator);
}
