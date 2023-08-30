<?php

namespace App\Services;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TokenService
{
    private $userRepository;
    private $encoder;
    private $userPasswordHasher;

    public function __construct(UserRepository $userRepository, JWTEncoderInterface $encoder, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userRepository = $userRepository;
        $this->encoder = $encoder;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function getTokenForUser(string $email, string $password): string
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if($this->userPasswordHasher->isPasswordValid($user, $password)) {
            return $this->encoder->encode([
                'username' => $user->getEmail(),
                'exp' => time() + 3600,
            ]);
        }

        throw new AuthenticationException('Credentials isnt valid');
    }
}