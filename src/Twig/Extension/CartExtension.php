<?php

namespace App\Twig\Extension;

use App\Service\CartMerger;
use App\Twig\Runtime\CartExtensionRuntime;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly CartMerger $cartMerger,
    ) {
    }

    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('filter_name', [CartExtensionRuntime::class, 'doSomething']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('panier_count', [$this, 'getPanierCount']),
        ];
    }

    public function getPanierCount(): int
    {
        $user = $this->security->getUser();

        return $this->cartMerger->getPanierItemsCount($user);
    }
}
