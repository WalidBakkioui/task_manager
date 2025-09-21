<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use App\Entity\Group;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'date', nullable: false)]
    #[Assert\NotBlank(message: "La date est obligatoire.")]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date limite ne peut pas être dans le passé."
    )]
    private ?\DateTimeInterface $dueDate = null;


    #[ORM\Column(length: 50)]
    private ?string $priority = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $completed = false;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: "group_id", referencedColumnName: "id", onDelete: "SET NULL", nullable: true)]
    private ?Group $group = null;

    #[ORM\Column(length: 20, options: ['default' => 'non_commence'])]
    private ?string $status = 'non_commence';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->status === 'terminee';
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;
        $this->status = $completed ? 'terminee' : 'en_cours';
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'non_commence';
        $this->completed = false;
    }

    public function getGroup(): ?Group {
        return $this->group;
    }
    public function setGroup(?Group $group): self {
        $this->group = $group; return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $allowed = ['non_commence', 'en_cours', 'terminee'];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Statut invalide');
        }

        $this->status = $status;
        $this->completed = ($status === 'terminee');
        return $this;
    }
}
