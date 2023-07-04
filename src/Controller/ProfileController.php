<?php

namespace App\Controller;

use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    private BillingClient $billingClient;
    private Security $security;

    public function __construct(Security $security, BillingClient $billingClient)
    {
        $this->security = $security;
        $this->billingClient = $billingClient;
    }
    /**
     * @Route("/profile", name="app_profile")
     */
    public function index(): Response
    {
        $user = $this->billingClient->getCurrentUser($this->getUser()->getApiToken());
        $transactions = $this->billingClient->getTransactions($this->getUser()->getApiToken());

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'transactions' => $transactions,
        ]);
    }
}
