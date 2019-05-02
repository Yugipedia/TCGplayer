<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'TCGplayer' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['TCGplayer'] = __DIR__ . '/i18n';
	
	$wgExtensionMessagesFiles['TCGplayerMagic'] = __DIR__ . '/TCGplayer.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for TCGplayer extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the TCGplayer extension requires MediaWiki 1.25+' );
}
