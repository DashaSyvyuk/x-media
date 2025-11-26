<?php

namespace App\Validator;

use App\Service\HydratorService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Order;
use SM\Factory\Factory;
use SM\SMException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class OrderStatusValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Factory $stateFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly HydratorService $hydrator
    ) {
    }

    /**
     * @param Order $value
     * @param Constraint $constraint
     *
     * @return bool
     *
     * Moving from payment pending and pay in progress to confirmed should not be allowed on the API.
     * Once something is complete moving to any other status should not be allowed
     * @throws SMException
     */
    public function validate($value, Constraint $constraint): bool
    {
        $originalEntityData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($value);
        if (empty($originalEntityData)) {
            return true;
        }

        $oldOrder = $this->hydrator->convertObjects(
            (object) $originalEntityData,
            new Order()
        );

        $orderSM = $this->stateFactory->get($oldOrder, 'simple');
        if (!$orderSM->can($value->getStatus())) {
            $this->context
                ->buildViolation(sprintf('Неможливо змінити на статус %s', Order::STATUSES[$value->getStatus()]))
                ->addViolation();

            return false;
        }

        return true;
    }
}
