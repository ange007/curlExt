<?php
/**
 * randomUserAgent - класс дополнение к cURL расширению - curlExt.
 *
 * @author
 * Vladimir B.
 * 
* Date: 03.05.2014
 */

class randomUserAgent {
	var $languageList = array( 'ru-RU', 'en-US', 'en-GB', 'pl-PL', 'de-DE', 'bg', 'es-ES', 'ja', 'fr' );	
	
	var $linuxProcessorList = array( 'i686', 'x86_64' );
	var $macProcessorList = array( 'Intel', 'PPC', 'U; Intel', 'U; PPC' );
	var $windowsList = array( 'Windows NT 5.1', 'Windows NT 5.2', 'Windows NT 6.0', 'Windows NT 6.1', 'Windows NT 6.2' );
		
	var $browsersList = array( 'opera', 'firefox', 'chrome', 'ie' );
	var $browserVersion = array( 
			'opera' => array( 'min' => 15, 'max' => 20 ),
			'firefox' => array( 'min' => 3, 'max' => 25 ),
			'chrome' => array( 'min' => 24, 'max' => 30 ),
			'ie' => array( 'min' => 9, 'max' => 11 ),
		);
	
	var $params = array( 
		'OS' => '',
		'browser' => '',
		'language' => '',
	);

	function randomUA( )
	{
		
	}
	
	public function getUserAgent( )
	{
		$osList = $this->getOSList( ); // Список операционных систем
		$os = $osList[ rand( 0, count( $osList ) - 1 ) ]; //

		//* Язык
		if( !empty( $this->params[ 'language' ] ) ) { $language = $this->languageList[ $this->params[ 'language' ] ]; }
			else { $language = $this->languageList[ count( $this->languageList ) - 1 ]; }
		
		//* Браузер
		if( !empty( $this->params[ 'browser' ] ) ) { $browser = mb_strtolower( $this->params[ 'browser' ] ); }
			else { $browser = mb_strtolower( $this->browsersList[ rand( 0, count( $this->browsersList ) - 1 ) ] ); }
		
		//* ЮзерАгент
		$UA = '';
		switch( $browser ) 
		{
			case 'firefox':
				$UA = $this->getFirefox( $os, $language );
				break;
			case 'chrome':
				$UA = $this->getWebkit( $os );
				break;
			case 'opera':
				$UA = $this->getWebkit( $os, True );
				break;
			default:
				$UA = $this->getIE( $os, $language );
				break;		
		}

		//* Отдаём результат
		return $UA;
	}
	
	private function getOSList( )
	{
		$osList = array( );
		
		//* Версии Windows
		foreach( $this->windowsList as $processor ) { array_push( $osList, $processor ); }
		
		//* Linux процессоры
		foreach( $this->linuxProcessorList as $processor ) { array_push( $osList, 'X11; Linux ' . $processor ); }
		
		//* Mac процессоры
		foreach( $this->macProcessorList as $processor ) { array_push( $osList, 'Macintosh; U; ' . $processor . '; Mac OS X 10_' . rand( 4, 9 ) . '_' . rand( 1, 9 ) ); }
		
		//* Отдаём результат
		return $osList;
	}
		
	private function getFirefox( $os, $language )
	{
		$version = rand( $this->browserVersion[ 'firefox' ][ 'min' ], $this->browserVersion[ 'firefox' ][ 'max' ] );

		return 'Mozilla/5.0 (' . $os . '; ' . $language . '; rv:' . $version . '.0;) Gecko/20100101 Firefox/' . $version . '.0';
	}
	
	private function getWebkit( $os, $opera = False )
	{
		$chromeVersion = rand( $this->browserVersion[ 'chrome' ][ 'min' ], $this->browserVersion[ 'chrome' ][ 'max' ] ) . '.0' . rand( 1000, 2000 ) . '.' . rand( 0, 100 );
		$operaVersion = rand( $this->browserVersion[ 'opera' ][ 'min' ], $this->browserVersion[ 'opera' ][ 'max' ] ) . '.0' . rand( 1000, 2000 ) . '.' . rand( 0, 100 );
		$webKitVersion = '537.36';

		if( $opera )
		{
			return 'Mozilla/5.0 (' . $os . ') AppleWebKit/' . $webKitVersion . ' (KHTML, like Gecko) Chrome/' . $chromeVersion . '.0 Safari/' . $webKitVersion . 'OPR/' . $operaVersion;
		}
		else
		{ 
			return 'Mozilla/5.0 (' . $os . ') AppleWebKit/' . $webKitVersion . ' (KHTML, like Gecko) Chrome/' . $chromeVersion . '.0 Safari/' . $webKitVersion;
		}
	}
	
	private function getIE( $os, $language )
	{
		$version = rand( $this->browserVersion[ 'ie' ][ 'min' ], $this->browserVersion[ 'ie' ][ 'max' ] );
		
		return 'Mozilla/5.0 (compatible; MSIE ' . $version . '.0; ' . $os . '; Trident/' . rand( 4, 6 ) . '.0; ' . $language . ')';
	}
}
