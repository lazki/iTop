<?php

/**
 * Copyright (C) 2013-2019 Combodo SARL
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 *
 *
 */

namespace Combodo\iTop\Portal\Controller;

use Combodo\iTop\Portal\Brick\BrickCollection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *
 * @package Combodo\iTop\Portal\Controller
 * @author Guillaume Lajarige <guillaume.lajarige@combodo.com>
 * @since 2.3.0
 */
class DefaultController extends AbstractController
{
	/**
	 * @param \Symfony\Component\HttpFoundation\Request $oRequest
	 * @param \Combodo\iTop\Portal\Brick\BrickCollection $bricksCollection
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function homeAction(Request $oRequest, BrickCollection $bricksCollection)
	{
		$aData = array();

		// Rendering tiles
		$aData['aTilesRendering'] = array();
		/** @var \Combodo\iTop\Portal\Brick\PortalBrick $oBrick */
        foreach($bricksCollection->getBricks()['bricks'] as $oBrick)
		{
			// Doing it only for tile visible on home page to avoid unnecessary rendering
			if (($oBrick->GetVisibleHome() === true) && ($oBrick->GetTileControllerAction() !== null))
			{
				$aControllerActionParts = explode('::', $oBrick->GetTileControllerAction());
				if (count($aControllerActionParts) !== 2)
				{
					return new Response('Tile controller action must be of form "\Namespace\ControllerClass::FunctionName" for brick "' . $oBrick->GetId() . '"', 500);
				}

				$sControllerName = $aControllerActionParts[0];
				$sControllerAction = $aControllerActionParts[1];

				$oController = new $sControllerName();
				if(!$oController instanceof ContainerAwareInterface)
				{
					return new Response('Tile controller must be implement ContainerAwareInterface for brick "' . $oBrick->GetId() . '"', 500);
				}
				$oController->setContainer($this->container);
				/** @var Response $oResponse */
				$oResponse = $oController->$sControllerAction($oRequest, $oBrick->GetId());
				$aData['aTilesRendering'][$oBrick->GetId()] = $oResponse->getContent();
			}
		}

		// Home page template
		$template = $this->getParameter('combodo.portal.instance.conf')['properties']['templates']['home'];

		return $this->render($template, $aData);
	}

}