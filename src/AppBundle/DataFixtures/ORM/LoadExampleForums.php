<?php

namespace Raddit\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Raddit\AppBundle\Entity\Forum;
use Raddit\AppBundle\Entity\Moderator;
use Raddit\AppBundle\Entity\User;

class LoadExampleForums extends AbstractFixture implements DependentFixtureInterface {
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager) {
        foreach ($this->provideForums() as $data) {
            $forum = new Forum();

            $forum->setName($data['name']);
            $forum->setTitle($data['title']);
            $forum->setDescription($data['description']);
            $forum->setCreated($data['created']);
            $forum->setFeatured($data['featured']);

            foreach ($data['moderators'] as $modData) {
                /** @var User $user */
                $user = $this->getReference('user-'.$modData['username']);

                $moderator = new Moderator();
                $moderator->setUser($user);
                $moderator->setForum($forum);
                $moderator->setTimestamp($modData['added']);

                $forum->addModerator($moderator);
            }

            $this->addReference('forum-'.$data['name'], $forum);

            $manager->persist($forum);
        }

        $manager->flush();
    }

    private function provideForums() {
        yield [
            'name' => 'cats',
            'title' => 'Cat Memes',
            'description' => 'le memes',
            'moderators' => [
                ['username' => 'emma', 'added' => new \DateTime('2017-04-20 19:17')],
                ['username' => 'zach', 'added' => new \DateTime('2017-05-05 05:05')],
            ],
            'created' => new \DateTime('2017-04-20 13:12'),
            'featured' => true,
        ];

        yield [
            'name' => 'news',
            'title' => 'News',
            'description' => "Discussion of current events\n\n### Rules\n\n* rulez go here",
            'moderators' => [
                ['username' => 'zach', 'added' => new \DateTime('2017-01-02 00:01')],
            ],
            'created' => new \DateTime('2017-01-01 00:00'),
            'featured' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [LoadExampleUsers::class];
    }
}
