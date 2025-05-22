<?php

namespace App\Controller;

use OpenApi\Attributes as OA;

/**
 * Documentation pour les routes automatiques Lexik JWT
 * Ces routes n'existent pas réellement comme méthodes mais sont gérées par le bundle
 */
class JwtDocController
{
    /**
     * Cette méthode n'existe que pour la documentation Swagger
     * La vraie route /api/login_check est gérée automatiquement par Lexik JWT
     */
    #[OA\Post(
        path: '/api/login_check',
        summary: 'Connexion et récupération du token JWT',
        description: 'Route automatique gérée par Lexik JWT. Envoie les identifiants pour recevoir un token JWT valide.',
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
                    description: 'Adresse email de l\'utilisateur (clé "username" obligatoire)',
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
        description: 'Connexion réussie - Token JWT retourné',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'token', 
                    type: 'string', 
                    description: 'Token JWT à utiliser dans les headers Authorization',
                    example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2...'
                ),
                new OA\Property(
                    property: 'refresh_token', 
                    type: 'string', 
                    description: 'Token de rafraîchissement (si configuré)',
                    nullable: true
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Identifiants invalides',
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
        description: 'Données manquantes ou format incorrect',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'code', type: 'integer', example: 400),
                new OA\Property(property: 'message', type: 'string', example: 'Invalid JSON.')
            ]
        )
    )]
    public function loginCheckDocumentation()
    {
        // Cette méthode est uniquement pour la documentation
        // La vraie route /api/login_check est gérée par Lexik JWT
        // Elle ne sera jamais appelée
    }
}