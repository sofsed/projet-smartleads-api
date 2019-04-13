<?php

namespace App\AdminBundle\Repository;

use App\AdminBundle\EntitySearch\Search;
use App\AdminBundle\Entity\Contacts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Proxies\__CG__\App\AdminBundle\Entity\Salesperson;

/**
 * @method Contacts[]    getAllContacts(ContactsSearch $search)
 * @method Contacts[]    getContactsCommercial(ContactsSearch $search)
 */
class ContactsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Contacts::class);
    }

    /**
     * @return Contacts[] Returns an array of Contacts objects
     * @param Search $search Un objet de recherche
     */
    public function getContactsCommercial($search, $id_user)
    {
        $query = $this->createQueryBuilder('contacts')
            ->where("contacts.salesperson = :salesperson")
            ->orderBy('contacts.lastName', 'ASC')
            ->setParameter(":salesperson", $id_user);

        if ($search->getSearch()) {
            $query->andWhere('contacts.lastName LIKE :search');
            $query->orWhere('contacts.firstName LIKE :search');
            $query->setParameter(":search", "%" . $search->getSearch() . "%");
        }

        return $query->getQuery();

    }

    /**
     * @return Contacts[] Returns an array of Contacts objects
     */
    public function getCountContactsCommercial($id_user)
    {
        $query = $this->createQueryBuilder('contacts')
            ->select('count(contacts.code)')
            ->where("contacts.salesperson = :salesperson")
            ->orderBy('contacts.lastName', 'ASC')
            ->setParameter(":salesperson", $id_user)
            ->getQuery();
            
        return $query->getSingleScalarResult();
    }

    /**
     * @return Contacts[] Returns an array of Contacts objects
     * @param Search $search Un objet de recherche
     */
    public function getAllContacts($search)
    {
        $query = $this->createQueryBuilder('contacts')
            ->orderBy('contacts.lastName', 'ASC');

        if ($search->getSearch()) {
            $query->andWhere('contacts.lastName LIKE :search');
            $query->orWhere('contacts.firstName LIKE :search');
            $query->setParameter(":search", "%" . $search->getSearch() . "%");
        }
        return $query->getQuery();

    }

    /**
     * @return Contacts[] Returns an array of Contacts objects
     */
    public function getCountAllContacts()
    {
        $query = $this->createQueryBuilder('contacts')
            ->select('count(contacts.code)')
            ->getQuery();
            
        return $query->getSingleScalarResult();
    }

    public function getContactsInArray($array){
        $query = $this->createQueryBuilder('contacts')
            ->orderBy('contacts.lastName', 'ASC')
            ->where("contacts.code in (:array)")
            ->setParameter(":array", $array)
            ->getQuery();
        return $query->getResult();
    }

    public function getContactsOperationNotSend($array){
        $query = $this->createQueryBuilder('contacts')
            ->orderBy('contacts.lastName', 'ASC')
            ->where("contacts.code not in (:array)")
            ->setParameter(":array", $array)
            ->getQuery();
        return $query->getResult();
    }

    public function lastWeekNewContactsPerDay(){
        date_default_timezone_set('Europe/Paris');
        $dateNow = date("Y-m-d H:i");
        $dateBefore = date("Y-m-d 00:00", strtotime('-8 days'));
        $query = $this->createQueryBuilder("contacts")
            ->select("COUNT(contacts.createdAt) as nb, DATE(contacts.createdAt) as createdAt")
            ->where("DATE(contacts.createdAt) BETWEEN :date_debut AND :date_fin")
            ->groupby("createdAt")
            ->setParameter('date_debut', $dateBefore)
            ->setParameter('date_fin', $dateNow)
            ->getQuery();
        return $query;
    }

    /**
     * Récupère le nombre de nouveaux contacts depuis la variable envoyée
     * @param $since Permet de savoir depuis quand on cherche les nouveaux contacts
     */
    public function lastContactsSince($since){
        date_default_timezone_set('Europe/Paris');
        $dateNow = date("Y-m-d H:i");
        $dateBefore = date("Y-m-d 00:00", strtotime($since));
        $query = $this->createQueryBuilder("contacts")
            ->select("COUNT(contacts.createdAt) as nb")
            ->where("DATE(contacts.createdAt) BETWEEN :date_debut AND :date_fin")
            ->setParameter('date_debut', $dateBefore)
            ->setParameter('date_fin', $dateNow)
            ->getQuery();
        return $query;
    }
}
