<?php
declare(strict_types=1);

namespace App\Application\Transport;

use App\Domain\DTO\AbstractProductDTO;
use App\Domain\DTO\ConcreteProductDTO;

interface TransportInterface
{
    public function dispatch(AbstractProductDTO|ConcreteProductDTO $DTO): bool;

}