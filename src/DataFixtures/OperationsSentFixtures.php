<?php

namespace App\DataFixtures;

use App\AdminBundle\Entity\OperationSent;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class OperationsSentFixtures extends BaseFixture implements DependentFixtureInterface
{
    public function loadData(ObjectManager $manager)
    {
        $this->createMany(0, "OperationSent", function($count){
            $operationSent = new OperationSent();
            $operationSent->setContacts($this->getRandomReference("Contacts"));
            $operationSent->setOperation($this->getRandomReference("Operation"));
            $operationSent->setSalesperson($this->getRandomReference("Salesperson"));
            $operationSent->setSentAt(new \DateTime());
            $operationSent->setUniqIdContact(\uniqid());
            return $operationSent;
        });

        $manager->flush();
    }

    public function getDependencies()
    {
        return array(
            ContactsFixtures::class,
            SalespersonFixtures::class,
            OperationsFixtures::class
        );
    }
}
