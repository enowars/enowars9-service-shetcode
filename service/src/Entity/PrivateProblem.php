<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'private_problems')]
class PrivateProblem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $difficulty = null;

    #[ORM\Column(type: 'json')]
    private array $testCases = [];

    #[ORM\Column(type: 'json')]
    private array $expectedOutputs = [];

    #[ORM\Column(type: 'float')]
    private float $maxRuntime = 1.0;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true)]
    private ?User $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getTestCases(): array
    {
        return $this->testCases;
    }

    public function setTestCases(array $testCases): self
    {
        $this->testCases = $testCases;

        return $this;
    }

    public function getExpectedOutputs(): array
    {
        return $this->expectedOutputs;
    }

    public function setExpectedOutputs(array $expectedOutputs): self
    {
        $this->expectedOutputs = $expectedOutputs;

        return $this;
    }

    public function getMaxRuntime(): float
    {
        return $this->maxRuntime;
    }

    public function setMaxRuntime(float $maxRuntime): self
    {
        $this->maxRuntime = min($maxRuntime, 1.0);
        
        return $this;
    }
    
    public function getAuthor(): ?User
    {
        return $this->author;
    }
    
    public function setAuthor(?User $author): self
    {
        $this->author = $author;
        
        return $this;
    }
} 