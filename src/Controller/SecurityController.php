<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(){
        if ($this->getUser()) {
            return $this->redirectToRoute('organization_home');
        }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder){
        if ($this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if('POST'===$request->getMethod()){
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            if($email && filter_var($email, FILTER_VALIDATE_EMAIL)){
                if(7<strlen($password)){
                    if($password===$confirmPassword){
                        $existingUser = $userRepository->findOneBy(['email'=>$email]);
                        if(null==$existingUser){
                            $user = new User();
                            $user->setEmail($email);
                            $user->setPassword($encoder->encodePassword($user, $password));
                            $manager = $this->getDoctrine()->getManager();
                            $manager->persist($user);
                            $manager->flush();
                            return $this->redirectToRoute('app_login');
                        }else{
                            $error = 'Email must be unqiue';
                        }
                    }else{
                        $error = 'Passwords must match';
                    }
                }else{
                    $error = 'Password is too short (8 character minimum)';
                }
            }else{
                $error = 'A valid email is required';
            }
        }

        return $this->render('security/register.html.twig', ['error'=>$error ?? null]);
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('organization_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('Log out');
    }
}
