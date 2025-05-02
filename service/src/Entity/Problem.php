<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'problems')]
class Problem
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

    #[ORM\Column(type: 'boolean')]
    private bool $isPublished = false;

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

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }
} 