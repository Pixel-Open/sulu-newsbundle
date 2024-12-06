<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Domain\Event;

use Pixel\NewsBundle\Entity\News;

trait NewsEventTrait
{
    private News $news;

    private array $payload;

    public function getNews(): News
    {
        return $this->news;
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return News::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->news->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->news->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return News::SECURITY_CONTEXT;
    }
}
