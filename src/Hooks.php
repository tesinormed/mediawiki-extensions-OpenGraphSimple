<?php

namespace MediaWiki\Extension\OpenGraphSimple;

use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\MainConfigNames;
use PageImages\PageImages;
use RuntimeException;

class Hooks implements OutputPageParserOutputHook {
	/** @noinspection PhpUnused */
	public static function onRegistration(): void {
		global $wgPageImagesOpenGraph, $wgPageImagesOpenGraphFallbackImage;

		if ( $wgPageImagesOpenGraph !== false ) {
			throw new RuntimeException( '$wgPageImagesOpenGraph must be set to false' );
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
		$config = $outputPage->getConfig();
		$title = $outputPage->getTitle();
		$description = $parserOutput->getPageProperty( 'description' );
		$image = PageImages::getPageImage( $title );
		if ( !$image ) {
			$image = $outputPage->getConfig()->get( 'PageImagesOpenGraphFallbackImage' );
		} else {
			$image = $image->getFullUrl();
		}
		$siteName = $config->get( MainConfigNames::Sitename );

		$metaProperties = [];

		// Open Graph
		if ( $title->isMainPage() ) {
			$metaProperties['og:title'] = $siteName;
			$metaProperties['og:type'] = 'website';
		} else {
			$metaProperties['og:site_name'] = $siteName;
			$metaProperties['og:title'] = htmlspecialchars( $outputPage->getDisplayTitle() );
			$metaProperties['og:type'] = 'article';
		}
		$metaProperties['og:image'] = $image;
		$metaProperties['og:url'] = $outputPage->getCanonicalUrl();
		if ( $metaProperties['og:url'] === false ) {
			$metaProperties['og:url'] = $title->getFullURL();
		}
		$metaProperties['og:description'] = $description;

		// Twitter card
		$metaProperties['twitter:card'] = 'summary';
		if ( $title->isMainPage() ) {
			$metaProperties['twitter:title'] = $siteName;
		} else {
			$metaProperties['twitter:title'] = htmlspecialchars( $outputPage->getDisplayTitle() );
		}
		if ( $description !== null ) {
			$metaProperties['twitter:description'] = mb_strimwidth( $description, 0, 200, "&hellip;" );
		}
		$metaProperties['twitter:image'] = $image;

		foreach ( $metaProperties as $property => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			$outputPage->addMeta( $property, $value );
		}
	}
}
