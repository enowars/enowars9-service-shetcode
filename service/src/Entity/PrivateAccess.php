<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'private_access')]
class PrivateAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PrivateProblem::class)]
    #[ORM\JoinColumn(name: 'problem_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?PrivateProblem $problem = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProblem(): ?PrivateProblem
    {
        return $this->problem;
    }

    public function setProblem(?PrivateProblem $problem): self
    {
        $this->problem = $problem;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
} 