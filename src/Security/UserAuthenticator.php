<?php

namespace App\Security;

use App\Controller\SecurityController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new RememberMeBadge(),
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            $session = $request->getSession();
            $role = $token->getUser()->getRoles();
            $idUser = array_values((array)$token->getUser())[0];
            $email =  array_values((array)$token->getUser())[1];
           
            $session->set('idUser', $idUser);
            $session->set('roles', $role[0]);
            $session->set('email', $email);
            
            return new RedirectResponse($targetPath);
        }
       /*  $session = new Session();
        $roles = $token->getRoleNames();
        $rolesTab = array_map(function ($role) {
            return $role;
        }, $roles);
        $session->set('roles',$token->getUser()->getRoles());
        $session->set('userConnect',$token->getUser());

        if (in_array('ROLE_CLIENT', $rolesTab, true)) {
            $redirection = new RedirectResponse($this->urlGenerator->generate('mes_commandes'));
        } elseif(in_array('ROLE_GESTIONNAIRE', $rolesTab, true)) {
            $redirection = new RedirectResponse($this->urlGenerator->generate('dashboard'));
        } */
        // For example:
        return new RedirectResponse($this->urlGenerator->generate('rediriger'));
        //throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
