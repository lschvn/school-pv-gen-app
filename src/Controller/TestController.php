<?php

namespace App\Controller;

use App\Entity\Test;
use App\Entity\Reports;
use App\Form\TestType;
use App\Repository\TestRepository;
use App\Repository\ReportsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test')]
final class TestController extends AbstractController
{
    // #[Route(name: 'app_test_index', methods: ['GET'])]
    // public function index(TestRepository $testRepository): Response
    // {
    //     return $this->render('test/index.html.twig', [
    //         'tests' => $testRepository->findAll(),
    //     ]);
    // }

    // #[Route('/new', name: 'app_test_new', methods: ['GET', 'POST'])]
    // public function new(Request $request, EntityManagerInterface $entityManager): Response
    // {
    //     $test = new Test();
    //     $form = $this->createForm(TestType::class, $test);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $entityManager->persist($test);
    //         $entityManager->flush();

    //         return $this->redirectToRoute('app_test_index', [], Response::HTTP_SEE_OTHER);
    //     }

    //     return $this->render('test/new.html.twig', [
    //         'test' => $test,
    //         'form' => $form,
    //     ]);
    // }

    #[Route('/new/report/{report_id}', name: 'app_test_new_for_report', methods: ['GET', 'POST'])]
    public function newForReport(Request $request, EntityManagerInterface $entityManager, int $report_id, ReportsRepository $reportsRepository): Response
    {
        $report = $reportsRepository->find($report_id);
        
        if (!$report) {
            throw $this->createNotFoundException('Rapport non trouvé');
        }
        
        if ($this->getUser() !== $report->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission d\'ajouter un test à ce rapport.');
        }
        
        $test = new Test();
        $test->setReport($report);
        
        // Suppression de l'option 'report' qui cause l'erreur
        $form = $this->createForm(TestType::class, $test);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Initialisation de ResultTest si nécessaire
            if (method_exists($test, 'setResult') && property_exists('App\Enum\ResultTest', 'PENDING')) {
                $test->setResult(\App\Enum\ResultTest::PENDING);
            }
            
            $entityManager->persist($test);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le test a été ajouté avec succès au rapport.');
            return $this->redirectToRoute('app_reports_tests', ['id' => $report_id]);
        }

        return $this->render('test/new_for_report.html.twig', [
            'test' => $test,
            'report' => $report,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_test_show', methods: ['GET'])]
    public function show(Test $test): Response
    {
        return $this->render('test/show.html.twig', [
            'test' => $test,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_test_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Test $test, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TestType::class, $test);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_test_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('test/edit.html.twig', [
            'test' => $test,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_test_delete', methods: ['POST'])]
    public function delete(Request $request, Test $test, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$test->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($test);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_test_index', [], Response::HTTP_SEE_OTHER);
    }
}
