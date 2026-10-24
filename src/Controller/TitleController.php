<?php

namespace App\Controller;

use App\Entity\Title;
use App\Enum\TitleType;
use App\Repository\TitleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class TitleController extends AbstractController
{
    private const MIN_YEAR = 1900;
    // A watchlist also holds announced titles, so a few future years are allowed.
    private const YEARS_AHEAD = 5;
    // Same as the length of the name column in Title.
    private const MAX_NAME_LENGTH = 255;

    // Every action needs the repository, so autowiring injects it once, here.
    public function __construct(private readonly TitleRepository $titleRepository)
    {
    }

    // Each filter is optional; an invalid value like ?type=flim is answered with a 400.
    #[Route('/api/titles', name: 'api_title_list', methods: ['GET'])]
    public function list(
        #[MapQueryParameter(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] ?TitleType $type = null,
        #[MapQueryParameter(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] ?bool $watched = null,
        #[MapQueryParameter] ?string $search = null,
    ): JsonResponse {
        return $this->json($this->titleRepository->findByFilters($type, $watched, $search));
    }

    #[Route('/api/titles', name: 'api_title_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Invalid JSON makes toArray() throw, and Symfony answers that with a 400.
        $data = $request->toArray();

        $errors = $this->findInputErrors($data);
        if ([] !== $errors) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);
        $type = TitleType::from($data['type']);
        // Same name, type and year is the same title; a remake has another year, so it is still allowed.
        if ($this->titleRepository->existsWithNameTypeAndYear($name, $type, $data['year'])) {
            return $this->json(
                ['errors' => ['name' => 'This title is already on your list.']],
                Response::HTTP_CONFLICT,
            );
        }

        $title = new Title($name, $type, $data['year'], $data['watched'] ?? false);
        $this->titleRepository->save($title);

        return $this->json($title, Response::HTTP_CREATED);
    }

    #[Route('/api/titles/{id}', name: 'api_title_update_watched', methods: ['PATCH'])]
    public function updateWatched(Title $title, Request $request): JsonResponse
    {
        $watched = $request->toArray()['watched'] ?? null;
        // Without this check, PHP would silently turn "yes" into true, and a missing value would cause a 500.
        if (!is_bool($watched)) {
            return $this->json(
                ['errors' => ['watched' => 'Watched must be true or false.']],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $title->setWatched($watched);
        // Doctrine already tracks this Title, so save() runs an UPDATE instead of an INSERT.
        $this->titleRepository->save($title);

        return $this->json($title);
    }

    // Symfony loads the Title by {id} before this runs, and answers 404 if it does not exist.
    #[Route('/api/titles/{id}', name: 'api_title_delete', methods: ['DELETE'])]
    public function delete(Title $title): Response
    {
        $this->titleRepository->remove($title);

        // A 204 response must not have a body, so this is a plain Response and not JSON.
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, string> one error message per invalid field; empty when the input is valid
     */
    private function findInputErrors(array $data): array
    {
        $errors = [];

        $name = $data['name'] ?? null;
        if (!is_string($name) || '' === trim($name)) {
            $errors['name'] = 'The name must not be empty.';
        } elseif (mb_strlen(trim($name)) > self::MAX_NAME_LENGTH) {
            $errors['name'] = sprintf('The name can be at most %d characters long.', self::MAX_NAME_LENGTH);
        }

        $type = $data['type'] ?? null;
        // is_string() comes first because tryFrom() throws an error when it gets an array.
        if (!is_string($type) || null === TitleType::tryFrom($type)) {
            $errors['type'] = 'The type must be "film" or "series".';
        }

        $year = $data['year'] ?? null;
        $maxYear = (int) date('Y') + self::YEARS_AHEAD;
        if (!is_int($year) || $year < self::MIN_YEAR || $year > $maxYear) {
            $errors['year'] = sprintf('The year must be a whole number from %d to %d.', self::MIN_YEAR, $maxYear);
        }

        if (isset($data['watched']) && !is_bool($data['watched'])) {
            $errors['watched'] = 'Watched must be true or false.';
        }

        return $errors;
    }
}
