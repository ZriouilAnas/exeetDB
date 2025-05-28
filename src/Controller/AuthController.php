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
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    #[OA\Post(
        path: '/api/login_check',
        summary: 'Connexion utilisateur avec JWT',
        description: 'Authentifie un utilisateur et retourne un token JWT. Ce token doit être utilisé dans le header Authorization pour les routes protégées.',
        tags: ['Authentification']
    )]
    #[OA\RequestBody(
        description: 'Identifiants de connexion',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['username', 'password'],
            properties: [
                new OA\Property(
                    property: 'username', 
                    type: 'string', 
                    format: 'email',
                    description: 'Adresse email de l\'utilisateur',
                    example: 'admin@exeet.com'
                ),
                new OA\Property(
                    property: 'password', 
                    type: 'string',
                    description: 'Mot de passe de l\'utilisateur',
                    example: 'motdepasse123'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Connexion réussie - Token JWT généré',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'token', 
                    type: 'string',
                    description: 'Token JWT à utiliser pour l\'authentification',
                    example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzQ4MjU2MDAsImV4cCI6MTYzNDgyOTIwMCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoiYWRtaW5AZXhlZXQuY29tIn0...'
                ),
                new OA\Property(
                    property: 'refresh_token', 
                    type: 'string',
                    description: 'Token de renouvellement pour générer de nouveaux JWT sans redemander les identifiants',
                    example: 'refresh_abc123def456ghi789jkl012mno345pqr678stu901vwx234yz567'
                ),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    description: 'Informations de l\'utilisateur connecté',
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'admin@exeet.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Admin Exeet'),
                        new OA\Property(
                            property: 'roles', 
                            type: 'array', 
                            items: new OA\Items(type: 'string'),
                            example: ['ROLE_USER', 'ROLE_ADMIN']
                        )
                    ]
                ),
                new OA\Property(
                    property: 'expires_at',
                    type: 'integer',
                    description: 'Timestamp d\'expiration du token (1h par défaut)',
                    example: 1634829200
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Identifiants incorrects',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 401),
                new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials.')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données manquantes ou invalides',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 400),
                new OA\Property(property: 'message', type: 'string', example: 'Bad Request')
            ]
        )
    )]
    public function login(): void
    {
        // Cette méthode ne sera jamais appelée car elle est interceptée par le firewall
        // La documentation OpenAPI ci-dessus décrit le comportement du système d'authentification JWT
        throw new \Exception('Cette méthode ne devrait jamais être appelée directement.');
    }

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

    #[Route('/api/refresh-token', name: 'api_refresh_token', methods: ['POST'])]
    #[OA\Post(
        path: '/api/refresh-token',
        summary: 'Renouvellement du token JWT',
        description: 'Génère un nouveau token JWT à partir d\'un refresh token valide. Permet de maintenir la session utilisateur sans redemander les identifiants.',
        tags: ['Authentification']
    )]
    #[OA\RequestBody(
        description: 'Refresh token pour générer un nouveau JWT',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['refresh_token'],
            properties: [
                new OA\Property(
                    property: 'refresh_token', 
                    type: 'string',
                    description: 'Token de renouvellement obtenu lors de la connexion',
                    example: 'refresh_abc123def456ghi789jkl012mno345pqr678stu901vwx234yz'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Token renouvelé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Token renouvelé avec succès'),
                new OA\Property(
                    property: 'token', 
                    type: 'string',
                    description: 'Nouveau token JWT',
                    example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MzQ4MjU2MDAsImV4cCI6MTYzNDgyOTIwMH0...'
                ),
                new OA\Property(
                    property: 'refresh_token', 
                    type: 'string',
                    description: 'Nouveau refresh token (optionnel - rotation des refresh tokens)',
                    example: 'refresh_new123abc456def789ghi012jkl345mno678pqr901stu234vwx'
                ),
                new OA\Property(
                    property: 'expires_at',
                    type: 'integer',
                    description: 'Timestamp d\'expiration du nouveau token',
                    example: 1634829200
                ),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    description: 'Informations de l\'utilisateur',
                    properties: [
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
        response: 401,
        description: 'Refresh token invalide ou expiré',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Refresh token invalide ou expiré'),
                new OA\Property(property: 'error_code', type: 'string', example: 'INVALID_REFRESH_TOKEN'),
                new OA\Property(property: 'note', type: 'string', example: 'Veuillez vous reconnecter via /api/login_check')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Refresh token manquant',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Refresh token manquant'),
                new OA\Property(property: 'error_code', type: 'string', example: 'MISSING_REFRESH_TOKEN')
            ]
        )
    )]
    public function refreshToken(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);

            if (!$data || empty($data['refresh_token'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Refresh token manquant',
                    'error_code' => 'MISSING_REFRESH_TOKEN'
                ], Response::HTTP_BAD_REQUEST);
            }

            $refreshToken = $data['refresh_token'];

            // Valider le format du refresh token
            if (!$this->isValidRefreshTokenFormat($refreshToken)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Format de refresh token invalide',
                    'error_code' => 'INVALID_REFRESH_TOKEN_FORMAT'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Trouver l'utilisateur par le refresh token
            $user = $this->findUserByRefreshToken($refreshToken, $entityManager);

            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'Refresh token invalide ou expiré',
                    'error_code' => 'INVALID_REFRESH_TOKEN',
                    'note' => 'Veuillez vous reconnecter via /api/login_check'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Générer un nouveau token JWT
            $newToken = $this->generateJwtToken($user);
            $expiresAt = time() + 3600; // 1 heure

            // Optionnel : Générer un nouveau refresh token (rotation)
            $newRefreshToken = $this->generateNewRefreshToken($user, $entityManager);

            return $this->json([
                'success' => true,
                'message' => 'Token renouvelé avec succès',
                'token' => $newToken,
                'refresh_token' => $newRefreshToken,
                'expires_at' => $expiresAt,
                'user' => [
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'roles' => $user->getRoles()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du renouvellement du token',
                'error_code' => 'REFRESH_ERROR',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Valide le format du refresh token
     */
    private function isValidRefreshTokenFormat(string $token): bool
    {
        // Refresh token format: refresh_[64 caractères alphanumériques]
        return preg_match('/^refresh_[a-zA-Z0-9]{64}$/', $token) === 1;
    }

    /**
     * Trouve un utilisateur par son refresh token
     */
    private function findUserByRefreshToken(string $refreshToken, EntityManagerInterface $entityManager): ?User
    {
        // Pour cette démo, nous simulons le stockage du refresh token
        // En production, vous devriez avoir une table refresh_tokens ou stocker dans User
        
        // Simuler la recherche par refresh token
        // Dans votre implémentation, vous devriez :
        // 1. Avoir une table refresh_tokens avec user_id, token, expires_at
        // 2. Ou ajouter un champ refresh_token dans la table User
        
        // Pour cet exemple, nous extrayons l'email du token (SIMULATION)
        $email = $this->extractEmailFromRefreshToken($refreshToken);
        
        if (!$email) {
            return null;
        }

        return $entityManager->getRepository(User::class)->findOneByEmail($email);
    }

    /**
     * Extrait l'email du refresh token (SIMULATION)
     * En production, vous utiliseriez une vraie base de données
     */
    private function extractEmailFromRefreshToken(string $refreshToken): ?string
    {
        // Ceci est une SIMULATION pour la démo
        // En réalité, vous interrogeriez votre base de données
        
        // Format simulé: refresh_[base64(email)]_[random]
        if (strpos($refreshToken, 'refresh_') !== 0) {
            return null;
        }
        
        // Pour la démo, accepter n'importe quel refresh token et retourner un email par défaut
        // EN PRODUCTION : Interroger la base de données !
        return 'admin@exeet.com'; // SIMULATION SEULEMENT
    }

    /**
     * Génère un nouveau token JWT (SIMULATION)
     * En production, utilisez le service JWT de Lexik
     */
    private function generateJwtToken(User $user): string
    {
        // SIMULATION d'un token JWT
        // En production, vous utiliseriez le JWTManager de LexikJWTAuthenticationBundle
        
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'iat' => time(),
            'exp' => time() + 3600
        ]));
        $signature = base64_encode('SIMULATION_SIGNATURE');
        
        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Génère un nouveau refresh token
     */
    private function generateNewRefreshToken(User $user, EntityManagerInterface $entityManager): string
    {
        // Générer un nouveau refresh token
        $newRefreshToken = 'refresh_' . bin2hex(random_bytes(32));
        
        // EN PRODUCTION : Sauvegarder en base de données
        // $refreshTokenEntity = new RefreshToken();
        // $refreshTokenEntity->setUser($user);
        // $refreshTokenEntity->setToken($newRefreshToken);
        // $refreshTokenEntity->setExpiresAt(new \DateTimeImmutable('+30 days'));
        // $entityManager->persist($refreshTokenEntity);
        // $entityManager->flush();
        
        return $newRefreshToken;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/logout',
        summary: 'Déconnexion utilisateur',
        description: 'Déconnecte l\'utilisateur. Pour JWT, il est recommandé de supprimer le token côté client. Cette route fournit des instructions et confirme la déconnexion.',
        tags: ['Authentification'],
        security: [['Bearer' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: 'Déconnexion réussie',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Déconnexion réussie'),
                new OA\Property(
                    property: 'instructions',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'client_action', type: 'string', example: 'Supprimez le token JWT de votre stockage local'),
                        new OA\Property(property: 'token_expiry', type: 'string', example: 'Le token expirera automatiquement dans 1 heure'),
                        new OA\Property(property: 'next_step', type: 'string', example: 'Utilisez /api/login_check pour vous reconnecter')
                    ]
                ),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    description: 'Utilisateur qui s\'est déconnecté',
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'admin@exeet.com'),
                        new OA\Property(property: 'nom', type: 'string', example: 'Admin Exeet')
                    ]
                ),
                new OA\Property(property: 'logout_time', type: 'string', format: 'date-time', example: '2025-05-28T14:30:00+00:00')
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
                new OA\Property(property: 'message', type: 'string', example: 'JWT Token not found'),
                new OA\Property(property: 'note', type: 'string', example: 'Vous êtes déjà déconnecté ou le token a expiré')
            ]
        )
    )]
    public function logout(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'JWT Token not found',
                'note' => 'Vous êtes déjà déconnecté ou le token a expiré'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Pour JWT, la déconnexion côté serveur n'est pas nécessaire
        // Le token expire automatiquement selon sa durée de vie
        return $this->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
            'instructions' => [
                'client_action' => 'Supprimez le token JWT de votre stockage local',
                'token_expiry' => 'Le token expirera automatiquement dans 1 heure',
                'next_step' => 'Utilisez /api/login_check pour vous reconnecter'
            ],
            'user' => [
                'email' => $user->getEmail(),
                'nom' => $user->getNom()
            ],
            'logout_time' => (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM)
        ]);
    }

    #[Route('/api/test-auth', name: 'api_test_auth', methods: ['GET'])]
    #[OA\Get(
        path: '/api/test-auth',
        summary: 'Test de l\'authentification JWT',
        description: 'Route de test pour vérifier si l\'authentification JWT fonctionne correctement. Utile pour debugger les problèmes de token.',
        tags: ['Authentification'],
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