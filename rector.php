<?php
declare(strict_types = 1);

// use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
// use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
// use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
// use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\SetList;
use Ssch\TYPO3Rector\CodeQuality\General\GeneralUtilityMakeInstanceToConstructorPropertyRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO311\v5\MigrateExtbaseViewInterfaceRector;
use Ssch\TYPO3Rector\TYPO313\v3\UseTYPO3CoreViewInterfaceInExtbaseRector;
use Ssch\TYPO3Rector\TYPO313\v4\MigratePluginContentElementAndPluginSubtypesRector;

return RectorConfig::configure()
	->withIndent("\t", 1)
	->withPHPStanConfigs([
		__DIR__ . '/.Build/vendor/saschaegerer/phpstan-typo3/extension.neon',
	])
	->withPaths([
		__DIR__ . '/Classes/',
		__DIR__ . '/Configuration/',
		__DIR__ . '/Tests/',
	])

	// ->withPreparedSets(
	// 	deadCode: true,
	// )
	->withSkip([
		RemoveUnusedVariableAssignRector::class,
		RemoveDeadZeroAndOneOperationRector::class,
		RemoveNullPropertyInitializationRector::class,
		SimplifyUselessVariableRector::class,
		// ExplicitBoolCompareRector::class,
		// FlipTypeControlToUseExclusiveTypeRector::class,
		// InlineArrayReturnAssignRector::class,
		// AddOverrideAttributeToOverriddenMethodsRector::class,

		UseTYPO3CoreViewInterfaceInExtbaseRector::class,

		MigrateExtbaseViewInterfaceRector::class,
		GeneralUtilityMakeInstanceToConstructorPropertyRector::class,

		RemoveUnusedPrivatePropertyRector::class => [
			__DIR__ . '/Tests/Unit/Backend/BackendLayoutDataProviderTest.php',
		],
		MigratePluginContentElementAndPluginSubtypesRector::class => [
			__DIR__ . '/Classes/Hooks/ContentElements.php',
		],
	])
	// uncomment to reach your current PHP version
	// ->withPhpSets()
	// ->withTypeCoverageLevel(0)
	// ->withDeadCodeLevel(30)
	// ->withCodeQualityLevel(0)
	// ->withCodingStyleLevel(0)
	->withPhpSets(php82: true)
	->withSets([
		SetList::PHP_POLYFILLS,
		SetList::DEAD_CODE,
		SetList::PRIVATIZATION,
		// SetList::CODE_QUALITY,
		// ArrayMergeOfNonArraysToSimpleArrayRector
		// ChangeArrayPushToArrayAssignRector
		// CombinedAssignRector
		// CombineIfRector
		// CompactToVariablesRector
		// CountArrayToEmptyArrayComparisonRector
		// DisallowedEmptyRuleFixerRector
		// ForRepeatedCountToOwnVariableRector
		// LocallyCalledStaticMethodToNonStaticRector
		// NumberCompareToMaxFuncCallRector
		// ShortenElseIfRector
		// SimplifyBoolIdenticalTrueRector
		// SimplifyDeMorganBinaryRector
		// SimplifyEmptyArrayCheckRector
		// SimplifyEmptyCheckOnEmptyArrayRector
		// SimplifyIfElseToTernaryRector
		// SimplifyIfReturnBoolRector
		// SimplifyRegexPatternRector
		// SingleInArrayToCompareRector
		// SwitchNegatedTernaryRector
		// UnusedForeachValueToArrayKeysRector
		// UseIdenticalOverEqualWithSameTypeRector

		Typo3SetList::CODE_QUALITY,
		// GeneralUtilityMakeInstanceToConstructorPropertyRector
		// AddErrorCodeToExceptionRector
		// UseExtensionKeyInLocalizationUtilityRector

		Typo3SetList::GENERAL,
		Typo3LevelSetList::UP_TO_TYPO3_14,
	])
;
