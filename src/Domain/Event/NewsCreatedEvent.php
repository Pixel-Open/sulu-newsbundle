<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Domain\Event;

use Pixel\NewsBundle\Entity\News;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class NewsCreatedEvent extends DomainEvent
{
    use NewsEventTrait;

    public function __construct(News $news, array $payload)
    {
        parent::__construct();
        $this->news = $news;
        $this->payload = $payload;
    }

    public function getEventType(): string
    {
        return 'created';
    }
}
