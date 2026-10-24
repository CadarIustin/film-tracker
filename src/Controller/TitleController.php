<?php

namespace App\Controller;

use App\Entity\Title;
use App\Enum\TitleType;
use App\Repository\TitleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/api/titles', name: 'api_title_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $title = new Title(
            $data['name'],
            TitleType::from($data['type']),
            $data['year'],
            $data['watched'] ?? false,
        );
        $this->titleRepository->save($title);

        return $this->json($title, Response::HTTP_CREATED);
    }

    #[Route('/api/titles/{id}', name: 'api_title_delete', methods: ['DELETE'])]
    public function delete(Title $title): Response
    {
        $this->titleRepository->remove($title);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
