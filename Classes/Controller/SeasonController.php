<?php

declare(strict_types=1);

/*
 * This file is part of PSBits Episode Guide.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace PSBits\EpisodeGuide\Controller;

use PSBits\EpisodeGuide\Domain\Model\Season;
use PSBits\EpisodeGuide\Domain\Repository\SeasonRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;

/**
 * Class SeasonController
 *
 * @package PSBits\EpisodeGuide\Controller
 */
class SeasonController extends ActionController
{
    public function __construct(
        protected readonly SeasonRepository $seasonRepository,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function createAction(Season $season): ResponseInterface
    {
        $this->seasonRepository->add($season);
        $this->addFlashMessage('Season created successfully.');

        return $this->redirect('list');
    }

    /**
     * @throws IllegalObjectTypeException
     */
    public function deleteAction(Season $season): ResponseInterface
    {
        $this->seasonRepository->remove($season);
        $this->addFlashMessage('Season deleted successfully.');

        return $this->redirect('list');
    }

    public function editAction(Season $season): ResponseInterface
    {
        $this->view->assign('season', $season);

        return $this->htmlResponse();
    }

    #[IgnoreValidation(['value' => 'newSeason'])]
    public function newAction(?Season $newSeason = null): ResponseInterface
    {
        $this->view->assign('season', $newSeason);

        return $this->htmlResponse();
    }

    public function showAction(Season $season): ResponseInterface
    {
        $this->view->assign('season', $season);

        return $this->htmlResponse();
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function updateAction(Season $season): ResponseInterface
    {
        $this->seasonRepository->update($season);
        $this->addFlashMessage('Season updated successfully.');

        return $this->redirect('list');
    }
}
