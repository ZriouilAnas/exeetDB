<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    private string $jwtSecret = 'your-super-secret-jwt-key-change-this-in-production';

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        try {
            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation des champs requis
            if (empty($data['email']) || empty($data['password']) || empty($data['nom'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Email, mot de passe et nom sont obligatoires'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneByEmail($data['email']);
            if ($existingUser) {
                return $this->json([
                    'success' => false,
                    'message' => 'Un utilisateur avec cet email existe déjà'
                ], Response::HTTP_CONFLICT);
            }

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setEmail($data['email']);
            $user->setNom($data['nom']);

            // Définir les rôles
            $roles = ['ROLE_USER']; // Rôle par défaut
            if (isset($data['roles']) && is_array($data['roles'])) {
                $roles = $data['roles'];
            }
            $user->setRoles($roles);

            // Hasher le mot de passe
            if (strlen($data['password']) < 6) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le mot de passe doit contenir au moins 6 caractères'
                ], Response::HTTP_BAD_REQUEST);
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Validation
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            // Sauvegarder
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'roles' => $user->getRoles()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        try {
            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);

            if (!$data || empty($data['email']) || empty($data['password'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Email et mot de passe requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Trouver l'utilisateur
            $user = $userRepository->findOneByEmail($data['email']);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Identifiants invalides'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Vérifier le mot de passe
            if (!$passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Identifiants invalides'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Générer le token JWT
            $payload = [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'iat' => time(), // Issued at
                'exp' => time() + (60 * 60) // Expire dans 1 heure
            ];

            $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

            return $this->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'roles' => $user->getRoles()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(Request $request, UserRepository $userRepository): JsonResponse
    {
        try {
            // Récupérer le token depuis les headers
            $authHeader = $request->headers->get('Authorization');
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token manquant'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $token = substr($authHeader, 7); // Enlever "Bearer "

            // Décoder le token
            try {
                $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
                $payload = (array) $decoded;
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token invalide'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Token expiré'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer l'utilisateur
            $user = $userRepository->find($payload['user_id']);
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], Response::HTTP_UNAUTHORIZED);
            }

            return $this->json([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'roles' => $user->getRoles()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du token',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/test-auth', name: 'api_test_auth', methods: ['GET'])]
    public function testAuth(Request $request): JsonResponse
    {
        // Route de test pour vérifier l'authentification
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader) {
            return $this->json([
                'success' => false,
                'message' => 'Pas d\'authentification - accès libre pour le moment',
                'status' => 'NO_AUTH'
            ]);
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->json([
                'success' => false,
                'message' => 'Format Bearer token requis',
                'status' => 'INVALID_FORMAT'
            ]);
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $payload = (array) $decoded;

            return $this->json([
                'success' => true,
                'message' => 'Token valide !',
                'user_data' => [
                    'user_id' => $payload['user_id'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'roles' => $payload['roles'] ?? null,
                    'expires_at' => date('Y-m-d H:i:s', $payload['exp'] ?? 0)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Token invalide',
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}