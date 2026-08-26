<?php

declare(strict_types=1);

namespace App\Identity\Controller;

use App\Identity\Entity\User;
use App\Identity\Tour\ProductTourPage;
use App\Identity\Tour\ProductTourStepsBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardHomeController extends AbstractController
{
    public function __construct(
        private readonly ProductTourStepsBuilder $tourStepsBuilder,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard_home', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('dashboard/home.html.twig', [
            ...$this->tourStepsBuilder->twigVars(ProductTourPage::Dashboard, $user, $request),
        ]);
    }
}
