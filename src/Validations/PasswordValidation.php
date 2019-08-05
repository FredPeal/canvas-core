<?php
declare(strict_types=1);

namespace Canvas\Validations;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Confirmation;
use Phalcon\Validation\Validator\StringLength;
use Exception;

class PasswordValidation
{
    /**
     * Validate the password given.
     *
     * @param string $newPassword
     * @param string $verifyPassword
     * @return boolean
     */
    public static function validate(string $newPassword, string $verifyPassword): bool
    {
        $data = [
            'new_password' => $newPassword,
            'verify_password' => $verifyPassword,
        ];

        //Ok let validate user password
        $validation = new Validation();

        $validation->add(
            'new_password',
            new PresenceOf([
                'message' => 'The password is required.'
            ])
        );

        $validation->add(
            'new_password',
            new StringLength([
                'min' => 8,
                'messageMinimum' => 'Password is too short. Minimum 8 characters.',
            ])
        );

        $validation->add(
            'new_password',
            new Confirmation([
                'message' => 'New password and confirmation do not match.',
                'with' => 'verify_password',
            ])
        );

        //validate this form for password
        $messages = $validation->validate($data);
        if (count($messages)) {
            foreach ($messages as $message) {
                throw new Exception($message->getMessage());
            }
        }

        return true;
    }
}