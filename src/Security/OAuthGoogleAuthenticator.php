<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OAuthGoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{

    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserPasswordHasherInterface
     */
    private $userPasswordHasher;

    /**
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     */
    public function __construct(
      ClientRegistry $clientRegistry,
      EntityManagerInterface $em,
      UserRepository $userRepository,
      UserPasswordHasherInterface $userPasswordHasher
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return RedirectResponse|Response
     */
    public function start(
      Request $request,
      AuthenticationException $authException = null
    ): Response {
        return new RedirectResponse(
          '/connect/',
          Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'google_auth';
    }

    /**
     * @param Request $request
     *
     * @return AccessToken|mixed
     */
    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getGoogleClient());
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User|null|UserInterface
     */
    public function getUser(
      $credentials,
      UserProviderInterface $userProvider
    ): UserInterface|User|null {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->getGoogleClient()
          ->fetchUserFromToken($credentials);

        $email = $googleUser->getEmail();

        /** @var User $existingUser */
        $existingUser = $this->userRepository
          ->findOneBy(['clientId' => $googleUser->getId()]);

        if ($existingUser) {
            return $existingUser;
        }

        /** @var User $user */
        $user = $this->userRepository
          ->findOneBy(['email' => $email]);

        if (!$user) {
            $plainPassword = "test";
            $user = User::fromGoogleRequest(
              $googleUser->getId(),
              $email,
              $googleUser->getName(),
              $this->userPasswordHasher->hashPassword($user, $plainPassword)
            );

            // encode the plain password
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return null|Response|void
     */
    public function onAuthenticationFailure(
      Request $request,
      AuthenticationException $exception
    ): ?Response {
        return null;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     *
     * @return null|Response
     */
    public function onAuthenticationSuccess(
      Request $request,
      TokenInterface $token,
      $providerKey
    ): ?Response {
        return null;
    }

    /**
     * @return \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface
     */
    public function getGoogleClient(
    ): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('google');
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->getGoogleClient();
        $accessToken = $this->fetchAccessToken($client);

        $googleUser = $this->getGoogleClient()
          ->fetchUserFromToken($accessToken);
        //dd($googleUser);

        $email = $googleUser->getEmail();

        $existingUser = $this->userRepository
          ->findOneBy(['clientId' => $googleUser->getId()]);

        if ($existingUser) {
            $user = $existingUser;
        }

        else {
            $user = $this->userRepository
              ->findOneBy(['email' => $email]);
        }

        if (!$user) {
            $plainPassword = "test";
            $user = User::fromGoogleRequest(
              $googleUser->getId(),
              $email,
              $googleUser->getName(),
              "test"
            );

            $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
            $this->em->persist($user);
            $this->em->flush();
        }

        //dd($user);

        return new SelfValidatingPassport(
          new UserBadge($user->getUserIdentifier())
        );

        //        return new SelfValidatingPassport(
        //          new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
        //              /** @var GoogleUser $googleUser */
        //              //$facebookUser = $client->fetchUserFromToken($accessToken);
        //              $googleUser = $this->getGoogleClient()
        //                ->fetchUserFromToken($accessToken);
        //
        //              //dd($googleUser);
        //
        //              $email = $googleUser->getEmail();
        //
        //              // 1) have they logged in with Facebook before? Easy!
        //              $existingUser = $this->userRepository
        //                ->findOneBy(['clientId' => $googleUser->getId()]);
        //
        //              if ($existingUser) {
        //                  return $existingUser;
        //              }
        //
        //              // 2) do we have a matching user by email?
        //              $user = $this->userRepository
        //                ->findOneBy(['email' => $email]);
        //
        //              if (!$user) {
        //                  $user = User::fromGoogleRequest(
        //                    $googleUser->getId(),
        //                    $email,
        //                    $googleUser->getName()
        //                  );
        //
        //                  $this->em->persist($user);
        //                  $this->em->flush();
        //              }
        //
        //              //return $user;
        //          })
        //        );
    }

}