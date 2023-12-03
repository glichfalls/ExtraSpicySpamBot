<?php declare(strict_types=1);

namespace App\Exception;

use App\Entity\User\User;

class RaidGuardException extends \Exception
{

    public function __construct(
        public User $leader,
        public User $target,
        string $message = '',
    ) {
        parent::__construct($message);
    }

}
