<?php

namespace PlayOrPay\Security\Event\EventParticipant;

use Doctrine\ORM\EntityNotFoundException;
use PlayOrPay\Application\Command\Event\EventParticipant\UpdateBlaeoGames\UpdateEventParticipantBlaeoGamesCommand;
use PlayOrPay\Infrastructure\Storage\Event\EventParticipantRepository;
use PlayOrPay\Infrastructure\Storage\User\ActorFinder;
use PlayOrPay\Security\CommonSecurityHandler;
use PlayOrPay\Security\SecuriryException;

class UpdateEventParticipantBlaeoGamesHandler extends CommonSecurityHandler
{
    /** @var EventParticipantRepository */
    private $participantRepo;

    public function __construct(ActorFinder $actorFinder, EventParticipantRepository $participantRepo)
    {
        parent::__construct($actorFinder);
        $this->participantRepo = $participantRepo;
    }

    /**
     * @param UpdateEventParticipantBlaeoGamesCommand $command
     *
     * @throws EntityNotFoundException
     * @throws SecuriryException
     */
    public function __invoke(UpdateEventParticipantBlaeoGamesCommand $command): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $participant = $this->participantRepo->get($command->getParticipantUuid());
        $this->assertActor($participant->getUser());
    }
}
