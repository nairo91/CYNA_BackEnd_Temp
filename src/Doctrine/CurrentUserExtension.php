<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Filtre automatiquement les collections Order / OrderItem / Address
 * pour n'exposer que les ressources appartenant à l'utilisateur courant.
 * Les ROLE_ADMIN voient tout.
 */
final class CurrentUserExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if (null === $user) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if (Order::class === $resourceClass || Address::class === $resourceClass) {
            $queryBuilder
                ->andWhere(sprintf('%s.user = :current_user', $rootAlias))
                ->setParameter('current_user', $user);
        }

        if (OrderItem::class === $resourceClass) {
            $queryBuilder
                ->join(sprintf('%s.order', $rootAlias), 'o_for_user')
                ->andWhere('o_for_user.user = :current_user')
                ->setParameter('current_user', $user);
        }
    }
}
