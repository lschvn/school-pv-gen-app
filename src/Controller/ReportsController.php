<?php

namespace App\Controller;

use App\Entity\Reports;
use App\Entity\ReportVersion;
use App\Form\ReportsType;
use App\Repository\ReportsRepository;
use App\Repository\TestRepository;
use App\Entity\Test;
use App\Form\TestType;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/reports')]
final class ReportsController extends AbstractController
{
    #[Route(name: 'app_reports_index', methods: ['GET'])]
    public function index(ReportsRepository $reportsRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to view reports.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('reports/index.html.twig', [
            'reports' => $reportsRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/new', name: 'app_reports_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $report = new Reports();
        $report->setUser($this->getUser());
        
        $form = $this->createForm(ReportsType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('firm_logo')->getData();
            
            if ($logoFile) {
                // Utilisation de uniqid() pour générer un identifiant unique
                $uniqueId = uniqid();
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $logoFile->getClientOriginalExtension();
                $newFilename = $uniqueId . '.' . $extension;
                
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/assets/logo';
                    
                    // Création du répertoire s'il n'existe pas
                    if (!file_exists($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }
                    
                    $logoFile->move(
                        $uploadsDir,
                        $newFilename
                    );
                    
                    // Définition de l'URL du logo dans l'entité
                    $report->setFirmLogoUrl('/assets/logo/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du logo: ' . $e->getMessage());
                }
            }

            $entityManager->persist($report);
            $entityManager->flush();

            return $this->redirectToRoute('app_reports_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reports/new.html.twig', [
            'report' => $report,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reports_show', methods: ['GET'])]
    public function show(Reports $report): Response
    {
        if ($this->getUser() !== $report->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to view this report.');
        }

        return $this->render('reports/show.html.twig', [
            'report' => $report,
            'reportVersions' => $report->getReportVersions(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reports_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reports $report, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if ($this->getUser() !== $report->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to edit this report.');
        }

        $form = $this->createForm(ReportsType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('firm_logo')->getData();
            
            if ($logoFile) {
                // Génération d'un identifiant unique pour le nouveau fichier
                $uniqueId = uniqid();
                $extension = $logoFile->getClientOriginalExtension();
                $newFilename = $uniqueId . '.' . $extension;
                
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/assets/logo';
                    
                    // Création du répertoire s'il n'existe pas
                    if (!file_exists($uploadsDir)) {
                        mkdir($uploadsDir, 0777, true);
                    }
                    
                    $logoFile->move(
                        $uploadsDir,
                        $newFilename
                    );
                    
                    // Suppression de l'ancien logo s'il existe
                    $oldLogoPath = $report->getFirmLogoUrl();
                    if ($oldLogoPath) {
                        $oldLogoFullPath = $this->getParameter('kernel.project_dir') . '/public' . $oldLogoPath;
                        if (file_exists($oldLogoFullPath)) {
                            unlink($oldLogoFullPath);
                        }
                    }
                    
                    // Mise à jour de l'URL du logo dans l'entité
                    $report->setFirmLogoUrl('/assets/logo/' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred while uploading the logo:' . $e->getMessage());
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_reports_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reports/edit.html.twig', [
            'report' => $report,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reports_delete', methods: ['POST'])]
    public function delete(Request $request, Reports $report, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $report->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to delete this report.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$report->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($report);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reports_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/tests', name: 'app_reports_tests', methods: ['GET'])]
    public function showTests(Reports $report, TestRepository $testRepository): Response
    {
        if ($this->getUser() !== $report->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de voir ces tests.');
        }

        $tests = $testRepository->findBy(['report' => $report]);

        return $this->render('reports/tests.html.twig', [
            'report' => $report,
            'tests' => $tests,
        ]);
    }

    #[Route('/{id}/tests/{test_id}/edit', name: 'app_reports_test_edit', methods: ['GET', 'POST'])]
    public function editTest(Request $request, Reports $report, int $test_id, EntityManagerInterface $entityManager, TestRepository $testRepository): Response
    {
        if ($this->getUser() !== $report->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de modifier ce test.');
        }

        $test = $testRepository->find($test_id);
        
        if (!$test || $test->getReport() !== $report) {
            throw $this->createNotFoundException('Test non trouvé ou n\'appartient pas à ce rapport.');
        }

        // Pas besoin d'option supplémentaire
        $form = $this->createForm(TestType::class, $test);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le test a été mis à jour avec succès.');
            return $this->redirectToRoute('app_reports_tests', ['id' => $report->getId()]);
        }

        return $this->render('reports/edit_test.html.twig', [
            'report' => $report,
            'test' => $test,
            'form' => $form,
        ]);
    }

        #[Route('/{id}/pdf', name: 'app_reports_pdf', methods: ['GET'])]
        public function generatePdf(Request $request, Reports $report, EntityManagerInterface $entityManager, \Symfony\Component\Mailer\MailerInterface $mailer): Response
        {
            // check perms
            if ($this->getUser() !== $report->getUser()) {
                throw $this->createAccessDeniedException('You do not have permission to generate a PDF for this report.');
            }

            // get the data from the report (tests etc)
            $tests = $report->getTests();

            // generate pdf from template with data
            $html = $this->render('reports/pdf.html.twig', [
                'report' => $report,
                'tests' => $tests,
                'user' => $this->getUser(),
            ])->getContent();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4','landscape');
            $dompdf->render();
            $pdf = $dompdf->output();

            // Création du répertoire pour les PDFs s'il n'existe pas
            $pdfDir = $this->getParameter('kernel.project_dir') . '/public/assets/pdf';
            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }

            // save the pdf to the filesystem
            $filename = uniqid() . '.pdf';
            $pdfPath = $pdfDir . '/' . $filename;
            file_put_contents($pdfPath, $pdf);

            // URL du fichier pour accès web
            $pdfUrl = '/assets/pdf/' . $filename;

            // check if report version exists for this report 
            $reportVersion = new ReportVersion();
            $reportVersion->setReport($report);
            $reportVersion->setPath($pdfUrl); // Stocker l'URL relative plutôt que le chemin complet
            $reportVersion->setVersion(count($report->getReportVersions()) + 1);
            
            $entityManager->persist($reportVersion);
            $entityManager->flush();

            $this->addFlash('success', 'The PDF has been generated successfully.');

            // send email
            $email = (new \Symfony\Component\Mime\Email())
                ->from('sendy@lschvn.dev')
                ->to($this->getUser()->getUserIdentifier())
                ->subject('Your report has been generated')
                ->text('Your report has been generated. You can view it here: ' . $this->generateUrl('app_reports_show', ['id' => $report->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL))
                ->attachFromPath($pdfPath, name: 'report.pdf');

            $mailer->send($email);

            // return the pdf to the user
            return $this->redirectToRoute('app_reports_show', ['id'=> $report->getId()]);
        }
}
