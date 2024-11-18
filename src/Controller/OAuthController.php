<?php

declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController extends AbstractController
{
    /**
     * @param ClientRegistry $clientRegistry
     *
     * @return RedirectResponse
     */
    #[Route("/connect/google", name: "connect_google_start")]
    public function redirectToGoogleConnect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
          ->getClient('google')
          ->redirect([
            'email', 'profile'
          ]);
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    #[Route("/google/auth", name: "google_auth")]
    public function connectGoogleCheck(): JsonResponse|RedirectResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['status' => false, 'message' => "User not found!"]);
        } else {
            return $this->redirectToRoute('app_default');
        }
    }
}