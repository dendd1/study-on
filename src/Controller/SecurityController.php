<?php

namespace App\Controller;

use App\Form\RegisterType;
use App\Security\AppCustomAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(BillingClient $billingClient, Security $security)
    {
        $this->billingClient = $billingClient;
        $this->security = $security;
    }
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $billingAuthenticator,
        AuthenticationUtils $authenticationUtils
    ): Response {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $token = $this->billingClient->register([
                    'username' => $form->get('email')->getData(),
                    'password' => $form->get('password')->getData()
                ]);
            } catch (CustomUserMessageAuthenticationException $e) {
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'error' => $e->getMessage(),
                ]);
            }
            $user
                ->setApiToken($token['token'])
                ->setRefreshToken($token['refresh_token']);

            return $userAuthenticator->authenticateUser(
                $user,
                $billingAuthenticator,
                $request
            );
        }
        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank 
        - it will be intercepted by the logout key on your firewall.');
    }
}
