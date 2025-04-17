<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api', name: 'api_')]
final class ProductController extends AbstractController
{
    #[Route('/product', name: 'product_index', methods:['get'] )]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        
        $products = $entityManager
            ->getRepository(Product::class)
            ->findAll();
    
        $data = [];
    
        foreach ($products as $product) {
           $data[] = [
               'id' => $product->getId(),
               'name' => $product->getName(),
               'description' => $product->getDescription(),
               'prix' => $product->getPrix(),
               'image' => $product->getImage(),
               'hover_image' => $product->getHoverImage(),
               'color' => $product->getColor(),
               'taille' => $product->getTaille(),
               'category' => $product->getCategory(),
               'created_at' => $product->getCreatedAt(),
               'updated_at' => $product->getUpdatedAt(),

           ];
        }
    
        return $this->json($data);
    }
}
