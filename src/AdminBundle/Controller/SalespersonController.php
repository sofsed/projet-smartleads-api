<?php

namespace App\AdminBundle\Controller;

use Faker;
use App\Service\FileUploader;
use Doctrine\ORM\EntityRepository;
use App\AdminBundle\Entity\Salesperson;
use App\AdminBundle\Form\SalespersonType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


/**
 * @Route("/salesperson")
 */
class SalespersonController extends AbstractController
{
    /**
     * @Route("/", name="salesperson_index", methods={"GET"})
     * @IsGranted("ROLE_DIRECTEUR", statusCode=403)
     */
    public function index(): Response
    {
        dump($this->getUser());


        $salespeople = $this->getDoctrine()
            ->getRepository(Salesperson::class)
            ->findAll();

        return $this->render('salesperson/index.html.twig', [
            'salespeople' => $salespeople,
        ]);
    }

    /**
     * @Route("/new", name="salesperson_new", methods={"GET","POST"})
     * @IsGranted("ROLE_DIRECTEUR", statusCode=403)
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder, FileUploader $fileUploader): Response
    {
        $repoSalesperson = $this->getDoctrine()->getRepository(Salesperson::class);
        $this->faker = Faker\Factory::create('fr_FR');
        $salesperson = new Salesperson();
        $form = $this->createForm(SalespersonType::class, $salesperson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $request->request->get("salesperson");
            do {
                $code = $this->faker->regexify("[A-Z]{10}");
            } while ($repoSalesperson->findOneBy(array("code" => $code)) != null);
            $roles = [];
            if ($data["profile"] == "Commercial") {
                $roles = ["ROLE_COMMERCIAL"];
            } else {
                $roles = ["ROLE_RESPONSABLE"];
            }

            $fileName = $fileUploader->upload($form['picture']->getData());
            $salesperson->setPicture($fileName);
            $salesperson->setRoles($roles);
            $salesperson->setCode($code);
            $salesperson->setPassword($passwordEncoder->encodePassword($salesperson, "azerty"));
            $salesperson->setCreatedAt(new \DateTime());
            $salesperson->setUpdatedAt(new \DateTime());
            $salesperson->setLeader($repoSalesperson->findOneBy(array("code" => $data["leader"])));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($salesperson);
            $entityManager->flush();

            return $this->redirectToRoute('salesperson_index');
        }

        return $this->render('salesperson/new.html.twig', [
            'salesperson' => $salesperson,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{code}", name="salesperson_show", methods={"GET"})
     * @IsGranted("ROLE_DIRECTEUR", statusCode=403)
     */
    public function show(Salesperson $salesperson): Response
    {
        return $this->render('salesperson/show.html.twig', [
            'salesperson' => $salesperson,
        ]);
    }

    /**
     * @Route("/{code}/edit", name="salesperson_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_DIRECTEUR", statusCode=403)
     */
    public function edit(Request $request, Salesperson $salesperson): Response
    {
        $form = $this->createForm(SalespersonType::class, $salesperson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('salesperson_index', [
                'code' => $salesperson->getCode(),
            ]);
        }

        return $this->render('salesperson/edit.html.twig', [
            'salesperson' => $salesperson,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{code}", name="salesperson_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Salesperson $salesperson): Response
    {
        if ($this->isCsrfTokenValid('delete' . $salesperson->getCode(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($salesperson);
            $entityManager->flush();
        }

        return $this->redirectToRoute('salesperson_index');
    }

    /**
     * @Route("/responsable/team", name="salesperson_team")
     * @IsGranted("ROLE_RESPONSABLE", statusCode=403)
     */
    public function team()
    {
        if($this->getUser()){
            $repoSalesperson = $this->getDoctrine()->getRepository(Salesperson::class);
            $salespersons = $repoSalesperson->findBy(["idLeader" => $this->getUser()->getCode()]);

        }

        return $this->render('salesperson/team.html.twig', [
            "salespersons" => $salespersons
        ]);
    }

    /**
     * @Route("/responsable/team/ajout", name="salesperson_team_ajout_membre", methods={"GET","POST"})
     * @IsGranted("ROLE_RESPONSABLE", statusCode=403)
     */
    public function ajoutMembreTeam(Request $request)
    {
        $salesperson = new Salesperson();
        $defaultData = ['message' => 'Type your message here'];
        $form = $this->createFormBuilder($defaultData)
                     ->add('name', EntityType::class, [
                        'class' => Salesperson::class,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('salesperson')
                                ->orderBy('salesperson.firstName', 'ASC');
                        },
                        'required' => false
                    ])
                     ->getForm();
        $form->handleRequest($request);
        // if($this->getUser()){
        //     $repoSalesperson = $this->getDoctrine()->getRepository(Salesperson::class);
        //     $salespersons = $repoSalesperson->findBy(["idLeader" => $this->getUser()->getCode()]);

        // }

        return $this->render('salesperson/ajout_membre.html.twig', [
            "formSalesperson" => $form->createView()
        ]);
    }
}
