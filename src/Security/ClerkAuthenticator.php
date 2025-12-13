<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Response;

class ClerkAuthenticator extends AbstractAuthenticator
{
    private ClerkJwtDecoder $jwtDecoder;
    private UserRepository $userRepository;

    public function __construct(
        ClerkJwtDecoder $jwtDecoder,
        UserRepository $userRepository
    ) {
        $this->jwtDecoder = $jwtDecoder;
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        // Solo intentar autenticar si hay un Bearer token
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            // Sin token => este authenticator NO aplica
            // - En rutas PUBLIC_ACCESS: pasa como anónimo
            // - En rutas ROLE_USER: luego saltará el access_control (401/403)
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): \Symfony\Component\Security\Http\Authenticator\Passport\Passport
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader === null || !str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('Missing Authorization header');
        }

        $jwt = substr($authHeader, 7);

        try {
            $claims = $this->jwtDecoder->decode($jwt);
        } catch (\Throwable $e) {
            throw new AuthenticationException('Invalid token: ' . $e->getMessage());
        }

        $clerkUserId = $claims['sub'] ?? null;
        if ($clerkUserId === null) {
            throw new AuthenticationException('Token missing sub (Clerk user id)');
        }

        // Datos opcionales que podemos mapear al User
        $email = $claims['email'] ?? null;
        $name = $claims['first_name'] ?? null;
        $lastName = $claims['last_name'] ?? null;

        return new \Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport(
            new \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge(
                $clerkUserId,
                function (string $userIdentifier) use ($email, $name, $lastName): UserInterface {
                    // Buscar o crear User por clerk_user_id
                    $user = $this->userRepository->findOneByClerkUserId($userIdentifier);

                    if (!$user) {
                        $user = new User();
                        $user->setClerkUserId($userIdentifier);

                        if ($email !== null) {
                            $user->setEmail($email);
                        }

                        if ($name !== null) {
                            $user->setName($name);
                        } else {
                            $user->setName('Unknown');
                        }

                        if ($lastName !== null) {
                            $user->setLastName($lastName);
                        } else {
                            $user->setLastName('User');
                        }

                        // roles por defecto
                        $user->setRoles(['ROLE_USER']);

                        $this->userRepository->getEntityManager()->persist($user);
                        $this->userRepository->getEntityManager()->flush();
                    }

                    return $user;
                }
            )
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Nada especial, dejamos que siga el request normal
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            [
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_FAILED',
                    'message' => $exception->getMessage(),
                ],
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
