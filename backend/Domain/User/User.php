<?php

declare(strict_types=1);

namespace PlayOrPay\Domain\User;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Knojector\SteamAuthenticationBundle\User\SteamUserInterface;
use PlayOrPay\Domain\Contracts\Entity\AggregateInterface;
use PlayOrPay\Domain\Contracts\Entity\AggregateTrait;
use PlayOrPay\Domain\Contracts\Entity\OnUpdateEventListenerInterface;
use PlayOrPay\Domain\Role\Role;
use PlayOrPay\Domain\Role\RoleName;
use PlayOrPay\Domain\Steam\Group;
use PlayOrPay\Domain\Steam\SteamId;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, SteamUserInterface, OnUpdateEventListenerInterface, AggregateInterface
{
    use AggregateTrait;

    /** @var SteamId */
    private $steamId;

    /** @var int|null */
    private $communityVisibilityState;

    /** @var int|null */
    private $profileState;

    /** @var string */
    private $profileName;

    /** @var DateTime|null */
    private $lastLogOff;

    /** @var int|null */
    private $commentPermission;

    /** @var string */
    private $profileUrl;

    /** @var string */
    private $avatar;

    /** @var int|null */
    private $personaState;

    /** @var int|null */
    private $primaryClanId;

    /** @var DateTime|null */
    private $joinDate;

    /** @var string|null */
    private $countryCode;

    /** @var Role[] */
    private $roles;

    /** @var bool */
    private $active = true;

    /** @var string|null */
    private $blaeoName;

    /** @var string */
    private $extraRules;

    /** @var DateTime */
    private $createdAt;

    /** @var DateTime */
    private $updatedAt;

    /** @var Group[] */
    private $groups;

    public function __construct()
    {
        $this->updatedAt = $this->createdAt = new DateTime();
        $this->roles = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    public function addGroup(Group $group)
    {
        if ($this->groups->contains($group)) {
            return $this;
        }

        $this->groups->add($group);
        $group->addMember($this);

        return $this;
    }

    public function getSteamId(): int
    {
        return $this->steamId->__default;
    }

    public function getId(): SteamId
    {
        return $this->steamId;
    }

    public function setSteamId(int $id): self
    {
        $this->steamId = new SteamId($id);

        return $this;
    }

    public function getCommunityVisibilityState(): int
    {
        return (int) $this->communityVisibilityState;
    }

    public function setCommunityVisibilityState(?int $state)
    {
        $this->communityVisibilityState = $state;

        return $this;
    }

    public function getProfileState(): int
    {
        return (int) $this->profileState;
    }

    public function setProfileState(?int $state): self
    {
        $this->profileState = $state;

        return $this;
    }

    public function getProfileName(): string
    {
        return $this->profileName;
    }

    public function setProfileName(string $name): self
    {
        $this->profileName = $name;

        return $this;
    }

    /**
     * @throws Exception
     *
     * @return DateTime
     */
    public function getLastLogOff(): DateTime
    {
        static $emptyDateTime;
        if (!$emptyDateTime) {
            $emptyDateTime = DateTime::createFromFormat('U', '0');
        }

        return $this->lastLogOff ?: $emptyDateTime;
    }

    /**
     * @param int $lastLogOff
     *
     * @throws Exception
     */
    public function setLastLogOff(?int $lastLogOff)
    {
        $this->lastLogOff = $lastLogOff
            ? DateTime::createFromFormat('U', (string) $lastLogOff)
            : null;
    }

    public function getCommentPermission(): int
    {
        return (int) $this->commentPermission;
    }

    public function setCommentPermission(?int $permission): self
    {
        $this->commentPermission = $permission;

        return $this;
    }

    public function getProfileUrl(): string
    {
        return $this->profileUrl;
    }

    public function setProfileUrl(string $url): self
    {
        $this->profileUrl = $url;

        return $this;
    }

    public function getAvatar(): string
    {
        return (string) $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getPersonaState(): int
    {
        return (int) $this->personaState;
    }

    public function setPersonaState(?int $state): self
    {
        $this->personaState = $state;

        return $this;
    }

    public function getPrimaryClanId(): ?int
    {
        return $this->primaryClanId;
    }

    public function setPrimaryClanId(?int $clanId): self
    {
        $this->primaryClanId = $clanId;

        return $this;
    }

    public function getJoinDate(): ?DateTime
    {
        return $this->joinDate;
    }

    /**
     * @throws Exception
     */
    public function setJoinDate(?int $joinDate): self
    {
        $this->joinDate = DateTime::createFromFormat('U', (string) $joinDate);

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function hasRole(RoleName $roleName): bool
    {
        $roleNameId = (string) $roleName;

        return $this->roles->exists(
            function (/* @noinspection PhpUnusedParameterInspection */ int $pos, Role $userRole) use ($roleNameId) {
                return $roleNameId === (string) $userRole->getName();
            }
        );
    }

    public function addRole(Role $role): self
    {
        if ($this->hasRole($role->getName())) {
            return $this;
        }

        $this->roles->add($role);

        return $this;
    }

    public function addRoles(Role ...$roles): self
    {
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function removeRole(RoleName $roleName): self
    {
        $this->roles->filter(function (Role $role, int $idx) use ($roleName) {
            if ((string) $role->getName() === (string) $roleName) {
                $this->roles->remove($idx);
            }
        });

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles->toArray();
    }

    /**
     * @return string[]
     */
    public function getRoleNames(): array
    {
        return array_map(function (Role $role) {
            return (string) $role->getName();
        }, $this->getRoles());
    }

    public function isAdmin()
    {
        return $this->hasRole(new RoleName(RoleName::ADMIN));
    }

    /**
     * @param array<string, string|int|null> $userData
     *
     * @throws Exception
     */
    public function update(array $userData)
    {
        $this->setCommunityVisibilityState($userData['communityvisibilitystate']);
        $this->setProfileState($userData['profilestate']);
        $this->setProfileName($userData['personaname']);
        $this->setLastLogOff($userData['lastlogoff'] ?? null);
        $this->setCommentPermission(
            isset($userData['commentpermission']) ? $userData['commentpermission'] : 0
        );
        $this->setProfileUrl($userData['profileurl']);
        $this->setAvatar($userData['avatarfull']);
        $this->setPersonaState($userData['personastate']);
        $this->setPrimaryClanId(
            isset($userData['primaryclanid']) ? (int) $userData['primaryclanid'] : null
        );
        $this->setCountryCode(
            isset($userData['loccountrycode']) ? $userData['loccountrycode'] : null
        );
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername(): string
    {
        return (string) $this->steamId->__default;
    }

    public function getBlaeoName(): ?string
    {
        return $this->blaeoName;
    }

    public function setBlaeoName(string $name): self
    {
        $this->blaeoName = $name;

        return $this;
    }

    public function activate(): self
    {
        $this->active = true;

        return $this;
    }

    public function deactivate(): self
    {
        $this->active = false;

        return $this;
    }

    public function getExtraRules(): ?string
    {
        return $this->extraRules;
    }

    public function setExtraRules(string $extraRules): self
    {
        $this->extraRules = $extraRules;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups->toArray();
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function onUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function eraseCredentials()
    {
        // impossible
    }
}
