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
use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use PSBits\Foundation\Attribute\PluginAction;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

class SeasonController extends ActionController
{
    public function __construct(
        protected readonly SeasonRepository $seasonRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     */
    #[PluginAction(uncached: true)]
    public function createAction(Season $season): ResponseInterface
    {
        $this->seasonRepository->add($season);
        $this->addFlashMessage('Season created successfully.');

        return $this->redirectToSeries($season);
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     */
    #[PluginAction(uncached: true)]
    public function deleteAction(Season $season): ResponseInterface
    {
        $this->seasonRepository->remove($season);
        $this->addFlashMessage('Season deleted successfully.');

        return $this->redirectToSeries($season);
    }

    #[PluginAction(uncached: true)]
    public function editAction(Season $season): ResponseInterface
    {
        $this->view->assign('season', $season);

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['value' => 'newSeason'])]
    #[PluginAction(uncached: true)]
    public function newAction(?Season $newSeason = null): ResponseInterface
    {
        $this->view->assign('season', $newSeason);

        return $this->htmlResponse();
    }

    #[PluginAction(default: true)]
    public function showAction(Season $season): ResponseInterface
    {
        $this->view->assign('season', $season);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidArgumentException
     * @throws UnknownObjectException
     */
    #[PluginAction(uncached: true)]
    public function updateAction(Season $season): ResponseInterface
    {
        $this->seasonRepository->update($season);
        $this->addFlashMessage('Season updated successfully.');

        return $this->redirectToSeries($season);
    }

    /**
     * There is no list view for seasons. Therefore, the user is sent back to the series the season belongs to.
     */
    private function redirectToSeries(Season $season): ResponseInterface
    {
        $series = $season->getSeries();

        if (null === $series) {
            return $this->redirect('list', 'Series');
        }

        return $this->redirect('show', 'Series', null, ['series' => $series]);
    }
}
