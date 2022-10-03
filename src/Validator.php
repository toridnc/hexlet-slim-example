<?php

namespace App;

class Validator implements ValidatorInterface
{
    public function validate(array $user)
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank";
        }

        return $errors;
    }
}