<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Address;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Assigne automatiquement l'utilisateur courant à l'adresse créée.
 * Corrige la faille BOLA : le champ `user` n'est plus dans address:write.
 *
 * @implements ProcessorInterface<Address, Address>
 */
final class AddressSetOwnerProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly Security $security,
    ) {
    }

    /**
     * @param Address $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Address
    {
        $user = $this->security->getUser();
        if (null === $user) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $data->setUser($user);

        /** @var Address $persisted */
        $persisted = $this->decorated->process($data, $operation, $uriVariables, $context);

        return $persisted;
    }
}
