<?php

namespace App\Entity;

use App\Repository\BlogPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
class BlogPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, minMessage: 'Modele entrer trop petite')]
    private ?string $title = null;

    #[ORM\Column]
    private ?int $maxPeople = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $consumption = null;

    #[ORM\Column(length: 20)]
    private ?string $box = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    #[Assert\NotNull()]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 4, max: 4, minMessage:'Valeur entrer trop petite', maxMessage: 'valeur entrer trop grande')]
    private ?int $years = null;

    #[ORM\OneToOne(targetEntity: Photo::class, cascade: ["persist"], orphanRemoval: true)]
    private ?Photo $photo = null;

    #[ORM\OneToMany(mappedBy: 'blogPost', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;



    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->photo = new Photo(); // Initialisez la propriété $photo avec une nouvelle instance
        $this->users = new ArrayCollection();
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMaxPeople(): ?int
    {
        return $this->maxPeople;
    }

    public function setMaxPeople(int $maxPeople): static
    {
        $this->maxPeople = $maxPeople;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getConsumption(): ?string
    {
        return $this->consumption;
    }

    public function setConsumption(string $consumption): static
    {
        $this->consumption = $consumption;

        return $this;
    }

    public function getBox(): ?string
    {
        return $this->box;
    }

    public function setBox(string $box): static
    {
        $this->box = $box;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getYears(): ?int
    {
        return $this->years;
    }

    public function setYears(int $years): static
    {
        $this->years = $years;

        return $this;
    }

    public function getPhoto(): Photo
    {
        return $this->photo;
    }

    public function setPhoto(Photo $photo): void
    {
        $this->photo = $photo;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setBlogPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getBlogPost() === $this) {
                $comment->setBlogPost(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }


}
