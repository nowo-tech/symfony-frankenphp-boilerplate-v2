<?php

declare(strict_types=1);

namespace App\Identity\Tour;

use App\Identity\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds driver.js step arrays for the platform tour pages (Dashboard, Admin).
 *
 * Product apps may extend this class and override {@see steps()} to add or
 * replace steps. Keep platform scope only: Dashboard home + Admin hub.
 *
 * CSRF: mark-seen uses X-CSRF-TOKEN (AJAX exception; see docs/CONTRIBUTING.md).
 */
class ProductTourStepsBuilder
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Return Twig vars to pass to the template alongside {@code _product_tour.html.twig}.
     *
     * @return array{
     *   productTourSteps: list<array<string, mixed>>,
     *   productTourPage: string,
     *   productTourLabels: array<string, string>,
     *   autoStartProductTour: bool,
     *   forceProductTour: bool,
     * }
     */
    public function twigVars(ProductTourPage $page, User $user, Request $request): array
    {
        $seen = $user->hasSeenTourPage($page->value);
        $force = '1' === $request->query->get('tour');

        return [
            'productTourSteps' => $this->steps($page),
            'productTourPage' => $page->value,
            'productTourLabels' => $this->labels(),
            'autoStartProductTour' => !$seen,
            'forceProductTour' => $force,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function steps(ProductTourPage $page): array
    {
        return match ($page) {
            ProductTourPage::Dashboard => $this->dashboardSteps(),
            ProductTourPage::Admin => $this->adminSteps(),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function dashboardSteps(): array
    {
        return [
            [
                'popover' => [
                    'title' => $this->t('tour.dashboard.welcome.title'),
                    'description' => $this->t('tour.dashboard.welcome.body'),
                    'side' => 'bottom',
                    'align' => 'start',
                ],
            ],
            [
                'element' => '[data-tour="user-menu"]',
                'popover' => [
                    'title' => $this->t('tour.dashboard.user_menu.title'),
                    'description' => $this->t('tour.dashboard.user_menu.body'),
                    'side' => 'bottom',
                    'align' => 'end',
                ],
            ],
            [
                'element' => '[data-tour="admin-link"]',
                'popover' => [
                    'title' => $this->t('tour.dashboard.admin_link.title'),
                    'description' => $this->t('tour.dashboard.admin_link.body'),
                    'side' => 'bottom',
                    'align' => 'start',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function adminSteps(): array
    {
        return [
            [
                'popover' => [
                    'title' => $this->t('tour.admin.welcome.title'),
                    'description' => $this->t('tour.admin.welcome.body'),
                    'side' => 'bottom',
                    'align' => 'start',
                ],
            ],
            [
                'element' => '[data-testid="admin-users"]',
                'popover' => [
                    'title' => $this->t('tour.admin.users.title'),
                    'description' => $this->t('tour.admin.users.body'),
                    'side' => 'bottom',
                    'align' => 'start',
                ],
            ],
            [
                'element' => '[data-testid="admin-appearance"]',
                'popover' => [
                    'title' => $this->t('tour.admin.appearance.title'),
                    'description' => $this->t('tour.admin.appearance.body'),
                    'side' => 'bottom',
                    'align' => 'start',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function labels(): array
    {
        return [
            'next' => $this->t('tour.labels.next'),
            'previous' => $this->t('tour.labels.previous'),
            'done' => $this->t('tour.labels.done'),
            'close' => $this->t('tour.labels.close'),
            'progress' => $this->t('tour.labels.progress'),
        ];
    }

    private function t(string $key): string
    {
        return $this->translator->trans($key, [], 'messages');
    }
}
