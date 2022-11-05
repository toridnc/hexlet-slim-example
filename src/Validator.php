<?php

namespace App;

class Validator
{
    public function validate(array $form)
    {
        $errors = [];
        if ($form['name'] === '') {
            $errors['name'] = "Can't be blank";
        }

        if ($form['email'] === '') {
            $errors['email'] = "Can't be blank";
        }

        return $errors;
    }
}