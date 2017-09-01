<?php

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;

/**
 * Usage: {$crop = "{getCrop image=$resource cropArea=default}"}
 */
function smarty_function_getCrop($params, $smarty) {
	$imageOrReference = $params['image'];

	if (!$imageOrReference->getProperty('crop')) {
		return '';
	}

	$crop = $imageOrReference->getProperty('crop');
	if ($crop) {
		$cropVariantCollection = CropVariantCollection::create($crop);
		$cropAreaName = $params['cropArea'] ?: 'default';
		$cropArea = $cropVariantCollection->getCropArea($cropAreaName);
		if ($cropArea->isEmpty()) {
			$crop = null;
		} else {
			$crop = $cropArea->makeAbsoluteBasedOnFile($imageOrReference);
		}
	}

	return $crop;
}
