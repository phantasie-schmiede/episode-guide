<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Controller;

use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Repository\EpisodeRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

/**
 * Class EpisodeController
 *
 * @package PSBits\EpisodeGuide\Controller
 */
class EpisodeController extends ActionController
{
    public function __construct(
        protected readonly EpisodeRepository $episodeRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function createAction(Episode $episode): ResponseInterface
    {
        $this->episodeRepository->add($episode);
        $this->addFlashMessage('Episode created successfully.');

        return $this->redirect('list');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteAction(Episode $episode): ResponseInterface
    {
        $this->episodeRepository->remove($episode);
        $this->addFlashMessage('Episode deleted successfully.');

        return $this->redirect('list');
    }

    public function editAction(Episode $episode): ResponseInterface
    {
        $this->view->assign('episode', $episode);

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['value' => 'newEpisode'])]
    public function newAction(?Episode $newEpisode = null): ResponseInterface
    {
        $this->view->assign('episode', $newEpisode);

        return $this->htmlResponse();
    }

    public function showAction(Episode $episode): ResponseInterface
    {
        $this->view->assign('episode', $episode);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function updateAction(Episode $episode): ResponseInterface
    {
        $this->episodeRepository->update($episode);
        $this->addFlashMessage('Episode updated successfully.');

        return $this->redirect('list');
    }
}
