<?php

namespace Subugoe\Xmlinclude\Controller;

/*******************************************************************************
 * Copyright notice
 *
 * Copyright (C) 2012-2013 by Sven-S. Porst, SUB Göttingen
 * <porst@sub.uni-goettingen.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 ******************************************************************************/

use Subugoe\Xmlinclude\Service\IncludeService;
use Subugoe\Xmlinclude\Utility\DebugUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * XMLInclude controller for the XMLInclude extension.
 * Provides the main controller for the xmlinclude plug-in.
 */
class IncludeController extends ActionController
{
    /**
     * @var IncludeService
     */
    private $includeService;

    /**
     * @var PageRenderer
     */
    private $pageRenderer;

    public function __construct(IncludeService $includeService, PageRenderer $pageRenderer)
    {
        parent::__construct();
        $this->includeService = $includeService;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Initializer.
     */
    public function initializeAction()
    {
        $this->errors = [];
        $this->includeService->setSettings($this->settings);
        $this->includeService->setArguments($this->request->getArguments());
        $this->includeService->setRequestUri($this->request->getRequestUri());
        DebugUtility::$data['settings'] = $this->settings;
    }

    /**
     * Index.
     */
    public function indexAction()
    {
        $this->addResourcesToHead();

        $XML = $this->includeService->XML();
        if ($XML) {
            $XML->formatOutput = true;
            $this->view->assign('xml', $XML->saveHTML($XML->firstChild));
        }

        $this->view->assign('settings', $this->settings);
        $this->view->assign('errors', DebugUtility::$error);
        $this->view->assign('debugInformation', DebugUtility::$data);
    }

    /**
     * Helper: Inserts style and script tags into the page’s head.
     */
    protected function addResourcesToHead()
    {
        if (array_key_exists('headCSS', $this->settings)) {
            foreach ($this->settings['headCSS'] as $CSSPath) {
                $this->pageRenderer->addCssFile($CSSPath);
            }
        }

        if (array_key_exists('headJavaScript', $this->settings)) {
            foreach ($this->settings['headJavaScript'] as $JSPath) {
                $this->pageRenderer->addJsFile($JSPath);
            }
        }
    }
}
