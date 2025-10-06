<?php // phpcs:disable -- imported code. Needs refactoring.

/**
 * Evaluate CSS variables to their final values.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

use Sabberworm\CSS\Parser;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\Value\CSSFunction;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\OutputFormat;

class CssVarEval {
	/**
	 * Evaluate CSS variables to their final values.
	 *
	 * @param string $css The CSS string to evaluate.
	 *
	 * @return string The evaluated CSS string.
	 */
	public static function evaluate( string $css ): string {
		return self::resolveCssVariables( $css );
	}

	public static function resolveCssVariables( string $css ): string {
		$parser = new Parser( $css );
		/** @var Document $document */
		$document = $parser->parse();

		$globalVars = [];

		// Extract variables from :root
		foreach ( $document->getAllDeclarationBlocks() as $block ) {
			if ( $block->getSelectors() && ! empty( $block->getSelectors()[0] ) && $block->getSelectors()[0]->getSelector() === ':root' ) {
				foreach ( $block->getRules() as $rule ) {
					if ( str_starts_with( $rule->getRule(), '--' ) ) {
						$globalVars[ $rule->getRule() ] = $rule->getValue();
					}
				}
			}
		}

		// Function to resolve a value, recursively handling var() functions
		$resolveValue = function ( $value, $localVars = [] ) use ( &$resolveValue, $globalVars ) {
			if ( $value instanceof CSSFunction && $value->getName() === 'var' ) {
				$args     = $value->getArguments();
				$varName  = trim( (string) $args[0] );
				$fallback = count( $args ) > 1 ? $args[1] : null;

				// Check local, then global
				if ( array_key_exists( $varName, $localVars ) ) {
					return $resolveValue( $localVars[ $varName ], $localVars );
				} elseif ( array_key_exists( $varName, $globalVars ) ) {
					return $resolveValue( $globalVars[ $varName ], $localVars );
				} elseif ( $fallback !== null ) {
					return $resolveValue( $fallback, $localVars );
				} else {
					return 'unset';
				}
			}

			// Handle lists like font: var(--font-family), sans-serif
			if ( $value instanceof RuleValueList ) {
				$newList     = clone $value;
				$aComponents = [];
				foreach ( $newList->getListComponents() as $i => $component ) {
					$aComponents[ $i ] = $resolveValue( $component, $localVars );
				}
				$newList->setListComponents( $aComponents );

				return $newList;
			}

			return $value;
		};

		// Apply resolution
		foreach ( $document->getAllDeclarationBlocks() as $block ) {
			$localVars = [];
			foreach ( $block->getRules() as $rule ) {
				$name = $rule->getRule();

				if ( str_starts_with( $name, '--' ) ) {
					$localVars[ $name ] = $rule->getValue();
				} else {
					$resolved = $resolveValue( $rule->getValue(), $localVars );
					$rule->setValue( $resolved );
				}
			}
		}

		return $document->render( OutputFormat::createPretty() );
	}
}
