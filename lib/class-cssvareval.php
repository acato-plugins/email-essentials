<?php
/**
 * Evaluate CSS variables to their final values.
 *
 * @package Acato_Email_Essentials
 */

namespace Acato\Email_Essentials;

use Sabberworm\CSS\Parser;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\Value\CSSFunction;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\OutputFormat;

/**
 * Class to evaluate CSS variables to their final values.
 */
class CssVarEval {
	/**
	 * Evaluate CSS variables to their final values.
	 *
	 * @param string $css The CSS string to evaluate.
	 *
	 * @return string The evaluated CSS string.
	 */
	public static function evaluate( $css ) {
		return self::resolve_css_variables( $css );
	}

	/**
	 * Resolve CSS variables in the given CSS string.
	 *
	 * @param string $css The CSS string to process.
	 *
	 * @return string The CSS string with variables resolved.
	 */
	public static function resolve_css_variables( $css ) {
		$parser = new Parser( $css );
		/**
		 * The parsed CSS document.
		 *
		 * @var Document $document
		 */
		$document = $parser->parse();

		$global_vars = [];

		// Extract variables from :root.
		foreach ( $document->getAllDeclarationBlocks() as $block ) {
			if ( $block->getSelectors() && ! empty( $block->getSelectors()[0] ) && ':root' === $block->getSelectors()[0]->getSelector() ) {
				foreach ( $block->getRules() as $rule ) {
					// At this time, we employ a WP 5.x compatibility, so we can not (yet) use str_starts_with.
					if ( 0 === strpos( $rule->getRule(), '--' ) ) {
						$global_vars[ $rule->getRule() ] = $rule->getValue();
					}
				}
			}
		}

		// Function to resolve a value, recursively handling var() functions.
		$resolve_value = function ( $value, $local_vars = [] ) use ( &$resolve_value, $global_vars ) {
			if ( $value instanceof CSSFunction && 'var' === $value->getName() ) {
				$args     = $value->getArguments();
				$var_name = trim( (string) $args[0] );
				$fallback = count( $args ) > 1 ? $args[1] : null;

				// Check local, then global.
				if ( array_key_exists( $var_name, $local_vars ) ) {
					return $resolve_value( $local_vars[ $var_name ], $local_vars );
				} elseif ( array_key_exists( $var_name, $global_vars ) ) {
					return $resolve_value( $global_vars[ $var_name ], $local_vars );
				} elseif ( null !== $fallback ) {
					return $resolve_value( $fallback, $local_vars );
				} else {
					return 'unset';
				}
			}

			// Handle lists like font: var(--font-family), sans-serif.
			if ( $value instanceof RuleValueList ) {
				$new_list     = clone $value;
				$a_components = [];
				foreach ( $new_list->getListComponents() as $i => $component ) {
					$a_components[ $i ] = $resolve_value( $component, $local_vars );
				}
				$new_list->setListComponents( $a_components );

				return $new_list;
			}

			return $value;
		};

		// Apply resolution.
		foreach ( $document->getAllDeclarationBlocks() as $block ) {
			$local_vars = [];
			foreach ( $block->getRules() as $rule ) {
				$name = $rule->getRule();

				// At this time, we employ a WP 5.x compatibility, so we can not (yet) use str_starts_with.
				if ( 0 === strpos( $name, '--' ) ) {
					$local_vars[ $name ] = $rule->getValue();
				} else {
					$resolved = $resolve_value( $rule->getValue(), $local_vars );
					$rule->setValue( $resolved );
				}
			}
		}

		return $document->render( OutputFormat::createPretty() );
	}
}
