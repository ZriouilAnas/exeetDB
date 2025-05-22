<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        tags: ['Authentification']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'password', 'nom'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'motdepasse123'),
                new OA\Property(property: 'nom', type: 'string', minLength: 2, example: 'Admin Exeet'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_ADMIN'])
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Utilisateur créé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Utilisateur créé avec succès'),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Admin Exeet'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Erreurs de validation'),
                new OA\Property(property: 'errors', type: 'object')
            ]
        )
    )]
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
                $roles = array_unique(array_merge($roles, $data['roles']));
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
    #[OA\Post(
        path: '/api/login',
        summary: 'Point d\'entrée pour la connexion',
        description: 'Redirige vers /api/login_check pour la connexion JWT',
        tags: ['Authentification']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['username', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', format: 'email', example: 'admin@example.com'),
                new OA\Property(property: 'password', type: 'string', example: 'motdepasse123')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Information sur l\'endpoint de connexion',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Utilisez POST /api/login_check'),
                new OA\Property(property: 'format', type: 'object', properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'votre-email@exemple.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'votre-mot-de-passe')
                ])
            ]
        )
    )]
    public function login(): JsonResponse
    {
        // Cette méthode ne sera jamais appelée car Symfony intercepte la route
        // La vraie logique de connexion est gérée par le firewall JWT
        return $this->json([
            'message' => 'Utilisez POST /api/login_check pour vous connecter',
            'format' => [
                'username' => 'votre-email@exemple.com',
                'password' => 'votre-mot-de-passe'
            ],
            'note' => 'Cette route est gérée automatiquement par Lexik JWT'
        ]);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Récupère les informations de l\'utilisateur connecté',
        tags: ['Authentification'],
        security: [['Bearer' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Informations utilisateur',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Admin Exeet'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Token manquant ou invalide',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'JWT Token not found')
            ]
        )
    )]
    public function me(): JsonResponse
    {
        // Récupérer l'utilisateur connecté via le token JWT
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'roles' => $user->getRoles(),
                'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/api/test-auth', name: 'api_test_auth', methods: ['GET'])]
    #[OA\Get(
        path: '/api/test-auth',
        summary: 'Test de l\'authentification JWT',
        description: 'Route de test pour vérifier si l\'authentification JWT fonctionne',
        tags: ['Authentification', 'Tests']
    )]
    #[OA\Response(
        response: 200,
        description: 'Test d\'authentification',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean'),
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'authenticated', type: 'boolean'),
                new OA\Property(property: 'user', type: 'object', nullable: true)
            ]
        )
    )]
    public function testAuth(): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user) {
            return $this->json([
                'success' => true,
                'message' => 'Authentification réussie !',
                'authenticated' => true,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles()
                ]
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Pas d\'authentification détectée',
                'authenticated' => false,
                'user' => null,
                'note' => 'Ajoutez un header Authorization: Bearer [votre-token]'
            ]);
        }
    }
}