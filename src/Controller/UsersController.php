<?php

namespace App\Controller;

use App\Api\Exception\AuthException;
use App\Entity\User;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users', name: 'api_users_')]
class UsersController extends Api\ApiController
{
    /** @var class-string<User> */
    protected const ENTITY_CLASS = User::class;

    // Solo habilito findAll y findByPK, como en tu ejemplo JS
    protected array $apiMethods = ['findAll', 'findByPK'];

    protected array $writableFields = [
        'clerk_user_id',
        'email',
        'name',
        'last_name',
        'roles',
    ];

    /**
     * @param User $entity
     * @return array<string,mixed>
     */
    protected function transformEntity(object $entity): array
    {
        /** @var User $entity */
        return [
            'id' => $entity->getId(),
            'clerk_user_id' => $entity->getClerkUserId(),
            'email' => $entity->getEmail(),
            'name' => $entity->getName(),
            'last_name' => $entity->getLastName(),
            'roles' => $entity->getRoles(),
        ];
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): array
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw new AuthException('User not authenticated', 'AUTH_NOT_AUTHENTICATED');
        }

        return $this->transformEntity($user);
    }
}
