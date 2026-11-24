<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Controller;

use InvalidArgumentException;
use PSBits\EpisodeGuide\Domain\Model\Episode;
use PSBits\EpisodeGuide\Domain\Repository\EpisodeRepository;
use PSBits\Foundation\Attribute\PluginAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

class EpisodeController extends ActionController
{
    public function __construct(
        protected readonly EpisodeRepository $episodeRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Episode $episode): ResponseInterface
    {
        $this->episodeRepository->add($episode);
        $this->addFlashMessage('Episode created successfully.');

        return $this->redirectToSeason($episode);
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(Episode $episode): ResponseInterface
    {
        $this->episodeRepository->remove($episode);
        $this->addFlashMessage('Episode deleted successfully.');

        return $this->redirectToSeason($episode);
    }

    #[PluginAction(uncached: true)]
    public function editAction(Episode $episode): ResponseInterface
    {
        $this->view->assign('episode', $episode);

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['value' => 'newEpisode'])]
    #[PluginAction(uncached: true)]
    public function newAction(?Episode $newEpisode = null): ResponseInterface
    {
        $this->view->assign('episode', $newEpisode);

        return $this->htmlResponse();
    }

    #[PluginAction(default: true)]
    public function showAction(Episode $episode): ResponseInterface
    {
        $this->view->assign('episode', $episode);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Episode $episode): ResponseInterface
    {
        $this->episodeRepository->update($episode);
        $this->addFlashMessage('Episode updated successfully.');

        return $this->redirectToSeason($episode);
    }

    /**
     * There is no list view for episodes. Therefore, the user is sent back to the season the episode belongs to.
     */
    private function redirectToSeason(Episode $episode): ResponseInterface
    {
        $season = $episode->getSeason();

        if (null === $season) {
            return $this->redirect('list', 'Series');
        }

        return $this->redirect('show', 'Season', null, ['season' => $season]);
    }
}
