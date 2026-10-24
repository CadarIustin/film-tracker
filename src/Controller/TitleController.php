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
    // Every action needs the repository, so autowiring injects it once, here.
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
        // Invalid JSON makes toArray() throw, and Symfony answers that with a 400.
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

    // Symfony loads the Title by {id} before this runs, and answers 404 if it does not exist.
    #[Route('/api/titles/{id}', name: 'api_title_delete', methods: ['DELETE'])]
    public function delete(Title $title): Response
    {
        $this->titleRepository->remove($title);

        // A 204 response must not have a body, so this is a plain Response and not JSON.
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
