<?php

namespace App\AdminBundle\Controller;

use Faker;
use Faker\Factory;
use App\AdminBundle\Entity\Company;
use App\AdminBundle\Entity\Contacts;
use App\AdminBundle\Form\SearchType;
use App\AdminBundle\Form\CompanyType;
use App\AdminBundle\Entity\Salesperson;
use App\AdminBundle\EntitySearch\Search;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\AdminBundle\Repository\SettingsRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/company")
 */
class CompanyController extends AbstractController
{

    private $settingsApplication;

    public function __construct(SettingsRepository $settingsRepo)
    {
        $this->settingsApplication = $settingsRepo
        ->findOneBy(array("id" => "1"));
    }
    
    /**
     * @Route("/", name="company_index", methods={"GET"})
     */
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $search = new Search();
        if($search->getLimit() == null) {
            $search->setLimit(10);
        }

        $form = $this->createForm(SearchType::class, $search);
        $form->handleRequest($request);
        $queryCompanies = $this->getDoctrine()
                ->getRepository(Company::class)
                ->getCompanies($search);
        
        $pageCompanies = $paginator->paginate(
            $queryCompanies,
            $request->query->getInt('page', 1, $search->getLimit()),
            $search->getLimit()
        );

        $nbCompanies = $this->getDoctrine()
        ->getRepository(Company::class)
        ->getCountAllCompanies($search);

        return $this->render('company/index.html.twig', [
            'companies' => $pageCompanies,
            'nbCompanies' => $nbCompanies,
            'formsearch' => $form->createView(),
            "settingsApplication" => $this->settingsApplication
        ]);
    }

    /**
     * @Route("/new", name="company_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $repoCompany = $this->getDoctrine()->getRepository(Company::class);
        $this->faker = Faker\Factory::create('fr_FR');
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            do{
                $code = $this->faker->regexify("[A-Z]{10}");
                
            }while($repoCompany->findOneBy(array("code" => $code)) != null);
            $company->setCode($code);
            $company->setCreatedAt(new \DateTime());
            $company->setUpdatedAt(new \DateTime());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($company);
            $entityManager->flush();

            return $this->redirectToRoute('company_index');
        }
        return $this->render('company/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
            "settingsApplication" => $this->settingsApplication
        ]);
    }

    /**
     * @Route("/{code}/edit", name="company_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Company $company): Response
    {
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $company->setUser_last_update($this->getUser());
            $company->setUpdatedAt(new \DateTime());
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('company_index', [
                'code' => $company->getCode(),
            ]);
        }

        $contacts_entreprise = $this->getDoctrine()->getRepository(Contacts::class)->findBy(array("company" => $company->getCode()));

        return $this->render('company/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
            'contacts_entreprise' => $contacts_entreprise,
            "settingsApplication" => $this->settingsApplication
        ]);
    }

    /**
     * @Route("/{code}", name="company_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Company $company): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getCode(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($company);
            $entityManager->flush();
        }

        return $this->redirectToRoute('company_index');
    }

    /**
     * @Route("/delete/many", name="company_delete_many", methods={"DELETE"})
     */
    public function delete_many(Request $request): Response
    {
        $eachId = $request->request->get("eachId");
        $entityManager = $this->getDoctrine()->getManager();

        foreach ($eachId as $id)
        {
            $company = $this->getDoctrine()->getRepository(Company::class)->findOneBy(['code' => $id]);

            $entityManager->remove($company);
                
        }
        $data = [
            'ids' => $eachId,
            'result' => true
        ];

        $entityManager->flush();
        // return $this->redirectToRoute('contacts_index');
        return new JsonResponse($data);

    }

    /**
     * @Route("/change_decision/{code}", name="company_change_decision", methods={"POST"})
     */
    public function changeDecision(Request $request, Company $company)
    {
        $decision = (int)$request->request->get("decision_level");
        $company->setDecisionLevel($decision);
        $company->setUser_last_update($this->getUser());
        $this->getDoctrine()->getManager()->flush();
        $data = array(
            "retour" => true
        );

        // $response = new Response(json_encode($data, 200));
        // $response->headers->set('Content-Type', 'application/json');
        return new JsonResponse($data);
    }

    /**
     * @Route("/change_statut/{code}", name="company_change_statut", methods={"GET","POST"})
     */
    public function changeStatutCompany(Request $request, Company $company)
    {
        $statut = (bool)$request->request->get("statut");
        $company->setActif($statut);
        $company->setUser_last_update($this->getUser());
        $this->getDoctrine()->getManager()->flush();
        $data = array(
            "retour" => true
        );

        // $response = new Response(json_encode($data, 200));
        // $response->headers->set('Content-Type', 'application/json');
        return new JsonResponse($data);
    }
}
