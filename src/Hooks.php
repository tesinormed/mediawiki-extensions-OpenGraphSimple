<?php

namespace MediaWiki\Extension\OpenGraphSimple;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\MainConfigNames;
use RuntimeException;

class Hooks implements OutputPageParserOutputHook {
	private Config $extensionConfig;

	public function __construct( ConfigFactory $configFactory ) {
		$this->extensionConfig = $configFactory->makeConfig( 'opengraphsimple' );
	}

	/** @noinspection PhpUnused */
	public static function onRegistration(): void {
		global $wgPageImagesOpenGraph, $wgPageImagesOpenGraphFallbackImage;

		if ( $wgPageImagesOpenGraph !== true ) {
			throw new RuntimeException( '$wgPageImagesOpenGraph must be set to true' );
		}

		if ( $wgPageImagesOpenGraphFallbackImage === false ) {
			throw new RuntimeException( '$wgPageImagesOpenGraphFallbackImage must be set' );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 * @inheritDoc
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		if ( !in_array(
			needle: $outputPage->getTitle()->getNamespace(),
			haystack: $this->extensionConfig->get( 'OpenGraphSimpleNamespaces' ),
			strict: true
		) ) {
			return;
		}

		$config = $outputPage->getConfig();
		$title = $outputPage->getTitle();
		$siteName = $config->get( MainConfigNames::Sitename );

		$metaProperties = [];

		if ( $title->isMainPage() ) {
			$metaProperties['og:title'] = $siteName;
			$metaProperties['og:type'] = 'website';
		} else {
			$metaProperties['og:site_name'] = $siteName;
			$metaProperties['og:title'] = htmlspecialchars( $outputPage->getDisplayTitle() );
			$metaProperties['og:type'] = 'article';
		}
		$metaProperties['og:url'] = $outputPage->getCanonicalUrl();
		if ( $metaProperties['og:url'] === false ) {
			$metaProperties['og:url'] = $title->getFullURL();
		}
		$metaProperties['og:description'] = $parserOutput->getPageProperty( 'description' );

		foreach ( $metaProperties as $property => $value ) {
			$outputPage->addMeta( $property, $value );
		}
	}
}
