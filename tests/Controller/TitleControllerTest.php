<?php

namespace App\Tests\Controller;

use App\Entity\Title;
use App\Enum\TitleType;
use App\Repository\TitleRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TitleControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Every test starts with an empty table, so no test depends on what another one left behind.
        $this->titleRepository()->createQueryBuilder('title')->delete()->getQuery()->execute();
    }

    public function testListReturnsTitlesNewestFirst(): void
    {
        $this->addTitle('Dune', TitleType::Film, 2021);
        $this->addTitle('Severance', TitleType::Series, 2022);

        $this->client->request('GET', '/api/titles');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame(['Severance', 'Dune'], array_column($this->responseData(), 'name'));
    }

    public function testListAppliesFilters(): void
    {
        $this->addTitle('Dune', TitleType::Film, 2021, true);
        $this->addTitle('Dune: Part Two', TitleType::Film, 2024);
        $this->addTitle('Severance', TitleType::Series, 2022, true);

        $this->client->request('GET', '/api/titles?type=film&watched=false&search=DUNE');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Dune: Part Two'], array_column($this->responseData(), 'name'));
    }

    #[DataProvider('provideInvalidFilters')]
    public function testListRejectsInvalidFilter(string $query): void
    {
        $this->client->request('GET', '/api/titles'.$query);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public static function provideInvalidFilters(): iterable
    {
        yield 'unknown type' => ['?type=flim'];
        yield 'watched is not a boolean' => ['?watched=maybe'];
    }

    public function testCreateReturnsTheNewTitle(): void
    {
        $this->client->jsonRequest('POST', '/api/titles', ['name' => '  Arrival  ', 'type' => 'film', 'year' => 2016]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->responseData();
        $this->assertIsInt($created['id']);
        $this->assertSame('Arrival', $created['name']);
        $this->assertFalse($created['watched']);
        $this->assertSame(['Arrival'], $this->savedNames());
    }

    #[DataProvider('provideInvalidTitles')]
    public function testCreateRejectsInvalidInput(array $input, string $invalidField): void
    {
        $this->client->jsonRequest('POST', '/api/titles', $input);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey($invalidField, $this->responseData()['errors']);
        $this->assertSame([], $this->savedNames());
    }

    public static function provideInvalidTitles(): iterable
    {
        $valid = ['name' => 'Dune', 'type' => 'film', 'year' => 2021];

        yield 'missing name' => [['type' => 'film', 'year' => 2021], 'name'];
        yield 'name of only spaces' => [['name' => '   '] + $valid, 'name'];
        yield 'name longer than 255 characters' => [['name' => str_repeat('a', 256)] + $valid, 'name'];
        yield 'unknown type' => [['type' => 'flim'] + $valid, 'type'];
        yield 'year before 1900' => [['year' => 1899] + $valid, 'year'];
        yield 'negative year' => [['year' => -1] + $valid, 'year'];
        yield 'year too far in the future' => [['year' => (int) date('Y') + 6] + $valid, 'year'];
        yield 'year as text' => [['year' => '2021'] + $valid, 'year'];
        yield 'watched is not a boolean' => [['watched' => 'yes'] + $valid, 'watched'];
    }

    public function testCreateRejectsBodyThatIsNotJson(): void
    {
        $this->client->request('POST', '/api/titles', server: ['CONTENT_TYPE' => 'application/json'], content: '{not json');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateRefusesDuplicate(): void
    {
        $this->addTitle('Dune', TitleType::Film, 2021);

        $this->client->jsonRequest('POST', '/api/titles', ['name' => ' dune ', 'type' => 'film', 'year' => 2021]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertSame('This title is already on your list.', $this->responseData()['errors']['name']);
        $this->assertSame(['Dune'], $this->savedNames());
    }

    public function testUpdateWatchedSavesTheChange(): void
    {
        $id = $this->addTitle('Dune', TitleType::Film, 2021);

        $this->client->jsonRequest('PATCH', "/api/titles/$id", ['watched' => true]);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->responseData()['watched']);
        $this->assertSame(['Dune'], $this->savedNames(watched: true));
    }

    public function testUpdateWatchedRejectsValueThatIsNotABoolean(): void
    {
        $id = $this->addTitle('Dune', TitleType::Film, 2021);

        $this->client->jsonRequest('PATCH', "/api/titles/$id", ['watched' => 'yes']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame([], $this->savedNames(watched: true));
    }

    public function testDeleteRemovesTheTitle(): void
    {
        $id = $this->addTitle('Dune', TitleType::Film, 2021);

        $this->client->request('DELETE', "/api/titles/$id");

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertSame('', $this->client->getResponse()->getContent());
        $this->assertSame([], $this->savedNames());
    }

    #[DataProvider('provideRequestsForUnknownTitle')]
    public function testUnknownTitleReturns404(string $method): void
    {
        $this->client->jsonRequest($method, '/api/titles/999999', ['watched' => true]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public static function provideRequestsForUnknownTitle(): iterable
    {
        yield 'switch watched' => ['PATCH'];
        yield 'delete' => ['DELETE'];
    }

    // The client restarts the app between requests, so a fresh repository is fetched every time.
    private function titleRepository(): TitleRepository
    {
        return static::getContainer()->get(TitleRepository::class);
    }

    private function addTitle(string $name, TitleType $type, int $year, bool $watched = false): int
    {
        $title = new Title($name, $type, $year, $watched);
        $this->titleRepository()->save($title);

        return $title->jsonSerialize()['id'];
    }

    /**
     * @return string[] the names of the titles that are really in the database
     */
    private function savedNames(?bool $watched = null): array
    {
        $titles = $this->titleRepository()->findByFilters(null, $watched, null);

        return array_map(fn (Title $title) => $title->jsonSerialize()['name'], $titles);
    }

    private function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
