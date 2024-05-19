<?php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(type: "string", length: 250, nullable: true)]
    private ?string $favoriteCity1 = null;

    #[ORM\Column(type: "string", length: 250, nullable: true)]
    private ?string $favoriteCity2 = null;

    #[ORM\Column(type: "string", length: 250, nullable: true)]
    private ?string $favoriteCity3 = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $columnPreferences = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $firstName = '';

    public function getFavoriteCity1(): ?string
    {
        return $this->favoriteCity1;
    }

    public function setFavoriteCity1(?string $favoriteCity1): static
    {
        $this->favoriteCity1 = $favoriteCity1;

        return $this;
    }

    public function getFavoriteCity2(): ?string
    {
        return $this->favoriteCity2;
    }

    public function setFavoriteCity2(?string $favoriteCity2): static
    {
        $this->favoriteCity2 = $favoriteCity2;

        return $this;
    }

    public function getFavoriteCity3(): ?string
    {
        return $this->favoriteCity3;
    }

    public function setFavoriteCity3(?string $favoriteCity3): static
    {
        $this->favoriteCity3 = $favoriteCity3;

        return $this;
    }

    public function getColumnPreferences(): ?array
    {
        return $this->columnPreferences;
    }

    public function setColumnPreferences(?array $columnPreferences): static
    {
        $this->columnPreferences = $columnPreferences;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }
}
