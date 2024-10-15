<?php
declare(strict_types=1);

namespace App\Domain\Message;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;

class ProductMessage
{
    public function __construct(
        private AbstractProductDTO|ConcreteProductDTO $content,
    )
    {
    }

    public function getContent(): AbstractProductDTO|ConcreteProductDTO
    {
        return $this->content;
    }
}
