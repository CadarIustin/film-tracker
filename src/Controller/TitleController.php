<?php

namespace App\Controller;

use App\Repository\TitleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TitleController extends AbstractController
{
    public function __construct(private readonly TitleRepository $titleRepository)
    {
    }

    #[Route('/api/titles', name: 'api_title_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->titleRepository->findAllNewestFirst());
    }
}
