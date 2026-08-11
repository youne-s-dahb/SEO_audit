<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\EmailVerificationCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
class AuthController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['message' => 'Machi connecté.'], 401);
        }

        return $this->json([
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
        ]);
    }
    #[Route('/api/register/send-code', name: 'api_register_send_code', methods: ['POST'])]
    public function sendCode(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (!$email) {
            return $this->json(['message' => 'Email khassin.'], 400);
        }

        $errors = $validator->validate($email, new Assert\Email());
        if (count($errors) > 0) {
            return $this->json(['message' => 'Email machi valide.'], 400);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['message' => 'Kayn deja compte b had email.'], 409);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $verification = $em->getRepository(EmailVerificationCode::class)->findOneBy(['email' => $email]);
        if (!$verification) {
            $verification = new EmailVerificationCode();
            $verification->setEmail($email);
            $verification->setCreatedAt(new \DateTimeImmutable());
        }
        $verification->setCode($code);
        $verification->setVerified(false);
        $verification->setExpiresAt(new \DateTimeImmutable('+10 minutes'));

        $em->persist($verification);
        $em->flush();

        $emailMessage = (new Email())
            ->from($this->getParameter('app_mail_from'))
            ->to($email)
            ->subject('Code  confirmation')
           ->html(
                    '
                    <div style="
                        margin:0;
                        padding:40px 20px;
                        background:#f6f8fc;
                        font-family:Arial, Helvetica, sans-serif;
                    ">
                        <div style="
                            max-width:520px;
                            margin:0 auto;
                            background:#ffffff;
                            border-radius:20px;
                            padding:40px 35px;
                            text-align:center;
                            box-shadow:0 8px 30px rgba(0,0,0,0.08);
                        ">

                            <div style="
                                width:55px;
                                height:55px;
                                margin:0 auto 20px;
                                background:#eef2ff;
                                border-radius:16px;
                                line-height:55px;
                                font-size:26px;
                            ">
                                🔐
                            </div>

                            <h1 style="
                                margin:0 0 12px;
                                color:#111827;
                                font-size:24px;
                                font-weight:700;
                            ">
                                Confirmez votre adresse email
                            </h1>

                            <p style="
                                margin:0 auto 28px;
                                max-width:400px;
                                color:#6b7280;
                                font-size:15px;
                                line-height:1.6;
                            ">
                                Utilisez le code ci-dessous pour confirmer votre adresse email
                                et continuer votre inscription.
                            </p>

                            <div style="
                                background:#f8fafc;
                                border:1px solid #e5e7eb;
                                border-radius:16px;
                                padding:22px 15px;
                                margin-bottom:22px;
                            ">
                                <div style="
                                    color:#9ca3af;
                                    font-size:12px;
                                    text-transform:uppercase;
                                    letter-spacing:1.5px;
                                    margin-bottom:10px;
                                ">
                                    Votre code de vérification
                                </div>

                                <div style="
                                    color:#4f46e5;
                                    font-size:32px;
                                    font-weight:700;
                                    letter-spacing:8px;
                                ">
                                    ' . $code . '
                                </div>
                            </div>

                            <p style="
                                margin:0 0 25px;
                                color:#ef4444;
                                font-size:13px;
                            ">
                                ⏱ Ce code expire dans 10 minutes.
                            </p>

                            <div style="
                                height:1px;
                                background:#e5e7eb;
                                margin:25px 0;
                            "></div>

                            <p style="
                                margin:0;
                                color:#9ca3af;
                                font-size:12px;
                                line-height:1.6;
                            ">
                                Si vous navez pas demandé ce code, vous pouvez ignorer cet email.
                            </p>

                            <p style="
                                margin:20px 0 0;
                                color:#6b7280;
                                font-size:12px;
                            ">
                                © Seo Audit
                            </p>

                        </div>
                    </div>'
    
            );

        $mailer->send($emailMessage);

        return $this->json(['message' => 'Code tsift l email dyalek.']);
    }

    #[Route('/api/register/verify-code', name: 'api_register_verify_code', methods: ['POST'])]
    public function verifyCode(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        $code = trim($data['code'] ?? '');

        if (!$email || !$code) {
            return $this->json(['message' => 'Email o code khassin.'], 400);
        }

        $verification = $em->getRepository(EmailVerificationCode::class)->findOneBy(['email' => $email]);

        if (!$verification || $verification->getCode() !== $code) {
            return $this->json(['message' => 'Code machi sahih.'], 400);
        }

        if ($verification->getExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['message' => 'Code khlas s7ito, talab code jdid.'], 410);
        }

        $verification->setVerified(true);
        $em->flush();

        return $this->json(['message' => 'Email tconfirma.']);
    }
   
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager 
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $fullName = trim($data['full_name'] ?? '');

        if (!$email || !$password || !$fullName) {
            return $this->json(['message' => 'Kolla les champs khassin.'], 400);
        }

        if (strlen($password) < 6) {
            return $this->json(['message' => 'Password khass ykon 6 caractères o ktar.'], 400);
        }

        $verification = $em->getRepository(EmailVerificationCode::class)->findOneBy(['email' => $email]);
        if (!$verification || !$verification->isVerified()) {
            return $this->json(['message' => 'Email mazal machi mconfirmé.'], 403);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['message' => 'Kayn deja compte b had email.'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setRole('user');
        $user->setIsActive(true);
        $user->setIsVerified(true);
        $user->setPasswordHash($passwordHasher->hashPassword($user, $password));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($user);
        $em->remove($verification);
        $em->flush();

        $token = $jwtManager->create($user);

        return $this->json([
                'message' => 'Compte tsawwer b nja7!',
                'token' => $token,
        ], 201);    }

    
}
