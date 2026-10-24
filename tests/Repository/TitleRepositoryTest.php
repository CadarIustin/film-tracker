<?php

namespace App\Tests\Repository;

use App\Entity\Title;
use App\Enum\TitleType;
use App\Repository\TitleRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TitleRepositoryTest extends KernelTestCase
{
    private TitleRepository $titleRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->titleRepository = self::getContainer()->get(TitleRepository::class);

        // Every test starts with an empty table, so no test depends on what another one left behind.
        $this->titleRepository->createQueryBuilder('title')->delete()->getQuery()->execute();
    }

    public function testListsNewestFirst(): void
    {
        $this->addTitles('Dune', 'Arrival', 'Severance');

        $this->assertSame(['Severance', 'Arrival', 'Dune'], $this->findNames());
    }

    public function testFiltersByType(): void
    {
        $this->addSampleTitles();

        $this->assertSame(['Arrival', 'Dune: Part Two', 'Dune'], $this->findNames(type: TitleType::Film));
        $this->assertSame(['Severance'], $this->findNames(type: TitleType::Series));
    }

    public function testFiltersByWatched(): void
    {
        $this->addSampleTitles();

        $this->assertSame(['Severance', 'Dune'], $this->findNames(watched: true));
        $this->assertSame(['Arrival', 'Dune: Part Two'], $this->findNames(watched: false));
    }

    public function testSearchIgnoresCaseAndMatchesPartOfTheName(): void
    {
        $this->addSampleTitles();

        $this->assertSame(['Dune: Part Two', 'Dune'], $this->findNames(search: 'DUNE'));
        $this->assertSame(['Dune: Part Two'], $this->findNames(search: 'part'));
    }

    public function testBlankSearchIsIgnored(): void
    {
        $this->addSampleTitles();

        $this->assertCount(4, $this->findNames(search: '   '));
    }

    public function testCombinesFilters(): void
    {
        $this->addSampleTitles();

        $this->assertSame(['Dune: Part Two'], $this->findNames(type: TitleType::Film, watched: false, search: 'dune'));
    }

    public function testSearchTreatsSqlAsPlainText(): void
    {
        $this->addSampleTitles();

        $this->assertSame([], $this->findNames(search: "' OR 1=1 --"));
    }

    #[DataProvider('provideDuplicateChecks')]
    public function testDuplicateCheck(string $name, TitleType $type, int $year, bool $isDuplicate): void
    {
        $this->titleRepository->save(new Title('Dune', TitleType::Film, 2021));

        $this->assertSame($isDuplicate, $this->titleRepository->existsWithNameTypeAndYear($name, $type, $year));
    }

    public static function provideDuplicateChecks(): iterable
    {
        yield 'exact copy' => ['Dune', TitleType::Film, 2021, true];
        yield 'other case and spaces' => ['  dUNE ', TitleType::Film, 2021, true];
        yield 'remake with another year' => ['Dune', TitleType::Film, 1984, false];
        yield 'series with the same name' => ['Dune', TitleType::Series, 2021, false];
        yield 'longer name' => ['Dune: Part Two', TitleType::Film, 2021, false];
    }

    private function addTitles(string ...$names): void
    {
        foreach ($names as $name) {
            $this->titleRepository->save(new Title($name, TitleType::Film, 2021));
        }
    }

    private function addSampleTitles(): void
    {
        $this->titleRepository->save(new Title('Dune', TitleType::Film, 2021, true));
        $this->titleRepository->save(new Title('Dune: Part Two', TitleType::Film, 2024));
        $this->titleRepository->save(new Title('Severance', TitleType::Series, 2022, true));
        $this->titleRepository->save(new Title('Arrival', TitleType::Film, 2016));
    }

    /**
     * @return string[] the names of the matching titles, in the order the repository returns them
     */
    private function findNames(?TitleType $type = null, ?bool $watched = null, ?string $search = null): array
    {
        $titles = $this->titleRepository->findByFilters($type, $watched, $search);

        return array_map(fn (Title $title) => $title->jsonSerialize()['name'], $titles);
    }
}
