<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Controller;

use PSBits\EpisodeGuide\Domain\Model\Series;
use PSBits\EpisodeGuide\Domain\Repository\SeriesRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

/**
 * Class SeriesController
 *
 * @package PSBits\EpisodeGuide\Controller
 */
class SeriesController extends ActionController
{
    public function __construct(
        protected readonly SeriesRepository $seriesRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function createAction(Series $series): ResponseInterface
    {
        $this->seriesRepository->add($series);
        $this->addFlashMessage('Series created successfully.');

        return $this->redirect('list');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteAction(Series $series): ResponseInterface
    {
        $this->seriesRepository->remove($series);
        $this->addFlashMessage('Series deleted successfully.');

        return $this->redirect('list');
    }

    public function editAction(Series $series): ResponseInterface
    {
        $this->view->assign('series', $series);

        return $this->htmlResponse();
    }

    public function listAction(): ResponseInterface
    {
        $this->view->assign('series', $this->seriesRepository->findAll());

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['value' => 'newSeries'])]
    public function newAction(?Series $newSeries = null): ResponseInterface
    {
        $this->view->assign('series', $newSeries);

        return $this->htmlResponse();
    }

    public function showAction(Series $series): ResponseInterface
    {
        $this->view->assign('series', $series);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function updateAction(Series $series): ResponseInterface
    {
        $this->seriesRepository->update($series);
        $this->addFlashMessage('Series updated successfully.');

        return $this->redirect('list');
    }
}
