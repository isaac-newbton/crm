<?php

namespace App\Controller;

use App\Doctrine\UuidEncoder;
use App\Entity\Lead;
use App\Form\LeadType;
use App\Repository\LeadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/lead")
 */
class LeadController extends AbstractController
{

    /**
     * @Route("/admin/lead/{id}/edit", name="lead_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Lead $lead, UuidEncoder $encoder): Response
    {
        $form = $this->createForm(LeadType::class, $lead);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('organization_leads', [
                "encodedUuid" => $encoder->encode($lead->getOrganization()->getUuid())
            ]);
        }

        return $this->render('lead/edit.html.twig', [
            'lead' => $lead,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="lead_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Lead $lead): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lead->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($lead);
            $entityManager->flush();
        }

        return $this->redirectToRoute('lead_index');
    }
}
