<?php

namespace PlayOrPay\Tests\Functional\EventPickerComment;

use Doctrine\ORM\EntityNotFoundException;
use Exception;
use PlayOrPay\Domain\Event\Event;
use PlayOrPay\Domain\Event\EventPicker;
use PlayOrPay\Domain\Event\EventPickerComment;
use PlayOrPay\Domain\Event\EventPickPlayedStatus;
use PlayOrPay\Infrastructure\Storage\Event\EventPickerCommentRepository;
use PlayOrPay\Tests\Functional\FixtureCollection;
use PlayOrPay\Tests\Functional\FunctionalTest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class EventPickerCommentTest extends FunctionalTest
{
    /** @var FixtureCollection */
    private $fixtures;

    /** @var Event */
    private $event;

    /** @var EventPicker  */
    private $picker;


    /**
     * @test
     *
     * @throws EntityNotFoundException
     */
    public function comment_adding_should_work()
    {
        $commentText = 'hello world';
        $comment = $this->prepareComment($commentText);
        $this->assertSame($commentText, $comment->getText());
    }

    /**
     * @test
     * @throws EntityNotFoundException
     */
    public function comment_update_should_keep_history()
    {
        $comments = ['hello', 'world', 'again'];
        $comment = $this->prepareComment($comments[0]);

        $this->request('update_comment', [
            'commentUuid' => (string) $comment->getUuid(),
            'text' => $comments[1],
        ]);

        $comment = $this->getComment($comment->getUuid());
        $this->assertSame([$comments[0]], $comment->getHistory());
        $this->assertSame($comments[1], $comment->getText());

        $this->request('update_comment', [
            'commentUuid' => (string) $comment->getUuid(),
            'text' => $comments[2],
        ]);

        $comment = $this->getComment($comment->getUuid());
        $this->assertSame([$comments[0], $comments[1]], $comment->getHistory());
        $this->assertSame($comments[2], $comment->getText());
    }

    /**
     * @test
     *
     * @throws Exception
     */
    public function review_duplicates_should_not_be_allowed()
    {
        $this->prepare();
        $pick = $this->picker->getPicks()[0];

        $this->request('change_pick_status', [
            'pickUuid' => (string) $pick->getUuid(),
            'status' => EventPickPlayedStatus::BEATEN,
        ]);

        $this->prepareComment('first', $pick->getUuid());

        $this->expectException(EntityNotFoundException::class);

        $this->prepareComment('second', $pick->getUuid(), false);
    }

    /**
     * @param string $commentText
     * @param UuidInterface|null $reviewedPickUuid
     * @param bool $shouldBeSuccessfull
     *
     * @return EventPickerComment
     *
     * @throws EntityNotFoundException
     * @throws Exception
     */
    private function prepareComment(
        string $commentText,
        ?UuidInterface $reviewedPickUuid = null,
        bool $shouldBeSuccessfull = true
    ): EventPickerComment {
        $this->prepare();

        $commentUuid = Uuid::uuid4();

        $this->request('add_comment', [
            'commentUuid' => (string) $commentUuid,
            'pickerUuid' => (string) $this->picker->getUuid(),
            'text' => $commentText,
            'reviewedPickUuid' => $reviewedPickUuid ? (string) $reviewedPickUuid : null,
        ], $shouldBeSuccessfull);

        /** @var EventPickerCommentRepository $commentRepo */
        $commentRepo = self::$container->get(EventPickerCommentRepository::class);
        $comment = $commentRepo->get($commentUuid);

        return $comment;
    }

    /**
     * @throws Exception
     */
    private function prepare(): void
    {
        if (!$this->fixtures) {
            $this->fixtures = $this->applyFixtures(__DIR__.'/../../fixtures/filled_event.yaml');
            $this->event = $this->fixtures->get('filled_event');
            $this->picker = $this->event->getPickers()[0];

            $this->authorizeAsAdmin();
        }
    }

    /**
     * @param UuidInterface $commentUuid
     *
     * @return EventPickerComment
     *
     * @throws EntityNotFoundException
     */
    private function getComment(UuidInterface $commentUuid): EventPickerComment
    {
        /** @var EventPickerCommentRepository $commentRepo */
        $commentRepo = self::$container->get(EventPickerCommentRepository::class);
        return $commentRepo->get($commentUuid);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->fixtures = null;
        $this->event = null;
        $this->picker = null;
    }
}