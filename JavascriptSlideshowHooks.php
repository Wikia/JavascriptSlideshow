<?php

use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;

/**
 * Javascript Slideshow
 * Javascript Slideshow Hooks
 *
 * @author  @See $wgExtensionCredits
 * @package Javacsript Slideshow
 * @link    https://gitlab.com/hydrawiki/extensions/javascriptslideshow
 */

class JavascriptSlideshowHooks implements ParserFirstCallInitHook {
	/**
	 * Sets up this extensions parser functions.
	 *
	 * @param Parser $parser
	 * @return bool true
	 */
	public function onParserFirstCallInit( $parser ): bool {
		$output = RequestContext::getMain()->getOutput();
		$output->addModules( 'ext.slideshow.main' );

		$parser->setHook( 'slideshow', [ self::class, 'renderSlideshowTag' ] );
		$parser->setFunctionHook( 'slideshow', [ self::class, 'renderSlideshowParserFunction' ] );

		return true;
	}

	/**
	 * Explodes a string that contains a space delimited array of associative key value pairs.
	 *
	 * @param string $string String to explode arguments from.
	 * @return array Constructed array of associative key value pairs.
	 */
	private static function explodeArguments( string $string ): array {
		$pairDelimiter = ' ';
		$kvDelimiter = '=';

		$_pieces = explode( $pairDelimiter, $string );
		if ( count( $_pieces ) ) {
			foreach ( $_pieces as $value ) {
				// We only want the information if it is a valid key value pair.
				if ( strpos( $value, $kvDelimiter ) ) {
					[ $key, $value ] = explode( $kvDelimiter, $value );
					$arguments[trim( $key )] = trim( $value );
				}
			}
		}
		return $arguments ?? [];
	}

	/**
	 * Initiates some needed classes.
	 *
	 * @param object &$parser Parser object passed as a reference.
	 * @param string $input First argument passed to function tag, HTML input of <div> tags.
	 * @param string $options Second argument passed to function tag, delimited list of options.
	 * @return string HTML output of self::renderSlideshow()
	 */
	public static function renderSlideshowParserFunction( object &$parser, string $input = '', string $options = '' ): string {
		return self::renderSlideshow( $input, self::explodeArguments( $options ) );
	}

	/**
	 * The callback function for converting the input text to HTML output.
	 *
	 * @param $input
	 * @param $argv
	 * @param $parser
	 * @param $frame
	 * @return string
	 */
	public static function renderSlideshowTag( $input, $argv, $parser, $frame ): string {
		$wikitext = self::renderSlideshow( $input, $argv );
		return $parser->recursiveTagParse( $wikitext, $frame );
	}

	/**
	 * Renders the slideshow information into output for the calling tag or function.
	 *
	 * @param string $wikitext Combined raw HTML and wiki text.
	 * @param array $options Options that have been parsed by self::explodeArguments()
	 * @return string Rendered output
	 */
	private static function renderSlideshow( string $wikitext, array $options = [] ): string {
		$validSequences = [ 'forward', 'backward', 'random' ];
		$validTransitions = [ 'cut', 'fade', 'blindDown' ];

		$id = ( $options['id'] ?? 'slideshow_' . rand() );

		// set default if not set
		$refresh = ( $options['refresh'] ?? '1000' );
		$sequence = ( $options['sequence'] ?? 'forward' );
		$transition = ( $options['transition'] ?? 'cut' );
		$transitiontime = ( $options['transitiontime'] ?? '400' );
		$center = ( $options['center'] ?? 'false' );

		// validate input
		if ( !in_array( $sequence, $validSequences ) ) {
			return '<span class="error">JavascriptSlideshow: ' .
				   wfMessage(
					   'javascriptslideshow-invalid-parameter',
					   'sequence',
					   $sequence,
					   implode( ', ', $validSequences ) )->inContentLanguage() .
				   '</span>';
		} elseif ( !in_array( $transition, $validTransitions ) ) {
			return '<span class="error">JavascriptSlideshow: ' .
				   wfMessage(
					   'javascriptslideshow-invalid-parameter',
					   'transition',
					   $transition,
					   implode( ', ', $validTransitions ) )->inContentLanguage() .
				   '</span>';
		} elseif ( !is_numeric( $refresh ) ) {
			return '<span class="error">JavascriptSlideshow: ' .
				   wfMessage( 'javascriptslideshow-invalid-num-parameter', 'refresh' )->inContentLanguage() . '</span>';
		} elseif ( !is_numeric( $transitiontime ) ) {
			return '<span class="error">JavascriptSlideshow: ' .
				   wfMessage( 'javascriptslideshow-invalid-num-parameter', 'transitiontime' )->inContentLanguage() . '</span>';
		} else {
			$output = '';
			$dataAttrs = "data-transition='$transition' data-refresh='$refresh' " .
						 "data-sequence='$sequence' data-transitiontime='$transitiontime'";
			$styleAttrs = ( $center == 'true' ? "style='margin-left:auto;margin-right:auto'" : "" );
			$output .= "<div id='$id' class='slideshow' $dataAttrs $styleAttrs >$wikitext</div> ";
			$output .= "<div id='$id-spacer' class='slideshowspacer'></div>";
			return $output;
		}
	}

	/**
	 * Add a CSS module with addModuleStyles to ensure it's loaded
	 * even if there is no Javascript support
	 */
	public static function loadModules(): bool {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$wgOut = $config->get( 'Out' );
		$wgOut->addModuleStyles( 'ext.slideshow.css' );
		return true;
	}
}
