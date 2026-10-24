<?php

namespace App\Tests\Entity;

use App\Entity\Title;
use App\Enum\TitleType;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    public function testNewTitleIsNotWatchedByDefault(): void
    {
        $title = new Title('Dune', TitleType::Film, 2021);

        $this->assertFalse($title->jsonSerialize()['watched']);
    }

    public function testJsonContainsAllFieldsWithTheTypeAsText(): void
    {
        $title = new Title('Severance', TitleType::Series, 2022, true);

        $this->assertSame([
            'id' => null,
            'name' => 'Severance',
            'type' => 'series',
            'year' => 2022,
            'watched' => true,
        ], $title->jsonSerialize());
    }

    public function testSetWatchedSwitchesBothWays(): void
    {
        $title = new Title('Dune', TitleType::Film, 2021);

        $title->setWatched(true);
        $this->assertTrue($title->jsonSerialize()['watched']);

        $title->setWatched(false);
        $this->assertFalse($title->jsonSerialize()['watched']);
    }

    public function testJsonEncodeUsesJsonSerialize(): void
    {
        $title = new Title('Dune', TitleType::Film, 2021);

        $this->assertSame(
            '{"id":null,"name":"Dune","type":"film","year":2021,"watched":false}',
            json_encode($title),
        );
    }
}
