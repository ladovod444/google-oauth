<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
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
    public const GITHUB_OAUTH = 'Github';
    public const GOOGLE_OAUTH = 'Google';

    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private $lastLogin;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $username = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $oauthType;

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

    /**
     * @param $clientId
     * @param string $email
     * @param string $username
     * @param string $oauthType
     * @param array $roles
     */
    public function __construct(
      $clientId,
      string $email,
      string $username,
      string $oauthType,
      array $roles,
      string $password,
    ) {
        $this->clientId = $clientId;
        $this->email = $email;
        $this->username = $username;
        $this->oauthType = $oauthType;
        $this->lastLogin = new DateTime('now');
        $this->roles = $roles;
        $this->password = $password;
    }

    /**
     * @param int $clientId
     * @param string $email
     * @param string $username
     *
     * @return User
     */
    public static function fromGithubRequest(
      int $clientId,
      string $email,
      string $username
    ): User
    {
        return new self(
          $clientId,
          $email,
          $username,
          self::GITHUB_OAUTH,
          [self::ROLE_USER]
        );
    }

    /**
     * @param string $clientId
     * @param string $email
     * @param string $username
     *
     * @return User
     */
    public static function fromGoogleRequest(
      string $clientId,
      string $email,
      string $username,
      string $password
    ): User
    {
        return new self(
          $clientId,
          $email,
          $username,
          self::GOOGLE_OAUTH,
          [self::ROLE_USER],
          $password
        );
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
        // guarantee every user at least has ROLE_USER
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
    public function getPassword(): ?string
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
        // If you store any temporary, sensitive data on the user, clear it here
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

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function getLastLogin(): DateTime
    {
        return $this->lastLogin;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getOauthType(): ?string
    {
        return $this->oauthType;
    }


}
