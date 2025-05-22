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

#[OA\Tag(name: 'Authentification', description: 'Gestion des utilisateurs et authentification JWT')]
class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        description: 'Crée un nouveau compte utilisateur avec validation complète. Les rôles disponibles sont ROLE_USER (par défaut) et ROLE_ADMIN.',
        tags: ['Authentification']
    )]
    #[OA\RequestBody(
        description: 'Données de l\'utilisateur à créer',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['email', 'password', 'nom'],
            properties: [
                new OA\Property(
                    property: 'email', 
                    type: 'string', 
                    format: 'email', 
                    description: 'Adresse email unique de l\'utilisateur',
                    example: 'admin@exeet.com'
                ),
                new OA\Property(
                    property: 'password', 
                    type: 'string', 
                    minLength: 6, 
                    description: 'Mot de passe (minimum 6 caractères)',
                    example: 'motdepasse123'
                ),
                new OA\Property(
                    property: 'nom', 
                    type: 'string', 
                    minLength: 2,
                    maxLength: 100,
                    description: 'Nom complet de l\'utilisateur',
                    example: 'Admin Exeet'
                ),
                new OA\Property(
                    property: 'roles', 
                    type: 'array', 
                    items: new OA\Items(type: 'string', enum: ['ROLE_USER', 'ROLE_ADMIN']),
                    description: 'Rôles de l\'utilisateur (optionnel, ROLE_USER par défaut)',
                    example: ['ROLE_ADMIN']
                )
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
                        new OA\Property(property: 'email', type: 'string', example: 'admin@exeet.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Admin Exeet'),
                        new OA\Property(
                            property: 'roles', 
                            type: 'array', 
                            items: new OA\Items(type: 'string'),
                            example: ['ROLE_USER', 'ROLE_ADMIN']
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation des données',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Erreurs de validation'),
                new OA\Property(
                    property: 'errors', 
                    type: 'object',
                    example: [
                        'email' => 'L\'email n\'est pas valide',
                        'password' => 'Le mot de passe doit contenir au moins 6 caractères'
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'Utilisateur déjà existant',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Un utilisateur avec cet email existe déjà')
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

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/me',
        summary: 'Récupère les informations de l\'utilisateur connecté',
        description: 'Retourne les informations détaillées de l\'utilisateur authentifié via le token JWT.',
        tags: ['Authentification'],
        security: [['Bearer' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Informations utilisateur récupérées avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@exeet.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Admin Exeet'),
                        new OA\Property(
                            property: 'roles', 
                            type: 'array', 
                            items: new OA\Items(type: 'string'),
                            example: ['ROLE_USER', 'ROLE_ADMIN']
                        ),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-22 10:30:00')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Token manquant, invalide ou expiré',
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
        description: 'Route de test pour vérifier si l\'authentification JWT fonctionne correctement. Utile pour debugger les problèmes de token.',
        tags: ['Authentification', 'Tests'],
        security: [['Bearer' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Test d\'authentification réussi',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Authentification réussie !'),
                new OA\Property(property: 'authenticated', type: 'boolean', example: true),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'admin@exeet.com'),
                        new OA\Property(
                            property: 'roles', 
                            type: 'array', 
                            items: new OA\Items(type: 'string'),
                            example: ['ROLE_USER', 'ROLE_ADMIN']
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Pas d\'authentification détectée',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Pas d\'authentification détectée'),
                new OA\Property(property: 'authenticated', type: 'boolean', example: false),
                new OA\Property(property: 'user', type: 'null', example: null),
                new OA\Property(property: 'note', type: 'string', example: 'Ajoutez un header Authorization: Bearer [votre-token]')
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
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}