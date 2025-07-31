<?php

namespace Carbon\Fontawesome\DataSource;

use Neos\Flow\Annotations as Flow;
use Carbon\Fontawesome\Service\IconService;
use Neos\Neos\Service\DataSource\AbstractDataSource;

class FontAwesomeDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    protected static $identifier = 'carbon-fontawesome';

    #[Flow\Inject]
    protected IconService $iconService;

    public function getData(mixed $node = null, array $arguments = [])
    {
        if (!empty($arguments['label'])) {
            $name = explode(':', $arguments['label'])[1] ?? '';
            return $this->iconService->getLabel($name);
        }

        if (!empty($arguments['total'])) {
            return $this->iconService->getTotalNumberOfIcons();
        }

        return $this->iconService->search(
            $arguments['search'] ?? null,
            $arguments['packs'] ?? null,
            $arguments['styles'] ?? null,
            $arguments['categories'] ?? null,
            $arguments['fixedStyles'] ?? null,
        );
    }
}
