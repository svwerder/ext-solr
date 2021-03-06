<?php
namespace ApacheSolrForTypo3\Solr\ViewHelpers\Backend\Security;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2017 dkd Internet Service GmbH <solr-support@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper implements an ifHasAccessToModule/else condition for BE users/groups.
 *
 * Class IfHasAccessToModuleViewHelper
 */
class IfHasAccessToModuleViewHelper extends AbstractConditionViewHelper
{
    /**
     * Message for that case, if $arguments['signature'] was used and module does not exist
     * @var string
     */
    const ERROR_APPENDIX_FOR_WRONG_SIGNATURE_ARGUMENT = 'Please check spelling and style by setting signature="mainName_ExtKeySubmoduleName".';

    /**
     * Message for that case, if $arguments['extension'], $arguments['main'] and $arguments['sub'] are used and module couldn't be resolved
     * @var string
     */
    const ERROR_APPENDIX_FOR_SIGNATURE_RESOLUTION = 'It was generated by setting extension="%s", main="%s", sub="%s", please check spelling and style by setting this arguments.';

    /**
     * Initializes following arguments: extension, main, sub, signature
     * Renders <f:then> child if the current logged in BE user belongs to the specified role (aka usergroup)
     * otherwise renders <f:else> child.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extension', 'string', 'The extension key.');
        $this->registerArgument('main', 'string', 'The main module name.');
        $this->registerArgument('sub', 'string', 'The sub module name.');
        $this->registerArgument('signature', 'string', 'The full signature of module. Simply mainmodulename_submodulename in most cases.');
    }


    protected static function evaluateCondition($arguments = null)
    {
        /* @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        $hasAccessToModule = $beUser->modAccess(
            self::getModuleConfiguration(self::getModuleSignatureFromArguments($arguments)),
            false
        );
        return $hasAccessToModule;
    }

    /**
     * Renders <f:then> child if $condition is true, otherwise renders <f:else> child.
     *
     * @todo This copy of the render method is just required for TYPO3 8 backwards compatibility, can be dropped when TYPO3 8 support is dropped.
     *
     * @param bool $condition View helper condition
     * @return string the rendered string
     */
    public function render()
    {
        if (static::evaluateCondition($this->arguments)) {
            return $this->renderThenChild();
        }
        return $this->renderElseChild();
    }

    protected static function getModuleConfiguration(string $moduleSignature)
    {
        return $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature];
    }

    /**
     * Resolves
     *
     * @param array $arguments
     * @return mixed|string
     */
    protected static function getModuleSignatureFromArguments(array $arguments)
    {
        $moduleSignature = $arguments['signature'];

        $possibleErrorMessageAppendix = self::ERROR_APPENDIX_FOR_WRONG_SIGNATURE_ARGUMENT;
        $possibleErrorCode = 1496311009;
        if (!is_string($moduleSignature)) {
            $moduleSignature = $arguments['main'];
            $subModuleName = $arguments['extension'] . GeneralUtility::underscoredToUpperCamelCase($arguments['sub']);
            $moduleSignature .= '_' . $subModuleName;
            $possibleErrorMessageAppendix = vsprintf(self::ERROR_APPENDIX_FOR_SIGNATURE_RESOLUTION, [$arguments['extension'], $arguments['main'], $arguments['sub']]);
            $possibleErrorCode = 1496311010;
        }
        if (!isset($GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature])) {
            throw new \RuntimeException(vsprintf('Module with signature "%s" is not configured or couldn\'t be resolved. ' . $possibleErrorMessageAppendix, [$moduleSignature]), $possibleErrorCode);
        }
        return $moduleSignature;
    }

    /**
     * Validates arguments given to this view helper.
     *
     * It checks if either signature or extension and main and sub are set.
     */
    public function validateArguments()
    {
        parent::validateArguments();

        if (empty($this->arguments['signature'])
            && (empty($this->arguments['extension']) || empty($this->arguments['main']) || empty($this->arguments['sub'])
            )
        ) {
            throw new \InvalidArgumentException('ifHasAccessToModule view helper requires either "signature" or all three other arguments: "extension", "main" and "sub". Please set arguments properly.', 1496314352);
        }
    }
}
