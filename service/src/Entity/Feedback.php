<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feedback')]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $image = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the image as a resource or null
     * @return resource|null
     */
    public function getImage()
    {
        if (is_resource($this->image)) {
            if (get_resource_type($this->image) === 'stream') {
                rewind($this->image);
            }
            return $this->image;
        }
        
        return null;
    }

    /**
     * Set the image content
     * @param resource|string|null $image
     * @return $this
     */
    public function setImage($image): self
    {
        if (is_string($image) && !is_resource($image)) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $image);
            rewind($stream);
            $this->image = $stream;
        } else {
            $this->image = $image;
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
} 