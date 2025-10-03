<?php

namespace Pixel\NewsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

#[ORM\Entity(repositoryClass: "Pixel\NewsBundle\Repository\NewsRepository")]
#[ORM\Table(name: "news")]
#[Serializer\ExclusionPolicy("all")]
class News
{
    public const RESOURCE_KEY = "news";

    public const FORM_KEY = "news_details";

    public const LIST_KEY = "news";

    public const SECURITY_CONTEXT = "news.news";

    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Serializer\Expose()]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CategoryInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Serializer\Expose()]
    private CategoryInterface $category;

    #[ORM\ManyToOne(targetEntity: MediaInterface::class)]
    #[ORM\JoinColumn(onDelete: "SET NULL")]
    #[Serializer\Expose()]
    private ?MediaInterface $cover;

    /**
     * @var Collection<string, NewsTranslation>
     */
    #[ORM\OneToMany(targetEntity: "Pixel\NewsBundle\Entity\NewsTranslation", mappedBy: "news", cascade: ["ALL"], indexBy: "locale")]
    #[Serializer\Exclude]
    private $translations;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private string $defaultLocale;

    private string $locale = 'fr';

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    #[Serializer\VirtualProperty(name: "title")]
    public function getTitle(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->getTitle();
    }

    protected function getTranslation(string $locale = 'fr'): ?NewsTranslation
    {
        if (!$this->translations->containsKey($locale)) {
            return null;
        }
        return $this->translations->get($locale);
    }

    public function setTitle(string $title): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setTitle($title);
        return $this;
    }

    protected function createTranslation(string $locale): NewsTranslation
    {
        $translation = new NewsTranslation($this, $locale);
        $this->translations->set($locale, $translation);
        return $translation;
    }

    #[Serializer\VirtualProperty(name: "route")]
    public function getRoutePath(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->getRoutePath();
    }

    public function setRoutePath(string $routePath): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setRoutePath($routePath);
        return $this;
    }

    #[Serializer\VirtualProperty(name: "is_published")]
    public function isPublished(): ?bool
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->isPublished();
    }

    public function setIsPublished(?bool $isPublished): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setIsPublished($isPublished);
        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    #[Serializer\VirtualProperty(name: "seo")]
    public function getSeo(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->getSeo();
    }

    /**
     * @return array<mixed>|null
     */
    #[Serializer\VirtualProperty(name: "ext")]
    public function getExt(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return ($translation->getSeo()) ? [
            'seo' => $translation->getSeo(),
        ] : [
            'seo' => [
                "title" => "",
                "description" => "",
            ],
        ];
    }

    /**
     * @param array<mixed>|null $seo
     * @return $this
     */
    public function setSeo(?array $seo): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setSeo($seo);
        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    #[Serializer\VirtualProperty(name: "content")]
    public function getContent(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->getContent();
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function setCategory(CategoryInterface $category): void
    {
        $this->category = $category;
    }

    public function getCover(): ?MediaInterface
    {
        return $this->cover;
    }

    public function setCover(?MediaInterface $cover): void
    {
        $this->cover = $cover;
    }

    /**
     * @param array<mixed> $content
     * @return $this
     */
    public function setContent(array $content): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setContent($content);
        return $this;
    }

    #[Serializer\VirtualProperty(name: "published_at")]
    public function getPublishedAt(): ?\DateTimeImmutable
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->getPublishedAt();
    }

    public function setPublishedAt(?\DateTimeImmutable $date): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setPublishedAt($date);
        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    #[Serializer\VirtualProperty(name: "excerpt")]
    public function getExcerpt(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return $translation->getExcerpt();
    }

    /**
     * @param array<mixed>|null $excerpt
     * @return $this
     */
    public function setExcerpt(?array $excerpt): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }
        $translation->setExcerpt($excerpt);
        return $this;
    }

    /**
     * @return array<string, NewsTranslation>
     */
    public function getTranslations(): array
    {
        return $this->translations->toArray();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultLocale(): ?string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
    }
}
