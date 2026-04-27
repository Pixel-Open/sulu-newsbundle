<?php

declare(strict_types=1);

namespace Pixel\NewsBundle\Reference;

use Pixel\NewsBundle\Entity\News;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollector;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class NewsReferenceProvider
{
    public function __construct(
        private ReferenceRepositoryInterface $referenceRepository,
    ) {
    }

    public function updateReferences(News $news, string $locale, string $context): void
    {
        $referenceCollector = new ReferenceCollector(
            $this->referenceRepository,
            News::RESOURCE_KEY,
            (string) $news->getId(),
            $locale,
            $news->getTitle() ?? '',
            $context,
            ['id' => $news->getId(), 'locale' => $locale],
        );

        if ($cover = $news->getCover()) {
            $referenceCollector->addReference(MediaInterface::RESOURCE_KEY, (string) $cover->getId(), 'cover');
        }

        foreach ($news->getExcerpt()['images']['ids'] ?? [] as $id) {
            $referenceCollector->addReference(MediaInterface::RESOURCE_KEY, (string) $id, 'excerpt.images');
        }

        foreach ($this->extractMediaFromBlocks($news->getContent() ?? []) as [$fieldKey, $mediaId]) {
            $referenceCollector->addReference(MediaInterface::RESOURCE_KEY, $mediaId, 'content.' . $fieldKey);
        }

        $referenceCollector->persistReferences();
        $this->referenceRepository->flush();
    }

    /**
     * Extracts media IDs from content blocks.
     *
     * @param array<mixed> $blocks
     * @return array<array{string, string}> List of [fieldKey, mediaId] tuples
     */
    private function extractMediaFromBlocks(array $blocks): array
    {
        // block type => fields of type single_media_selection
        $singleMediaFields = [
            'image_block' => ['image'],
            'image_text' => ['image'],
            'image_before_after' => ['before', 'after'],
            'two_columns' => ['image_one', 'image_two'],
            'three_columns' => ['image_one', 'image_two', 'image_three'],
            'file_block' => ['file'],
            'video_text' => ['video'],
        ];

        // block type => fields of type media_selection (ids array)
        $multiMediaFields = [
            'images' => ['images'],
        ];

        $results = [];

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            if (!$type) {
                continue;
            }

            foreach ($singleMediaFields[$type] ?? [] as $field) {
                $id = $block[$field]['id'] ?? null;
                if ($id) {
                    $results[] = [$type . '.' . $field, (string) $id];
                }
            }

            foreach ($multiMediaFields[$type] ?? [] as $field) {
                foreach ($block[$field]['ids'] ?? [] as $id) {
                    $results[] = [$type . '.' . $field, (string) $id];
                }
            }
        }

        return $results;
    }
}
